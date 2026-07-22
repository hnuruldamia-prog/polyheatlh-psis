<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

/*
|--------------------------------------------------------------------------
| Protect admin page
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["admin_fullname"] ?? "Administrator";

/*
|--------------------------------------------------------------------------
| Escape output
|--------------------------------------------------------------------------
*/

function admin_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_student_csrf"])) {
    $_SESSION["admin_student_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["admin_student_csrf"];

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

$successMessage = $_SESSION["admin_success"] ?? "";
$errorMessage = $_SESSION["admin_error"] ?? "";

unset(
    $_SESSION["admin_success"],
    $_SESSION["admin_error"]
);

/*
|--------------------------------------------------------------------------
| Delete student
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_student"])
) {
    $submittedToken = $_POST["csrf_token"] ?? "";
    $studentId = (int) ($_POST["student_id"] ?? 0);

    if (
        empty($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $_SESSION["admin_error"] =
            "Invalid request. Please refresh the page and try again.";

        header("Location: students.php");
        exit;
    }

    if ($studentId <= 0) {
        $_SESSION["admin_error"] =
            "Invalid student account selected.";

        header("Location: students.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete related records
    |--------------------------------------------------------------------------
    | A transaction prevents partial deletion.
    |--------------------------------------------------------------------------
    */

$conn->begin_transaction();

try {

    // Delete DASS answers first
    $deleteAnswers = $conn->prepare("
        DELETE FROM dass_answers
        WHERE result_id IN (
            SELECT result_id 
            FROM dass_results
            WHERE student_id = ?
        )
    ");

    $deleteAnswers->bind_param("i", $studentId);
    $deleteAnswers->execute();


    // Delete DASS results
    $deleteResults = $conn->prepare("
        DELETE FROM dass_results
        WHERE student_id = ?
    ");

    $deleteResults->bind_param("i", $studentId);
    $deleteResults->execute();


    // Delete moods
    $deleteMoods = $conn->prepare("
        DELETE FROM moods
        WHERE student_id = ?
    ");

    $deleteMoods->bind_param("i", $studentId);
    $deleteMoods->execute();


    // Delete journals
    $deleteJournals = $conn->prepare("
        DELETE FROM journals
        WHERE student_id = ?
    ");

    $deleteJournals->bind_param("i", $studentId);
    $deleteJournals->execute();


    // Delete student
    $deleteStudent = $conn->prepare("
        DELETE FROM students
        WHERE student_id = ?
    ");

    $deleteStudent->bind_param("i", $studentId);
    $deleteStudent->execute();


    $conn->commit();

    $_SESSION["admin_success"] =
    "Student account deleted successfully.";

}
catch(Exception $e){

    $conn->rollback();

    $_SESSION["admin_error"] =
    "Student account could not be deleted.";
}


header("Location: students.php");
exit;

}

/*
|--------------------------------------------------------------------------
| Search and filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");
$semesterFilter = trim($_GET["semester"] ?? "");
$sort = $_GET["sort"] ?? "newest";

$allowedSorts = [
    "newest",
    "oldest",
    "name_asc",
    "name_desc"
];

if (!in_array($sort, $allowedSorts, true)) {
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
| Build WHERE conditions
|--------------------------------------------------------------------------
*/

$whereParts = [];
$parameterValues = [];
$parameterTypes = "";

if ($search !== "") {
    $whereParts[] = "
        (
            student_number LIKE ?
            OR fullname LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $parameterValues[] = $searchValue;
    $parameterValues[] = $searchValue;
    $parameterValues[] = $searchValue;
    $parameterValues[] = $searchValue;

    $parameterTypes .= "ssss";
}

if ($courseFilter !== "") {
    $whereParts[] = "course = ?";
    $parameterValues[] = $courseFilter;
    $parameterTypes .= "s";
}

if ($semesterFilter !== "") {
    $whereParts[] = "semester = ?";
    $parameterValues[] = $semesterFilter;
    $parameterTypes .= "s";
}

$whereSql = "";

if (!empty($whereParts)) {
    $whereSql = "WHERE " . implode(" AND ", $whereParts);
}

/*
|--------------------------------------------------------------------------
| Sort order
|--------------------------------------------------------------------------
*/

$orderSql = "s.created_at DESC";

switch ($sort) {
    case "oldest":
        $orderSql = "s.created_at ASC";
        break;

    case "name_asc":
        $orderSql = "s.fullname ASC";
        break;

    case "name_desc":
        $orderSql = "s.fullname DESC";
        break;
}

/*
|--------------------------------------------------------------------------
| Count filtered students
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total
    FROM students
    {$whereSql}
";

$countStatement = $conn->prepare($countSql);

$totalStudents = 0;

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

    $totalStudents = (int) ($countRow["total"] ?? 0);

    $countStatement->close();
}

$totalPages = max(
    1,
    (int) ceil($totalStudents / $recordsPerPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $recordsPerPage;
}

/*
|--------------------------------------------------------------------------
| Retrieve students
|--------------------------------------------------------------------------
*/

$studentSql = "
    SELECT
        s.student_id,
        s.student_number,
        s.fullname,
        s.email,
        s.phone,
        s.course,
        s.semester,
        s.created_at,

        (
            SELECT COUNT(*)
            FROM dass_results dr
            WHERE dr.student_id = s.student_id
        ) AS screening_count,

        (
            SELECT COUNT(*)
            FROM journals j
            WHERE j.student_id = s.student_id
        ) AS journal_count,

        (
            SELECT COUNT(*)
            FROM dass_results dr2
            WHERE dr2.student_id = s.student_id
            AND dr2.requires_attention = 1
        ) AS attention_count

    FROM students s

    {$whereSql}

    ORDER BY {$orderSql}

    LIMIT ? OFFSET ?
";

$studentStatement = $conn->prepare($studentSql);

$students = [];

if ($studentStatement) {
    $studentParameterValues = $parameterValues;
    $studentParameterValues[] = $recordsPerPage;
    $studentParameterValues[] = $offset;

    $studentParameterTypes = $parameterTypes . "ii";

    $studentStatement->bind_param(
        $studentParameterTypes,
        ...$studentParameterValues
    );

    $studentStatement->execute();

    $studentResult = $studentStatement->get_result();

    while ($student = $studentResult->fetch_assoc()) {
        $students[] = $student;
    }

    $studentStatement->close();
}

/*
|--------------------------------------------------------------------------
| Course filter values
|--------------------------------------------------------------------------
*/

$courses = [];

$courseResult = $conn->query(
    "SELECT DISTINCT course
     FROM students
     WHERE course IS NOT NULL
     AND course != ''
     ORDER BY course ASC"
);

if ($courseResult) {
    while ($courseRow = $courseResult->fetch_assoc()) {
        $courses[] = $courseRow["course"];
    }
}

/*
|--------------------------------------------------------------------------
| Summary statistics
|--------------------------------------------------------------------------
*/

function admin_count($conn, $query)
{
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

$allStudentCount = admin_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM students"
);

$studentsThisMonth = admin_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM students
     WHERE YEAR(created_at) = YEAR(CURDATE())
     AND MONTH(created_at) = MONTH(CURDATE())"
);

$studentsWithScreening = admin_count(
    $conn,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM dass_results"
);

$studentsRequiringAttention = admin_count(
    $conn,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM dass_results
     WHERE requires_attention = 1"
);

/*
|--------------------------------------------------------------------------
| Pagination URL helper
|--------------------------------------------------------------------------
*/

function student_page_url($page)
{
    $parameters = $_GET;
    $parameters["page"] = $page;

    return "students.php?" . http_build_query($parameters);
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

    <title>Manage Students | Poly-Health</title>

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

    <!-- Sidebar -->
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

                <small>
                    System Administrator
                </small>

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
                class="admin-nav-link active"
            >
                <span>🎓</span>
                Manage Students
            </a>

            <a
                href="screening_results.php"
                class="admin-nav-link"
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
    href="profile.php"
    class="admin-nav-link"
>
    <span>👤</span>
    My Profile
</a>

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
                Student information must be handled confidentially.
            </p>

        </div>

    </aside>

    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>

    <!-- Main content -->
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

                <strong>Student Management</strong>

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
                        Student Accounts
                    </span>

                    <h1>Manage Students</h1>

                    <p>
                        Search, review and manage registered Poly-Health
                        student accounts.
                    </p>

                </div>

                <a
                    href="dashboard.php"
                    class="btn admin-secondary-button"
                >
                    ← Dashboard
                </a>

            </section>

            <?php if ($successMessage !== ""): ?>

                <div
                    class="alert alert-success admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($successMessage); ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>

                <div
                    class="alert alert-danger admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($errorMessage); ?>
                </div>

            <?php endif; ?>

            <!-- Statistics -->
            <section class="admin-student-stat-grid">

                <article class="admin-student-stat-card">

                    <span>🎓</span>

                    <div>

                        <small>Total Students</small>

                        <strong>
                            <?= number_format($allStudentCount); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>🆕</span>

                    <div>

                        <small>Registered This Month</small>

                        <strong>
                            <?= number_format($studentsThisMonth); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card">

                    <span>📋</span>

                    <div>

                        <small>Completed Screening</small>

                        <strong>
                            <?= number_format($studentsWithScreening); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-student-stat-card attention">

                    <span>⚠️</span>

                    <div>

                        <small>Require Attention</small>

                        <strong>
                            <?= number_format(
                                $studentsRequiringAttention
                            ); ?>
                        </strong>

                    </div>

                </article>

            </section>

            <!-- Search filters -->
            <section class="admin-filter-card">

                <form
                    method="GET"
                    action="students.php"
                    class="admin-filter-form"
                >

                    <div class="admin-filter-search">

                        <label for="search">
                            Search student
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="search"
                            name="search"
                            value="<?= admin_escape($search); ?>"
                            placeholder="Name, student number, email or phone"
                        >

                    </div>

                    <div>

                        <label for="course">
                            Course
                        </label>

                        <select
                            class="form-select"
                            id="course"
                            name="course"
                        >

                            <option value="">
                                All courses
                            </option>

                            <?php foreach ($courses as $course): ?>

                                <option
                                    value="<?= admin_escape($course); ?>"
                                    <?= $courseFilter === $course
                                        ? "selected"
                                        : ""; ?>
                                >
                                    <?= admin_escape($course); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div>

                        <label for="semester">
                            Semester
                        </label>

                        <select
                            class="form-select"
                            id="semester"
                            name="semester"
                        >

                            <option value="">
                                All semesters
                            </option>

                            <?php for ($number = 1; $number <= 8; $number++): ?>

                                <?php
                                $semesterValue =
                                    "Semester " . $number;
                                ?>

                                <option
                                    value="<?= admin_escape(
                                        $semesterValue
                                    ); ?>"
                                    <?= $semesterFilter === $semesterValue
                                        ? "selected"
                                        : ""; ?>
                                >
                                    <?= admin_escape($semesterValue); ?>
                                </option>

                            <?php endfor; ?>

                        </select>

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
                                value="name_asc"
                                <?= $sort === "name_asc"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Name A–Z
                            </option>

                            <option
                                value="name_desc"
                                <?= $sort === "name_desc"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Name Z–A
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
                            href="students.php"
                            class="btn admin-filter-reset"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>

            <!-- Student table -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Student Directory
                        </span>

                        <h2>
                            Registered Students
                        </h2>

                    </div>

                    <span class="admin-record-count">
                        <?= number_format($totalStudents); ?>
                        result<?= $totalStudents === 1 ? "" : "s"; ?>
                    </span>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($students)): ?>

                        <div class="table-responsive">

                            <table class="table admin-students-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Contact</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Activity</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($students as $student): ?>

                                        <tr>

                                            <td>

                                                <div class="admin-table-student">

                                                    <div class="admin-table-avatar">

                                                        <?= admin_escape(
                                                            strtoupper(
                                                                substr(
                                                                    trim(
                                                                        $student["fullname"]
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
                                                                $student["fullname"]
                                                            ); ?>
                                                        </strong>

                                                        <small>
                                                            <?= admin_escape(
                                                                $student["student_number"]
                                                            ); ?>
                                                        </small>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>

                                                <div class="admin-contact-cell">

                                                    <strong>
                                                        <?= admin_escape(
                                                            $student["email"]
                                                        ); ?>
                                                    </strong>

                                                    <small>
                                                        <?= !empty(
                                                            $student["phone"]
                                                        )
                                                            ? admin_escape(
                                                                $student["phone"]
                                                            )
                                                            : "No phone number"; ?>
                                                    </small>

                                                </div>

                                            </td>

                                            <td>

                                                <?= !empty($student["course"])
                                                    ? admin_escape(
                                                        $student["course"]
                                                    )
                                                    : "Not provided"; ?>

                                            </td>

                                            <td>

                                                <?= !empty(
                                                    $student["semester"]
                                                )
                                                    ? admin_escape(
                                                        $student["semester"]
                                                    )
                                                    : "Not provided"; ?>

                                            </td>

                                            <td>

                                                <div class="admin-activity-cell">

                                                    <span>
                                                        📋
                                                        <?= (int) $student[
                                                            "screening_count"
                                                        ]; ?>
                                                    </span>

                                                    <span>
                                                        📖
                                                        <?= (int) $student[
                                                            "journal_count"
                                                        ]; ?>
                                                    </span>

                                                </div>

                                            </td>

                                            <td>

                                                <?php if (
                                                    (int) $student[
                                                        "attention_count"
                                                    ] > 0
                                                ): ?>

                                                    <span class="admin-attention-status">
                                                        Attention
                                                    </span>

                                                <?php elseif (
                                                    (int) $student[
                                                        "screening_count"
                                                    ] > 0
                                                ): ?>

                                                    <span class="admin-stable-status">
                                                        Screened
                                                    </span>

                                                <?php else: ?>

                                                    <span class="admin-neutral-status">
                                                        Not screened
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <span class="admin-table-date">

                                                    <?= date(
                                                        "d M Y",
                                                        strtotime(
                                                            $student["created_at"]
                                                        )
                                                    ); ?>

                                                    <small>

                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $student["created_at"]
                                                            )
                                                        ); ?>

                                                    </small>

                                                </span>

                                            </td>

                                            <td>

                                                <div class="admin-table-actions">

                                                    <a
                                                        href="student_details.php?id=<?=
                                                            (int) $student[
                                                                "student_id"
                                                            ];
                                                        ?>"
                                                        class="admin-action-view"
                                                    >
                                                        View
                                                    </a>

                                                    <button
                                                        type="button"
                                                        class="admin-action-delete"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteStudentModal"
                                                        data-student-id="<?=
                                                            (int) $student[
                                                                "student_id"
                                                            ];
                                                        ?>"
                                                        data-student-name="<?=
                                                            admin_escape(
                                                                $student[
                                                                    "fullname"
                                                                ]
                                                            );
                                                        ?>"
                                                    >
                                                        Delete
                                                    </button>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>🔎</span>

                            <h3>No students found</h3>

                            <p>
                                Try changing the search term or clearing
                                the selected filters.
                            </p>

                            <a
                                href="students.php"
                                class="btn btn-poly mt-3"
                            >
                                Clear Filters
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- Pagination -->
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
                                            student_page_url(
                                                $currentPage - 1
                                            )
                                        )
                                        : "#"; ?>"
                                >
                                    Previous
                                </a>

                            </li>

                            <?php
                            $startPage = max(
                                1,
                                $currentPage - 2
                            );

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
                                            student_page_url($page)
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
                                            student_page_url(
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

<!-- Delete confirmation modal -->
<div
    class="modal fade"
    id="deleteStudentModal"
    tabindex="-1"
    aria-labelledby="deleteStudentModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content admin-delete-modal">

            <div class="modal-header">

                <h2
                    class="modal-title fs-5"
                    id="deleteStudentModalLabel"
                >
                    Delete Student Account
                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <form
                method="POST"
                action="students.php"
            >

                <div class="modal-body">

                    <div class="admin-delete-warning-icon">
                        ⚠️
                    </div>

                    <p>
                        Are you sure you want to permanently delete
                        <strong id="deleteStudentName">
                            this student
                        </strong>?
                    </p>

                    <div class="alert alert-danger">

                        This will also delete the student's moods,
                        screenings, DASS answers and journal records.
                        This action cannot be undone.

                    </div>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= admin_escape($csrfToken); ?>"
                    >

                    <input
                        type="hidden"
                        name="student_id"
                        id="deleteStudentId"
                        value=""
                    >

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        name="delete_student"
                        class="btn btn-danger"
                    >
                        Delete Permanently
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById("adminSidebarOverlay");
    const openButton = document.getElementById("openAdminSidebar");
    const closeButton = document.getElementById("closeAdminSidebar");

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

    const deleteModal =
        document.getElementById("deleteStudentModal");

    if (deleteModal) {
        deleteModal.addEventListener(
            "show.bs.modal",
            function (event) {
                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                const studentId =
                    button.getAttribute("data-student-id");

                const studentName =
                    button.getAttribute("data-student-name");

                const idInput =
                    document.getElementById("deleteStudentId");

                const nameElement =
                    document.getElementById("deleteStudentName");

                if (idInput) {
                    idInput.value = studentId;
                }

                if (nameElement) {
                    nameElement.textContent = studentName;
                }
            }
        );
    }
});
</script>

</body>
</html>