<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["admin_fullname"] ?? "Administrator";

function admin_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function severity_class($level)
{
    $level = strtolower(trim((string) $level));

    if ($level === "normal") {
        return "severity-normal";
    }

    if ($level === "mild") {
        return "severity-mild";
    }

    if ($level === "moderate") {
        return "severity-moderate";
    }

    if (
        $level === "severe" ||
        $level === "extremely severe"
    ) {
        return "severity-severe";
    }

    return "severity-default";
}

function admin_count($conn, $query)
{
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

function screening_page_url($page)
{
    $parameters = $_GET;
    $parameters["page"] = $page;

    return "screening_results.php?" . http_build_query($parameters);
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$severity = trim($_GET["severity"] ?? "");
$attention = trim($_GET["attention"] ?? "");
$dateFrom = trim($_GET["date_from"] ?? "");
$dateTo = trim($_GET["date_to"] ?? "");
$sort = $_GET["sort"] ?? "newest";

$allowedCategories = [
    "",
    "depression",
    "anxiety",
    "stress"
];

$allowedSeverities = [
    "",
    "Normal",
    "Mild",
    "Moderate",
    "Severe",
    "Extremely Severe"
];

$allowedAttention = [
    "",
    "1",
    "0"
];

$allowedSort = [
    "newest",
    "oldest",
    "highest_depression",
    "highest_anxiety",
    "highest_stress"
];

if (!in_array($category, $allowedCategories, true)) {
    $category = "";
}

if (!in_array($severity, $allowedSeverities, true)) {
    $severity = "";
}

if (!in_array($attention, $allowedAttention, true)) {
    $attention = "";
}

if (!in_array($sort, $allowedSort, true)) {
    $sort = "newest";
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$recordsPerPage = 10;
$currentPage = max(1, (int) ($_GET["page"] ?? 1));
$offset = ($currentPage - 1) * $recordsPerPage;

/*
|--------------------------------------------------------------------------
| Build filter query
|--------------------------------------------------------------------------
*/

$whereParts = [];
$parameterValues = [];
$parameterTypes = "";

if ($search !== "") {
    $searchValue = "%" . $search . "%";

    $whereParts[] = "
        (
            s.fullname LIKE ?
            OR s.student_number LIKE ?
            OR s.email LIKE ?
        )
    ";

    $parameterValues[] = $searchValue;
    $parameterValues[] = $searchValue;
    $parameterValues[] = $searchValue;

    $parameterTypes .= "sss";
}

if ($severity !== "") {
    if ($category === "depression") {
        $whereParts[] = "dr.depression_level = ?";
    } elseif ($category === "anxiety") {
        $whereParts[] = "dr.anxiety_level = ?";
    } elseif ($category === "stress") {
        $whereParts[] = "dr.stress_level = ?";
    } else {
        $whereParts[] = "
            (
                dr.depression_level = ?
                OR dr.anxiety_level = ?
                OR dr.stress_level = ?
            )
        ";

        $parameterValues[] = $severity;
        $parameterValues[] = $severity;
        $parameterValues[] = $severity;

        $parameterTypes .= "sss";
    }

    if ($category !== "") {
        $parameterValues[] = $severity;
        $parameterTypes .= "s";
    }
}

if ($attention !== "") {
    $whereParts[] = "dr.requires_attention = ?";
    $parameterValues[] = (int) $attention;
    $parameterTypes .= "i";
}

if ($dateFrom !== "") {
    $whereParts[] = "DATE(dr.screening_date) >= ?";
    $parameterValues[] = $dateFrom;
    $parameterTypes .= "s";
}

if ($dateTo !== "") {
    $whereParts[] = "DATE(dr.screening_date) <= ?";
    $parameterValues[] = $dateTo;
    $parameterTypes .= "s";
}

$whereSql = "";

if (!empty($whereParts)) {
    $whereSql = "WHERE " . implode(" AND ", $whereParts);
}

/*
|--------------------------------------------------------------------------
| Sorting
|--------------------------------------------------------------------------
*/

$orderSql = "dr.screening_date DESC";

switch ($sort) {
    case "oldest":
        $orderSql = "dr.screening_date ASC";
        break;

    case "highest_depression":
        $orderSql = "dr.depression_score DESC, dr.screening_date DESC";
        break;

    case "highest_anxiety":
        $orderSql = "dr.anxiety_score DESC, dr.screening_date DESC";
        break;

    case "highest_stress":
        $orderSql = "dr.stress_score DESC, dr.screening_date DESC";
        break;
}

/*
|--------------------------------------------------------------------------
| Count filtered records
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total
    FROM dass_results dr
    INNER JOIN students s
        ON s.student_id = dr.student_id
    {$whereSql}
";

$countStatement = $conn->prepare($countSql);

$totalFilteredResults = 0;

if ($countStatement) {
    if (!empty($parameterValues)) {
        $countStatement->bind_param(
            $parameterTypes,
            ...$parameterValues
        );
    }

    $countStatement->execute();

    $countResult = $countStatement->get_result();
    $countRow = $countResult->fetch_assoc();

    $totalFilteredResults = (int) ($countRow["total"] ?? 0);

    $countStatement->close();
}

$totalPages = max(
    1,
    (int) ceil($totalFilteredResults / $recordsPerPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $recordsPerPage;
}

/*
|--------------------------------------------------------------------------
| Retrieve screening records
|--------------------------------------------------------------------------
*/

$screeningSql = "
    SELECT
        dr.student_id,
        dr.depression_score,
        dr.anxiety_score,
        dr.stress_score,
        dr.depression_level,
        dr.anxiety_level,
        dr.stress_level,
        dr.requires_attention,
        dr.screening_date,
        s.fullname,
        s.student_number,
        s.email,
        s.course,
        s.semester
    FROM dass_results dr
    INNER JOIN students s
        ON s.student_id = dr.student_id
    {$whereSql}
    ORDER BY {$orderSql}
    LIMIT ? OFFSET ?
";

$screeningStatement = $conn->prepare($screeningSql);
$screenings = [];

if ($screeningStatement) {
    $screeningValues = $parameterValues;
    $screeningValues[] = $recordsPerPage;
    $screeningValues[] = $offset;

    $screeningTypes = $parameterTypes . "ii";

    $screeningStatement->bind_param(
        $screeningTypes,
        ...$screeningValues
    );

    $screeningStatement->execute();

    $screeningResult = $screeningStatement->get_result();

    while ($row = $screeningResult->fetch_assoc()) {
        $screenings[] = $row;
    }

    $screeningStatement->close();
}

/*
|--------------------------------------------------------------------------
| Summary statistics
|--------------------------------------------------------------------------
*/

$totalScreenings = admin_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results"
);

$attentionCount = admin_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE requires_attention = 1"
);

$screeningsToday = admin_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE DATE(screening_date) = CURDATE()"
);

