<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
| Get a single count from the database
|--------------------------------------------------------------------------
*/

function get_dashboard_count($conn, $query)
{
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return isset($row["total"])
        ? (int) $row["total"]
        : 0;
}

/*
|--------------------------------------------------------------------------
| Dashboard statistics
|--------------------------------------------------------------------------
*/

$totalStudents = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM students"
);

$totalScreenings = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results"
);

$attentionScreenings = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE requires_attention = 1"
);

$totalJournals = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM journals"
);

/*
|--------------------------------------------------------------------------
| Monthly Screening Statistics
|--------------------------------------------------------------------------
*/

$monthlyLabels = [];
$monthlyTotals = [];

$chartQuery = "
SELECT
    MONTH(screening_date) AS month_no,
    MONTHNAME(screening_date) AS month_name,
    COUNT(*) AS total
FROM dass_results
GROUP BY MONTH(screening_date)
ORDER BY MONTH(screening_date)
";

$result = $conn->query($chartQuery);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyLabels[] = substr($row["month_name"], 0, 3);
        $monthlyTotals[] = (int)$row["total"];
    }
}
/*
|--------------------------------------------------------------------------
| Screenings completed today
|--------------------------------------------------------------------------
*/

$todayScreenings = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE DATE(screening_date) = CURDATE()"
);

/*
|--------------------------------------------------------------------------
| Students registered this month
|--------------------------------------------------------------------------
*/

$newStudentsThisMonth = get_dashboard_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM students
     WHERE YEAR(created_at) = YEAR(CURDATE())
     AND MONTH(created_at) = MONTH(CURDATE())"
);

/*
|--------------------------------------------------------------------------
| Latest DASS-21 screening results
|--------------------------------------------------------------------------
*/

$latestScreenings = [];

$screeningQuery = "
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
        s.student_number,
        s.fullname
    FROM dass_results dr
    INNER JOIN students s
        ON s.student_id = dr.student_id
    ORDER BY dr.screening_date DESC
    LIMIT 6
";

$screeningResult = $conn->query($screeningQuery);

