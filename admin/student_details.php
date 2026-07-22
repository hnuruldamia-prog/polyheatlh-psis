<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["admin_fullname"] ?? "Administrator";
$studentId = (int) ($_GET["id"] ?? 0);

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

function format_admin_date($date, $format = "d M Y")
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return "Not available";
    }

    return date($format, $timestamp);
}

if ($studentId <= 0) {
    header("Location: students.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Student information
|--------------------------------------------------------------------------
*/

$studentStatement = $conn->prepare(
    "SELECT
        student_id,
        student_number,
        fullname,
        email,
        phone,
        course,
        semester,
        created_at
     FROM students
     WHERE student_id = ?
     LIMIT 1"
);

if (!$studentStatement) {
    die("Unable to retrieve student information.");
}

$studentStatement->bind_param("i", $studentId);
$studentStatement->execute();

$studentResult = $studentStatement->get_result();
$student = $studentResult->fetch_assoc();

$studentStatement->close();

if (!$student) {
    header("Location: students.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Student statistics
|--------------------------------------------------------------------------
*/

function get_student_count($conn, $table, $studentId)
{
    $allowedTables = [
        "dass_results",
        "journals",
        "moods"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $statement = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM {$table}
         WHERE student_id = ?"
    );

    if (!$statement) {
        return 0;
    }

    $statement->bind_param("i", $studentId);
    $statement->execute();

    $result = $statement->get_result();
    $row = $result->fetch_assoc();

    $statement->close();

    return (int) ($row["total"] ?? 0);
}

$totalScreenings = get_student_count(
    $conn,
    "dass_results",
    $studentId
);

$totalJournals = get_student_count(
    $conn,
    "journals",
    $studentId
);

$totalMoods = get_student_count(
    $conn,
    "moods",
    $studentId
);

/*
|--------------------------------------------------------------------------
| Results requiring attention
|--------------------------------------------------------------------------
*/

$attentionCount = 0;

$attentionStatement = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE student_id = ?
     AND requires_attention = 1"
);

if ($attentionStatement) {
    $attentionStatement->bind_param("i", $studentId);
    $attentionStatement->execute();

    $attentionResult = $attentionStatement->get_result();
    $attentionRow = $attentionResult->fetch_assoc();

    $attentionCount = (int) ($attentionRow["total"] ?? 0);

    $attentionStatement->close();
}

/*
|--------------------------------------------------------------------------
| Latest DASS-21 result
|--------------------------------------------------------------------------
*/

$latestScreening = null;

$latestStatement = $conn->prepare(
    "SELECT
        depression_score,
        anxiety_score,
        stress_score,
        depression_level,
        anxiety_level,
        stress_level,
        requires_attention,
        screening_date
     FROM dass_results
     WHERE student_id = ?
     ORDER BY screening_date DESC
     LIMIT 1"
);

if ($latestStatement) {
    $latestStatement->bind_param("i", $studentId);
    $latestStatement->execute();

    $latestResult = $latestStatement->get_result();
    $latestScreening = $latestResult->fetch_assoc();

    $latestStatement->close();
}

/*
|--------------------------------------------------------------------------
| Screening history
|--------------------------------------------------------------------------
*/

$screeningHistory = [];

$historyStatement = $conn->prepare(
    "SELECT
        depression_score,
        anxiety_score,
        stress_score,
        depression_level,
        anxiety_level,
        stress_level,
        requires_attention,
        screening_date
     FROM dass_results
     WHERE student_id = ?
     ORDER BY screening_date DESC"
);

if ($historyStatement) {
    $historyStatement->bind_param("i", $studentId);
    $historyStatement->execute();

    $historyResult = $historyStatement->get_result();

    while ($row = $historyResult->fetch_assoc()) {
        $screeningHistory[] = $row;
    }

    $historyStatement->close();
}

$studentInitial = strtoupper(
    substr(trim($student["fullname"]), 0, 1)
);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= admin_escape($student["fullname"]); ?>
        | Poly-Health
    </title>

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
                href="../index.php"
                class="admin-nav-link"
                target="_blank"
            >
                <span>🌐</span>
                View Website
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

                <strong>Student Details</strong>

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
                        Student Profile
                    </span>

                    <h1>Student Details</h1>

                    <p>
                        Review the student's account and DASS-21
                        screening activity.
                    </p>

                </div>

                <a
                    href="students.php"
                    class="btn admin-secondary-button"
                >
                    ← Back to Students
                </a>

            </section>

            <!-- Student profile -->
            <section class="admin-student-profile-card">

                <div class="admin-student-profile-main">

                    <div class="admin-student-profile-avatar">
                        <?= admin_escape($studentInitial); ?>
                    </div>

                    <div>

                        <span class="admin-profile-label">
                            Registered Student
                        </span>

                        <h2>
                            <?= admin_escape(
                                $student["fullname"]
                            ); ?>
                        </h2>

                        <p>
                            <?= admin_escape(
                                $student["student_number"]
                            ); ?>
                        </p>

                    </div>

                </div>

                <div class="admin-profile-status-area">

                    <?php if ($attentionCount > 0): ?>

                        <span class="admin-profile-attention">
                            ⚠ Requires Attention
                        </span>

                    <?php elseif ($totalScreenings > 0): ?>

                        <span class="admin-profile-screened">
                            ✓ Screening Completed
                        </span>

                    <?php else: ?>

                        <span class="admin-profile-neutral">
                            No Screening Yet
                        </span>

                    <?php endif; ?>

                    <small>
                        Joined
                        <?= format_admin_date(
                            $student["created_at"]
                        ); ?>
                    </small>

                </div>

            </section>

            <!-- Student information -->
            <section class="admin-details-grid">

                <article class="admin-details-card">

                    <div class="admin-details-card-header">

                        <span>👤</span>

                        <div>

                            <small>Student Record</small>

                            <h2>Personal Information</h2>

                        </div>

                    </div>

                    <div class="admin-information-list">

                        <div class="admin-information-item">

                            <span>Full Name</span>

                            <strong>
                                <?= admin_escape(
                                    $student["fullname"]
                                ); ?>
                            </strong>

                        </div>

                        <div class="admin-information-item">

                            <span>Student Number</span>

                            <strong>
                                <?= admin_escape(
                                    $student["student_number"]
                                ); ?>
                            </strong>

                        </div>

                        <div class="admin-information-item">

                            <span>Email Address</span>

                            <strong>
                                <?= admin_escape(
                                    $student["email"]
                                ); ?>
                            </strong>

                        </div>

                        <div class="admin-information-item">

                            <span>Phone Number</span>

                            <strong>
                                <?= !empty($student["phone"])
                                    ? admin_escape(
                                        $student["phone"]
                                    )
                                    : "Not provided"; ?>
                            </strong>

                        </div>

                        <div class="admin-information-item">

                            <span>Course</span>

                            <strong>
                                <?= !empty($student["course"])
                                    ? admin_escape(
                                        $student["course"]
                                    )
                                    : "Not provided"; ?>
                            </strong>

                        </div>

                        <div class="admin-information-item">

                            <span>Semester</span>

                            <strong>
                                <?= !empty($student["semester"])
                                    ? admin_escape(
                                        $student["semester"]
                                    )
                                    : "Not provided"; ?>
                            </strong>

                        </div>

                    </div>

                </article>

                <!-- Activity summary -->
                <article class="admin-details-card">

                    <div class="admin-details-card-header">

                        <span>📊</span>

                        <div>

                            <small>Account Activity</small>

                            <h2>Student Summary</h2>

                        </div>

                    </div>

                    <div class="admin-profile-stat-grid">

                        <div class="admin-profile-stat">

                            <span>📋</span>

                            <strong>
                                <?= number_format(
                                    $totalScreenings
                                ); ?>
                            </strong>

                            <small>
                                DASS-21 Screenings
                            </small>

                        </div>

                        <div class="admin-profile-stat">

                            <span>📖</span>

                            <strong>
                                <?= number_format(
                                    $totalJournals
                                ); ?>
                            </strong>

                            <small>
                                Journal Entries
                            </small>

                        </div>

                        <div class="admin-profile-stat">

                            <span>😊</span>

                            <strong>
                                <?= number_format(
                                    $totalMoods
                                ); ?>
                            </strong>

                            <small>
                                Mood Records
                            </small>

                        </div>

                        <div class="admin-profile-stat attention">

                            <span>⚠️</span>

                            <strong>
                                <?= number_format(
                                    $attentionCount
                                ); ?>
                            </strong>

                            <small>
                                Attention Results
                            </small>

                        </div>

                    </div>

                    <div class="admin-confidential-note">

                        <span>🔒</span>

                        <p>
                            This information is confidential and should
                            only be used for authorised student wellbeing
                            support.
                        </p>

                    </div>

                </article>

            </section>

            <!-- Latest screening -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Latest Assessment
                        </span>

                        <h2>Latest DASS-21 Result</h2>

                    </div>

                </div>

                <?php if ($latestScreening): ?>

                    <div class="admin-latest-screening-card">

                        <div class="admin-latest-screening-header">

                            <div>

                                <span>Screening Date</span>

                                <strong>
                                    <?= format_admin_date(
                                        $latestScreening[
                                            "screening_date"
                                        ],
                                        "d F Y, h:i A"
                                    ); ?>
                                </strong>

                            </div>

                            <?php if (
                                (int) $latestScreening[
                                    "requires_attention"
                                ] === 1
                            ): ?>

                                <span class="admin-attention-status">
                                    Requires Attention
                                </span>

                            <?php else: ?>

                                <span class="admin-stable-status">
                                    Stable
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="admin-dass-result-grid">

                            <article class="admin-dass-result-card">

                                <span class="admin-dass-title">
                                    Depression
                                </span>

                                <strong>
                                    <?= (int) $latestScreening[
                                        "depression_score"
                                    ]; ?>
                                </strong>

                                <span
                                    class="admin-severity-badge <?= severity_class(
                                        $latestScreening[
                                            "depression_level"
                                        ]
                                    ); ?>"
                                >
                                    <?= admin_escape(
                                        $latestScreening[
                                            "depression_level"
                                        ]
                                    ); ?>
                                </span>

                            </article>

                            <article class="admin-dass-result-card">

                                <span class="admin-dass-title">
                                    Anxiety
                                </span>

                                <strong>
                                    <?= (int) $latestScreening[
                                        "anxiety_score"
                                    ]; ?>
                                </strong>

                                <span
                                    class="admin-severity-badge <?= severity_class(
                                        $latestScreening[
                                            "anxiety_level"
                                        ]
                                    ); ?>"
                                >
                                    <?= admin_escape(
                                        $latestScreening[
                                            "anxiety_level"
                                        ]
                                    ); ?>
                                </span>

                            </article>

                            <article class="admin-dass-result-card">

                                <span class="admin-dass-title">
                                    Stress
                                </span>

                                <strong>
                                    <?= (int) $latestScreening[
                                        "stress_score"
                                    ]; ?>
                                </strong>

                                <span
                                    class="admin-severity-badge <?= severity_class(
                                        $latestScreening[
                                            "stress_level"
                                        ]
                                    ); ?>"
                                >
                                    <?= admin_escape(
                                        $latestScreening[
                                            "stress_level"
                                        ]
                                    ); ?>
                                </span>

                            </article>

                        </div>

                        <?php if (
                            (int) $latestScreening[
                                "requires_attention"
                            ] === 1
                        ): ?>

                            <div class="admin-screening-warning">

                                <span>⚠️</span>

                                <p>
                                    This result contains one or more
                                    elevated DASS-21 severity levels.
                                    Appropriate follow-up may be required.
                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <div class="admin-empty-state admin-table-card">

                        <span>📋</span>

                        <h3>No DASS-21 screening completed</h3>

                        <p>
                            This student has not completed a screening
                            assessment yet.
                        </p>

                    </div>

                <?php endif; ?>

            </section>

            <!-- Screening history -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Assessment History
                        </span>

                        <h2>DASS-21 Screening History</h2>

                    </div>

                    <span class="admin-record-count">
                        <?= number_format($totalScreenings); ?>
                        result<?= $totalScreenings === 1 ? "" : "s"; ?>
                    </span>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($screeningHistory)): ?>

                        <div class="table-responsive">

                            <table class="table admin-screening-history-table">

                                <thead>

                                    <tr>

                                        <th>Date</th>
                                        <th>Depression</th>
                                        <th>Anxiety</th>
                                        <th>Stress</th>
                                        <th>Status</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $screeningHistory as $screening
                                    ): ?>

                                        <tr>

                                            <td>

                                                <span class="admin-table-date">

                                                    <?= format_admin_date(
                                                        $screening[
                                                            "screening_date"
                                                        ]
                                                    ); ?>

                                                    <small>
                                                        <?= format_admin_date(
                                                            $screening[
                                                                "screening_date"
                                                            ],
                                                            "h:i A"
                                                        ); ?>
                                                    </small>

                                                </span>

                                            </td>

                                            <td>

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

                                                    <small>
                                                        <?= (int) $screening[
                                                            "depression_score"
                                                        ]; ?>
                                                    </small>
                                                </span>

                                            </td>

                                            <td>

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

                                                    <small>
                                                        <?= (int) $screening[
                                                            "anxiety_score"
                                                        ]; ?>
                                                    </small>
                                                </span>

                                            </td>

                                            <td>

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

                                                    <small>
                                                        <?= (int) $screening[
                                                            "stress_score"
                                                        ]; ?>
                                                    </small>
                                                </span>

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

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>📊</span>

                            <h3>No screening history</h3>

                            <p>
                                Completed DASS-21 screenings will appear
                                here.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

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