$screenedStudents = admin_count(
    $conn,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM dass_results"
);

$attentionPercentage = 0;

if ($totalScreenings > 0) {
    $attentionPercentage = (
        $attentionCount / $totalScreenings
    ) * 100;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DASS-21 Results | Poly-Health</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="admin-dashboard-body">

<div class="admin-dashboard-layout">

    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-sidebar-header">

            <a
                href="dashboard.php"
                class="admin-sidebar-logo"
            >
                <span class="admin-sidebar-logo-icon">
                    +
                </span>

                <span>POLY-HEALTH</span>
            </a>

            <button
                type="button"
                class="admin-sidebar-close"
                id="closeAdminSidebar"
                aria-label="Close sidebar"
            >
                ×
            </button>

        </div>

        <div class="admin-sidebar-profile">

            <div class="admin-sidebar-avatar">
                <?= admin_escape(
                    strtoupper(
                        substr(trim($adminName), 0, 1)
                    )
                ); ?>
            </div>

            <div>

                <strong>
                    <?= admin_escape($adminName); ?>
                </strong>

                <small>System Administrator</small>

            </div>

        </div>

        <nav class="admin-sidebar-nav">

            <span class="admin-nav-label">
                Main Menu
            </span>

            <a
                href="dashboard.php"
                class="admin-nav-link"
            >
                <span>🏠</span>
                Dashboard
            </a>

            <a
                href="students.php"
                class="admin-nav-link"
            >
                <span>🎓</span>
                Manage Students
            </a>

            <a
                href="screening_results.php"
                class="admin-nav-link active"
            >
                <span>📊</span>
                DASS-21 Results
            </a>

            <a
                href="journals.php"
                class="admin-nav-link"
            >
                <span>📖</span>
                Journal Entries
            </a>

            <a
                href="reports.php"
                class="admin-nav-link"
            >
                <span>📈</span>
                Reports
            </a>

            <span class="admin-nav-label admin-nav-label-second">
                Account
            </span>

            

            <a
                href="logout.php"
                class="admin-nav-link admin-logout-link"
            >
                <span>🚪</span>
                Logout
            </a>

        </nav>

        <div class="admin-sidebar-footer">

            <span>🔒</span>

            <p>
                Screening information must remain confidential.
            </p>

        </div>

    </aside>

    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>

    <div class="admin-dashboard-main">

        <header class="admin-topbar">

            <button
                type="button"
                class="admin-menu-button"
                id="openAdminSidebar"
                aria-label="Open sidebar"
            >
                ☰
            </button>

            <div class="admin-topbar-title">

                <span>Administrator Portal</span>

                <strong>DASS-21 Screening Results</strong>

            </div>

            <div class="admin-topbar-account">

                <div class="admin-topbar-avatar">
                    <?= admin_escape(
                        strtoupper(
                            substr(trim($adminName), 0, 1)
                        )
                    ); ?>
                </div>

                <div class="admin-topbar-user">

                    <strong>
                        <?= admin_escape($adminName); ?>
                    </strong>

                    <small>Administrator</small>

                </div>

                <a
                    href="logout.php"
                    class="btn admin-topbar-logout"
                >
                    Logout
                </a>

            </div>

        </header>

        <main class="admin-dashboard-content">

            <section class="admin-page-header">

                <div>

                    <span class="admin-section-label">
                        Mental Health Screening
                    </span>

                    <h1>DASS-21 Results</h1>

                    <p>
                        Review depression, anxiety and stress screening
                        results completed by registered students.
                    </p>

                </div>

                <a
                    href="dashboard.php"
                    class="btn admin-secondary-button"
                >
                    ← Dashboard
                </a>

            </section>

            <section class="admin-student-stat-grid">

                <article class="admin-student-stat-card">

                    <span>📋</span>

                    <div>

                        <small>Total Screenings</small>

                        <strong>
                            <?= number_format($totalScreenings); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>🎓</span>

                    <div>

                        <small>Students Screened</small>

                        <strong>
                            <?= number_format($screenedStudents); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>✅</span>

                    <div>

                        <small>Completed Today</small>

                        <strong>
                            <?= number_format($screeningsToday); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card attention">

                    <span>⚠️</span>

                    <div>

                        <small>Require Attention</small>

                        <strong>
                            <?= number_format($attentionCount); ?>
                        </strong>

                    </div>

                </article>

            </section>

            <section class="admin-screening-summary-card">

                <div>

                    <span class="admin-section-label">
                        Attention Rate
                    </span>

                    <strong>
                        <?= number_format($attentionPercentage, 1); ?>%
                    </strong>

                    <p>
                        Percentage of screening records currently marked
                        as requiring attention.
                    </p>

                </div>

                <div class="admin-screening-progress">

                    <div
                        class="admin-screening-progress-bar"
                        style="width: <?= min(
                            100,
                            max(0, $attentionPercentage)
                        ); ?>%;"
                    ></div>

                </div>

            </section>

            <!-- Filters -->
            <section class="admin-filter-card">

                <form
                    method="GET"
                    action="screening_results.php"
                    class="admin-screening-filter-form"
                >

                    <div class="admin-screening-search">

                        <label for="search">
                            Search student
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            value="<?= admin_escape($search); ?>"
                            placeholder="Name, student number or email"
                        >

                    </div>

                    <div>

                        <label for="category">
                            Category
                        </label>

                        <select
                            class="form-select"
                            id="category"
                            name="category"
                        >

                            <option value="">
                                Any category
                            </option>

                            <option
                                value="depression"
                                <?= $category === "depression"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Depression
                            </option>

                            <option
                                value="anxiety"
                                <?= $category === "anxiety"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Anxiety
                            </option>

                            <option
                                value="stress"
                                <?= $category === "stress"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Stress
                            </option>

                        </select>

                    </div>

                    <div>

                        <label for="severity">
                            Severity
                        </label>

                        <select
                            class="form-select"
                            id="severity"
                            name="severity"
                        >

                            <option value="">
                                All severity levels
                            </option>

                            <?php
                            $severityOptions = [
                                "Normal",
                                "Mild",
                                "Moderate",
                                "Severe",
                                "Extremely Severe"
                            ];
                            ?>

                            <?php foreach (
                                $severityOptions as $severityOption
                            ): ?>

                                <option
                                    value="<?= admin_escape(
                                        $severityOption
                                    ); ?>"
                                    <?= $severity === $severityOption
                                        ? "selected"
                                        : ""; ?>
                                >
                                    <?= admin_escape($severityOption); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div>

                        <label for="attention">
                            Status
                        </label>

                        <select
                            class="form-select"
                            id="attention"
                            name="attention"
                        >

                            <option value="">
                                All results
                            </option>

                            <option
                                value="1"
                                <?= $attention === "1"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Requires attention
                            </option>

                            <option
                                value="0"
                                <?= $attention === "0"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Stable
                            </option>

                        </select>

                    </div>

                    <div>

                        <label for="date_from">
                            From date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="date_from"
                            name="date_from"
                            value="<?= admin_escape($dateFrom); ?>"
                        >

                    </div>

                    <div>

                        <label for="date_to">
                            To date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="date_to"
                            name="date_to"
                            value="<?= admin_escape($dateTo); ?>"
                        >

                    </div>

                    <div>

                        <label for="sort">
                            Sort
                        </label>

                        <select
                            class="form-select"
                            id="sort"
                            name="sort"
                        >

                            <option
                                value="newest"
                                <?= $sort === "newest"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Newest first
                            </option>

                            <option
                                value="oldest"
                                <?= $sort === "oldest"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Oldest first
                            </option>

                            <option
                                value="highest_depression"
                                <?= $sort === "highest_depression"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Highest depression
                            </option>

                            <option
                                value="highest_anxiety"
                                <?= $sort === "highest_anxiety"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Highest anxiety
                            </option>

                            <option
                                value="highest_stress"
                                <?= $sort === "highest_stress"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Highest stress
                            </option>

                        </select>

                    </div>

                    <div class="admin-filter-actions">

                        <button
                            type="submit"
                            class="btn btn-poly"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="screening_results.php"
                            class="btn admin-filter-reset"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>

            <!-- Results table -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Screening Records
                        </span>

                        <h2>Student Screening Results</h2>

                    </div>

                    <span class="admin-record-count">
                        <?= number_format($totalFilteredResults); ?>
                        result<?= $totalFilteredResults === 1 ? "" : "s"; ?>
                    </span>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($screenings)): ?>

                        <div class="table-responsive">

                            <table class="table admin-screening-results-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Depression</th>
                                        <th>Anxiety</th>
                                        <th>Stress</th>
                                        <th>Status</th>
                                        <th>Screening Date</th>
                                        <th>Action</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($screenings as $screening): ?>

                                        <tr>

                                            <td>

                                                <div class="admin-table-student">

                                                    <div class="admin-table-avatar">

                                                        <?= admin_escape(
                                                            strtoupper(
                                                                substr(
                                                                    trim(
                                                                        $screening["fullname"]
                                                                    ),
                                                                    0,
                                                                    1
                                                                )
                                                            )
                                                        ); ?>

                                                    </div>

                                                    <div>

                                                        <strong>
                                                            <?= admin_escape(
                                                                $screening["fullname"]
                                                            ); ?>
                                                        </strong>

                                                        <small>
                                                            <?= admin_escape(
                                                                $screening["student_number"]
                                                            ); ?>
                                                        </small>

                                                        <small>
                                                            <?= admin_escape(
                                                                $screening["course"]
                                                            ); ?>
                                                        </small>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>

                                                <div class="admin-screening-score-cell">

                                                    <strong>
                                                        <?= (int) $screening[
                                                            "depression_score"
                                                        ]; ?>
                                                    </strong>

                                                    <span
                                                        class="admin-severity-badge <?= severity_class(
                                                            $screening[
                                                                "depression_level"
                                                            ]
                                                        ); ?>"
                                                    >
                                                        <?= admin_escape(
                                                            $screening[
                                                                "depression_level"
                                                            ]
                                                        ); ?>
                                                    </span>

                                                </div>

                                            </td>

                                            <td>

                                                <div class="admin-screening-score-cell">

                                                    <strong>
                                                        <?= (int) $screening[
                                                            "anxiety_score"
                                                        ]; ?>
                                                    </strong>

                                                    <span
                                                        class="admin-severity-badge <?= severity_class(
                                                            $screening[
                                                                "anxiety_level"
                                                            ]
                                                        ); ?>"
                                                    >
                                                        <?= admin_escape(
                                                            $screening[
                                                                "anxiety_level"
                                                            ]
                                                        ); ?>
                                                    </span>

                                                </div>

                                            </td>

                                            <td>

                                                <div class="admin-screening-score-cell">

                                                    <strong>
                                                        <?= (int) $screening[
                                                            "stress_score"
                                                        ]; ?>
                                                    </strong>

                                                    <span
                                                        class="admin-severity-badge <?= severity_class(
                                                            $screening[
                                                                "stress_level"
                                                            ]
                                                        ); ?>"
                                                    >
                                                        <?= admin_escape(
                                                            $screening[
                                                                "stress_level"
                                                            ]
                                                        ); ?>
                                                    </span>

                                                </div>

                                            </td>

                                            <td>

                                                <?php if (
                                                    (int) $screening[
                                                        "requires_attention"
                                                    ] === 1
                                                ): ?>

                                                    <span class="admin-attention-status">
                                                        Attention
                                                    </span>

                                                <?php else: ?>

                                                    <span class="admin-stable-status">
                                                        Stable
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <span class="admin-table-date">

                                                    <?= date(
                                                        "d M Y",
                                                        strtotime(
                                                            $screening[
                                                                "screening_date"
                                                            ]
                                                        )
                                                    ); ?>

                                                    <small>

                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $screening[
                                                                    "screening_date"
                                                                ]
                                                            )
                                                        ); ?>

                                                    </small>

                                                </span>

                                            </td>

                                            <td>

                                                <a
                                                    href="student_details.php?id=<?=
                                                        (int) $screening[
                                                            "student_id"
                                                        ];
                                                    ?>"
                                                    class="admin-action-view"
                                                >
                                                    View Student
                                                </a>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>🔎</span>

                            <h3>No screening results found</h3>

                            <p>
                                Try changing or clearing the selected
                                search filters.
                            </p>

                            <a
                                href="screening_results.php"
                                class="btn btn-poly mt-3"
                            >
                                Clear Filters
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

                <?php if ($totalPages > 1): ?>

                    <nav class="admin-pagination-wrapper">

                        <ul class="pagination admin-pagination">

                            <li class="page-item <?= $currentPage <= 1
                                ? "disabled"
                                : ""; ?>">

                                <a
                                    class="page-link"
                                    href="<?= $currentPage > 1
                                        ? admin_escape(
                                            screening_page_url(
                                                $currentPage - 1
                                            )
                                        )
                                        : "#"; ?>"
                                >
                                    Previous
                                </a>

                            </li>

                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min(
                                $totalPages,
                                $currentPage + 2
                            );
                            ?>

                            <?php for (
                                $page = $startPage;
                                $page <= $endPage;
                                $page++
                            ): ?>

                                <li class="page-item <?= $page === $currentPage
                                    ? "active"
                                    : ""; ?>">

                                    <a
                                        class="page-link"
                                        href="<?= admin_escape(
                                            screening_page_url($page)
                                        ); ?>"
                                    >
                                        <?= $page; ?>
                                    </a>

                                </li>

                            <?php endfor; ?>

                            <li class="page-item <?= $currentPage >= $totalPages
                                ? "disabled"
                                : ""; ?>">

                                <a
                                    class="page-link"
                                    href="<?= $currentPage < $totalPages
                                        ? admin_escape(
                                            screening_page_url(
                                                $currentPage + 1
                                            )
                                        )
                                        : "#"; ?>"
                                >
                                    Next
                                </a>

                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </section>

        </main>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById(
        "adminSidebarOverlay"
    );
    const openButton = document.getElementById(
        "openAdminSidebar"
    );
    const closeButton = document.getElementById(
        "closeAdminSidebar"
    );

    function openSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add("show");
        overlay.classList.add("show");
        document.body.classList.add("admin-sidebar-open");
    }

    function closeSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove("show");
        overlay.classList.remove("show");
        document.body.classList.remove("admin-sidebar-open");
    }

    if (openButton) {
        openButton.addEventListener("click", openSidebar);
    }

    if (closeButton) {
        closeButton.addEventListener("click", closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }
});
</script>

</body>
</html>