if ($screeningResult) {
    while ($row = $screeningResult->fetch_assoc()) {
        $latestScreenings[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Latest registered students
|--------------------------------------------------------------------------
*/

$latestStudents = [];

$studentQuery = "
    SELECT
        student_id,
        student_number,
        fullname,
        email,
        course,
        semester,
        created_at
    FROM students
    ORDER BY created_at DESC
    LIMIT 6
";

$studentResult = $conn->query($studentQuery);

if ($studentResult) {
    while ($row = $studentResult->fetch_assoc()) {
        $latestStudents[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| Severity badge class
|--------------------------------------------------------------------------
*/

function severity_class($level)
{
    $normalisedLevel = strtolower(trim((string) $level));

    if ($normalisedLevel === "normal") {
        return "severity-normal";
    }

    if ($normalisedLevel === "mild") {
        return "severity-mild";
    }

    if ($normalisedLevel === "moderate") {
        return "severity-moderate";
    }

    if (
        $normalisedLevel === "severe" ||
        $normalisedLevel === "extremely severe"
    ) {
        return "severity-severe";
    }

    return "severity-default";
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

    <title>Admin Dashboard | Poly-Health</title>

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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

                <span>
                    POLY-HEALTH
                </span>
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
                class="admin-nav-link active"
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
                Authorised administrators only.
            </p>

        </div>

    </aside>

    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>

    <!-- Main content -->
    <div class="admin-dashboard-main">

        <!-- Top navigation -->
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

                <strong>
                    Poly-Health Management System
                </strong>

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

                    <small>
                        Administrator
                    </small>

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

            <!-- Welcome -->
            <section class="admin-welcome-section">

                <div>

                    <span class="admin-section-label">
                        Dashboard Overview
                    </span>

                    <h1>
                        Welcome back,
                        <?= admin_escape($adminName); ?>.
                    </h1>

                    <p>
                        Monitor student wellbeing, review DASS-21
                        screenings and manage Poly-Health records.
                    </p>

                </div>

                <div class="admin-current-date">

                    <span>📅</span>

                    <div>

                        <small>Today</small>

                        <strong>
                            <?= date("d F Y"); ?>
                        </strong>

                    </div>

                </div>

            </section>

            <!-- Main statistics -->
            <section class="admin-stat-grid">

                <article class="admin-stat-card">

                    <div class="admin-stat-icon">
                        🎓
                    </div>

                    <div class="admin-stat-content">

                        <span>Total Students</span>

                        <strong>
                            <?= number_format($totalStudents); ?>
                        </strong>

                        <small>
                            Registered student accounts
                        </small>

                    </div>

                    <a href="students.php">
                        View students →
                    </a>

                </article>

                <article class="admin-stat-card">

                    <div class="admin-stat-icon">
                        📋
                    </div>

                    <div class="admin-stat-content">

                        <span>Total Screenings</span>

                        <strong>
                            <?= number_format($totalScreenings); ?>
                        </strong>

                        <small>
                            Completed DASS-21 screenings
                        </small>

                    </div>

                    <a href="screening_results.php">
                        View results →
                    </a>

                </article>

                <article class="admin-stat-card admin-stat-attention">

                    <div class="admin-stat-icon">
                        ⚠️
                    </div>

                    <div class="admin-stat-content">

                        <span>Require Attention</span>

                        <strong>
                            <?= number_format($attentionScreenings); ?>
                        </strong>

                        <small>
                            Screenings marked for attention
                        </small>

                    </div>

                    <a href="screening_results.php?attention=1">
                        Review now →
                    </a>

                </article>

                <article class="admin-stat-card">

                    <div class="admin-stat-icon">
                        📖
                    </div>

                    <div class="admin-stat-content">

                        <span>Journal Entries</span>

                        <strong>
                            <?= number_format($totalJournals); ?>
                        </strong>

                        <small>
                            Student journal records
                        </small>

                    </div>

                    <a href="journals.php">
                        View journals →
                    </a>

                </article>

            </section>

            <!-- Secondary statistics -->
            <section class="admin-secondary-grid">

                <article class="admin-secondary-card">

                    <span class="admin-secondary-icon">
                        ✅
                    </span>

                    <div>

                        <small>
                            Screenings Today
                        </small>

                        <strong>
                            <?= number_format($todayScreenings); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-secondary-card">

                    <span class="admin-secondary-icon">
                        👤
                    </span>

                    <div>

                        <small>
                            New Students This Month
                        </small>

                        <strong>
                            <?= number_format($newStudentsThisMonth); ?>
                        </strong>

                    </div>

                </article>

                <article class="admin-secondary-card">

                    <span class="admin-secondary-icon">
                        📌
                    </span>

                    <div>

                        <small>
                            Attention Percentage
                        </small>

                        <strong>
                            <?php
                            if ($totalScreenings > 0) {
                                echo number_format(
                                    (
                                        $attentionScreenings /
                                        $totalScreenings
                                    ) * 100,
                                    1
                                );
                            } else {
                                echo "0.0";
                            }
                            ?>%
                        </strong>

                    </div>

                </article>

            </section>

            <section class="admin-dashboard-section">

    <div class="admin-dashboard-heading">

        <div>

            <span class="admin-section-label">
                Analytics
            </span>

            <h2>
                Monthly DASS-21 Screenings
            </h2>

        </div>

    </div>

    <div class="admin-chart-card">

        <canvas id="monthlyChart"></canvas>

    </div>

</section>

            <!-- Quick actions -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Shortcuts
                        </span>

                        <h2>Quick Actions</h2>

                    </div>

                </div>

                <div class="admin-quick-action-grid">

                    <a
                        href="students.php"
                        class="admin-quick-action-card"
                    >

                        <span>🎓</span>

                        <div>

                            <h3>Manage Students</h3>

                            <p>
                                View student profiles and registration
                                information.
                            </p>

                        </div>

                    </a>

                    <a
                        href="screening_results.php"
                        class="admin-quick-action-card"
                    >

                        <span>📊</span>

                        <div>

                            <h3>DASS-21 Results</h3>

                            <p>
                                Review depression, anxiety and stress
                                screening scores.
                            </p>

                        </div>

                    </a>

                    <a
                        href="journals.php"
                        class="admin-quick-action-card"
                    >

                        <span>📖</span>

                        <div>

                            <h3>Journal Records</h3>

                            <p>
                                Access permitted student journal
                                information.
                            </p>

                        </div>

                    </a>

                    <a
                        href="reports.php"
                        class="admin-quick-action-card"
                    >

                        <span>📈</span>

                        <div>

                            <h3>Reports</h3>

                            <p>
                                View system statistics and wellbeing
                                trends.
                            </p>

                        </div>

                    </a>

                </div>

            </section>

            <!-- Latest screening results -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Recent Activity
                        </span>

                        <h2>Latest DASS-21 Screenings</h2>

                    </div>

                    <a
                        href="screening_results.php"
                        class="admin-heading-link"
                    >
                        View all results
                    </a>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($latestScreenings)): ?>

                        <div class="table-responsive">

                            <table class="table admin-dashboard-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Depression</th>
                                        <th>Anxiety</th>
                                        <th>Stress</th>
                                        <th>Status</th>
                                        <th>Date</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($latestScreenings as $screening): ?>

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
    <a
        href="student_details.php?id=<?= (int) $screening["student_id"]; ?>"
        class="text-decoration-none"
    >
        <?= admin_escape($screening["fullname"]); ?>
    </a>
</strong>

                                                        <small>
                                                            <?= admin_escape(
                                                                $screening["student_number"]
                                                            ); ?>
                                                        </small>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $screening["depression_level"]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $screening["depression_level"]
                                                    ); ?>

                                                    <small>
                                                        <?= (int) $screening["depression_score"]; ?>
                                                    </small>
                                                </span>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $screening["anxiety_level"]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $screening["anxiety_level"]
                                                    ); ?>

                                                    <small>
                                                        <?= (int) $screening["anxiety_score"]; ?>
                                                    </small>
                                                </span>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $screening["stress_level"]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $screening["stress_level"]
                                                    ); ?>

                                                    <small>
                                                        <?= (int) $screening["stress_score"]; ?>
                                                    </small>
                                                </span>

                                            </td>

                                            <td>

                                                <?php if ((int) $screening["requires_attention"] === 1): ?>

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
                                                            $screening["screening_date"]
                                                        )
                                                    ); ?>

                                                    <small>

                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $screening["screening_date"]
                                                            )
                                                        ); ?>

                                                    </small>

                                                </span>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>📊</span>

                            <h3>No screening results yet</h3>

                            <p>
                                Student DASS-21 results will appear here
                                after screenings are completed.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <!-- Latest students -->
             <section class="admin-dashboard-two-column">

 

</section>
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Student Accounts
                        </span>

                        <h2>Latest Registrations</h2>

                    </div>

                    <a
                        href="students.php"
                        class="admin-heading-link"
                    >
                        Manage students
                    </a>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($latestStudents)): ?>

                        <div class="table-responsive">

                            <table class="table admin-dashboard-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Semester</th>
                                        <th>Registered</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach ($latestStudents as $student): ?>

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
    <a
        href="student_details.php?id=<?= (int) $student["student_id"]; ?>"
        class="text-decoration-none"
    >
        <?= admin_escape($student["fullname"]); ?>
    </a>
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
                                                <?= admin_escape(
                                                    $student["email"]
                                                ); ?>
                                            </td>

                                            <td>

                                                <?= !empty($student["course"])
                                                    ? admin_escape(
                                                        $student["course"]
                                                    )
                                                    : "Not provided"; ?>

                                            </td>

                                            <td>

                                                <?= !empty($student["semester"])
                                                    ? admin_escape(
                                                        $student["semester"]
                                                    )
                                                    : "Not provided"; ?>

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

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>🎓</span>

                            <h3>No registered students</h3>

                            <p>
                                New student accounts will appear here.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <footer class="admin-dashboard-footer">

                <p>
                    Poly-Health Smart Mental Health Screening and Support
                </p>

                <span>
                    Administrator Portal
                </span>

            </footer>

        </main>

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

    window.addEventListener("resize", function () {
        if (window.innerWidth > 991) {
            closeSidebar();
        }
    });
});
</script>

<script>

const monthlyChart = document.getElementById("monthlyChart");

new Chart(monthlyChart, {

    type: "line",

    data: {

        labels: <?= json_encode($monthlyLabels); ?>,

        datasets: [{

            label: "Screenings",

            data: <?= json_encode($monthlyTotals); ?>,

            borderWidth: 3,

            tension: 0.4,

            fill: true

        }]

    },

    options: {

        responsive: true,

        plugins: {

            legend: {

                display: false

            }

        }

    }

});

</script>

</body>
</html>