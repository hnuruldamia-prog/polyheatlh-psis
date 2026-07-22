<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminName = $_SESSION["admin_fullname"] ?? "Administrator";

/*
|--------------------------------------------------------------------------
| Helper functions
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

function report_count($conn, $query)
{
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

function report_percentage($value, $total)
{
    if ($total <= 0) {
        return 0;
    }

    return round(($value / $total) * 100, 1);
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

/*
|--------------------------------------------------------------------------
| Date filter
|--------------------------------------------------------------------------
*/

$period = $_GET["period"] ?? "all";

$allowedPeriods = [
    "all",
    "today",
    "7days",
    "30days",
    "this_month",
    "this_year"
];

if (!in_array($period, $allowedPeriods, true)) {
    $period = "all";
}

$dateCondition = "";

switch ($period) {
    case "today":
        $dateCondition = "
            AND DATE(screening_date) = CURDATE()
        ";
        break;

    case "7days":
        $dateCondition = "
            AND screening_date >= DATE_SUB(
                NOW(),
                INTERVAL 7 DAY
            )
        ";
        break;

    case "30days":
        $dateCondition = "
            AND screening_date >= DATE_SUB(
                NOW(),
                INTERVAL 30 DAY
            )
        ";
        break;

    case "this_month":
        $dateCondition = "
            AND MONTH(screening_date) = MONTH(CURDATE())
            AND YEAR(screening_date) = YEAR(CURDATE())
        ";
        break;

    case "this_year":
        $dateCondition = "
            AND YEAR(screening_date) = YEAR(CURDATE())
        ";
        break;
}

/*
|--------------------------------------------------------------------------
| Main statistics
|--------------------------------------------------------------------------
*/

$totalStudents = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM students"
);

$totalScreenings = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE 1 = 1
     {$dateCondition}"
);

$studentsScreened = report_count(
    $conn,
    "SELECT COUNT(DISTINCT student_id) AS total
     FROM dass_results
     WHERE 1 = 1
     {$dateCondition}"
);

$attentionResults = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE requires_attention = 1
     {$dateCondition}"
);

$totalJournals = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM journals"
);

$totalMoods = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM moods"
);

$screeningRate = report_percentage(
    $studentsScreened,
    $totalStudents
);

$attentionRate = report_percentage(
    $attentionResults,
    $totalScreenings
);

/*
|--------------------------------------------------------------------------
| Average DASS-21 scores
|--------------------------------------------------------------------------
*/

$averageScores = [
    "depression" => 0,
    "anxiety" => 0,
    "stress" => 0
];

$averageResult = $conn->query(
    "SELECT
        AVG(depression_score) AS depression_average,
        AVG(anxiety_score) AS anxiety_average,
        AVG(stress_score) AS stress_average
     FROM dass_results
     WHERE 1 = 1
     {$dateCondition}"
);

if ($averageResult) {
    $averageRow = $averageResult->fetch_assoc();

    $averageScores["depression"] = round(
        (float) ($averageRow["depression_average"] ?? 0),
        1
    );

    $averageScores["anxiety"] = round(
        (float) ($averageRow["anxiety_average"] ?? 0),
        1
    );

    $averageScores["stress"] = round(
        (float) ($averageRow["stress_average"] ?? 0),
        1
    );
}

/*
|--------------------------------------------------------------------------
| Severity distribution
|--------------------------------------------------------------------------
*/

$severityLevels = [
    "Normal",
    "Mild",
    "Moderate",
    "Severe",
    "Extremely Severe"
];

$severityData = [];

foreach ($severityLevels as $level) {
    $severityData[$level] = [
        "depression" => 0,
        "anxiety" => 0,
        "stress" => 0
    ];
}

$severityStatement = $conn->prepare(
    "SELECT
        depression_level,
        anxiety_level,
        stress_level
     FROM dass_results
     WHERE 1 = 1
     {$dateCondition}"
);

if ($severityStatement) {
    $severityStatement->execute();

    $severityResult = $severityStatement->get_result();

    while ($row = $severityResult->fetch_assoc()) {
        $depressionLevel = $row["depression_level"];
        $anxietyLevel = $row["anxiety_level"];
        $stressLevel = $row["stress_level"];

        if (isset($severityData[$depressionLevel])) {
            $severityData[$depressionLevel]["depression"]++;
        }

        if (isset($severityData[$anxietyLevel])) {
            $severityData[$anxietyLevel]["anxiety"]++;
        }

        if (isset($severityData[$stressLevel])) {
            $severityData[$stressLevel]["stress"]++;
        }
    }

    $severityStatement->close();
}

/*
|--------------------------------------------------------------------------
| Monthly screening activity
|--------------------------------------------------------------------------
*/

$monthlyActivity = [];

for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
    $monthTimestamp = strtotime(
        "-{$monthOffset} months"
    );

    $monthKey = date("Y-m", $monthTimestamp);

    $monthlyActivity[$monthKey] = [
        "label" => date("M Y", $monthTimestamp),
        "total" => 0
    ];
}

$monthlyResult = $conn->query(
    "SELECT
        DATE_FORMAT(screening_date, '%Y-%m') AS month_key,
        COUNT(*) AS total
     FROM dass_results
     WHERE screening_date >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        '%Y-%m-01'
     )
     GROUP BY DATE_FORMAT(screening_date, '%Y-%m')
     ORDER BY month_key ASC"
);

if ($monthlyResult) {
    while ($monthlyRow = $monthlyResult->fetch_assoc()) {
        $monthKey = $monthlyRow["month_key"];

        if (isset($monthlyActivity[$monthKey])) {
            $monthlyActivity[$monthKey]["total"] =
                (int) $monthlyRow["total"];
        }
    }
}

$highestMonthlyTotal = 1;

foreach ($monthlyActivity as $monthData) {
    if ($monthData["total"] > $highestMonthlyTotal) {
        $highestMonthlyTotal = $monthData["total"];
    }
}

/*
|--------------------------------------------------------------------------
| Course statistics
|--------------------------------------------------------------------------
*/

$courseStatistics = [];

$courseResult = $conn->query(
    "SELECT
        COALESCE(NULLIF(TRIM(s.course), ''), 'Not specified') AS course_name,
        COUNT(DISTINCT s.student_id) AS student_total,
        COUNT(dr.student_id) AS screening_total
     FROM students s
     LEFT JOIN dass_results dr
        ON dr.student_id = s.student_id
     GROUP BY COALESCE(
        NULLIF(TRIM(s.course), ''),
        'Not specified'
     )
     ORDER BY student_total DESC
     LIMIT 8"
);

if ($courseResult) {
    while ($courseRow = $courseResult->fetch_assoc()) {
        $courseStatistics[] = $courseRow;
    }
}

/*
|--------------------------------------------------------------------------
| Recent attention results
|--------------------------------------------------------------------------
*/

$recentAttentionResults = [];

$attentionResult = $conn->query(
    "SELECT
        dr.student_id,
        dr.depression_score,
        dr.anxiety_score,
        dr.stress_score,
        dr.depression_level,
        dr.anxiety_level,
        dr.stress_level,
        dr.screening_date,
        s.fullname,
        s.student_number,
        s.course
     FROM dass_results dr
     INNER JOIN students s
        ON s.student_id = dr.student_id
     WHERE dr.requires_attention = 1
     ORDER BY dr.screening_date DESC
     LIMIT 8"
);

if ($attentionResult) {
    while ($attentionRow = $attentionResult->fetch_assoc()) {
        $recentAttentionResults[] = $attentionRow;
    }
}

/*
|--------------------------------------------------------------------------
| Most severe categories
|--------------------------------------------------------------------------
*/

$highDepression = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE depression_level IN (
        'Severe',
        'Extremely Severe'
     )
     {$dateCondition}"
);

$highAnxiety = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE anxiety_level IN (
        'Severe',
        'Extremely Severe'
     )
     {$dateCondition}"
);

$highStress = report_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM dass_results
     WHERE stress_level IN (
        'Severe',
        'Extremely Severe'
     )
     {$dateCondition}"
);

$highestRiskTotal = max(
    1,
    $highDepression,
    $highAnxiety,
    $highStress
);

$reportGeneratedAt = date("d F Y, h:i A");
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Reports & Analytics | Poly-Health</title>

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
                class="admin-nav-link active"
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
                Reports contain confidential student wellbeing data.
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

                <strong>
                    Reports & Analytics
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

            <section class="admin-page-header admin-report-page-header">

                <div>

                    <span class="admin-section-label">
                        System Overview
                    </span>

                    <h1>Reports & Analytics</h1>

                    <p>
                        Review student participation, DASS-21 outcomes
                        and mental-health support activity.
                    </p>

                </div>

                <div class="admin-report-header-actions">

                    <button
                        type="button"
                        class="btn admin-secondary-button"
                        onclick="window.print()"
                    >
                        🖨 Print Report
                    </button>

                    <a
                        href="dashboard.php"
                        class="btn btn-poly"
                    >
                        Dashboard
                    </a>

                </div>

            </section>

            <!-- Period filter -->
            <section class="admin-report-filter-card">

                <form
                    method="GET"
                    action="reports.php"
                    class="admin-report-filter-form"
                >

                    <div>

                        <label for="period">
                            Screening report period
                        </label>

                        <select
                            class="form-select"
                            id="period"
                            name="period"
                        >

                            <option
                                value="all"
                                <?= $period === "all"
                                    ? "selected"
                                    : ""; ?>
                            >
                                All time
                            </option>

                            <option
                                value="today"
                                <?= $period === "today"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Today
                            </option>

                            <option
                                value="7days"
                                <?= $period === "7days"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Last 7 days
                            </option>

                            <option
                                value="30days"
                                <?= $period === "30days"
                                    ? "selected"
                                    : ""; ?>
                            >
                                Last 30 days
                            </option>

                            <option
                                value="this_month"
                                <?= $period === "this_month"
                                    ? "selected"
                                    : ""; ?>
                            >
                                This month
                            </option>

                            <option
                                value="this_year"
                                <?= $period === "this_year"
                                    ? "selected"
                                    : ""; ?>
                            >
                                This year
                            </option>

                        </select>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-poly"
                    >
                        Generate Report
                    </button>

                </form>

                <span>
                    Generated:
                    <?= admin_escape($reportGeneratedAt); ?>
                </span>

            </section>

            <!-- Main statistics -->
            <section class="admin-report-stat-grid">

                <article class="admin-report-stat-card">

                    <div class="admin-report-stat-icon">
                        🎓
                    </div>

                    <div>

                        <small>Total Students</small>

                        <strong>
                            <?= number_format($totalStudents); ?>
                        </strong>

                        <p>Registered accounts</p>

                    </div>

                </article>

                <article class="admin-report-stat-card">

                    <div class="admin-report-stat-icon">
                        📋
                    </div>

                    <div>

                        <small>Total Screenings</small>

                        <strong>
                            <?= number_format($totalScreenings); ?>
                        </strong>

                        <p>For selected period</p>

                    </div>

                </article>

                <article class="admin-report-stat-card">

                    <div class="admin-report-stat-icon">
                        📖
                    </div>

                    <div>

                        <small>Journal Entries</small>

                        <strong>
                            <?= number_format($totalJournals); ?>
                        </strong>

                        <p>All student journals</p>

                    </div>

                </article>

                <article class="admin-report-stat-card">

                    <div class="admin-report-stat-icon">
                        😊
                    </div>

                    <div>

                        <small>Mood Records</small>

                        <strong>
                            <?= number_format($totalMoods); ?>
                        </strong>

                        <p>All mood check-ins</p>

                    </div>

                </article>

            </section>

            <!-- Rate cards -->
            <section class="admin-report-rate-grid">

                <article class="admin-report-rate-card">

                    <div class="admin-report-rate-heading">

                        <div>

                            <small>Student Participation</small>

                            <strong>
                                Screening Rate
                            </strong>

                        </div>

                        <span>
                            <?= number_format($screeningRate, 1); ?>%
                        </span>

                    </div>

                    <div class="admin-report-progress">

                        <div
                            class="admin-report-progress-bar"
                            style="width: <?= min(
                                100,
                                max(0, $screeningRate)
                            ); ?>%;"
                        ></div>

                    </div>

                    <p>
                        <?= number_format($studentsScreened); ?>
                        of
                        <?= number_format($totalStudents); ?>
                        registered students have completed at least one
                        screening in the selected period.
                    </p>

                </article>

                <article class="admin-report-rate-card attention">

                    <div class="admin-report-rate-heading">

                        <div>

                            <small>Elevated Results</small>

                            <strong>
                                Attention Rate
                            </strong>

                        </div>

                        <span>
                            <?= number_format($attentionRate, 1); ?>%
                        </span>

                    </div>

                    <div class="admin-report-progress">

                        <div
                            class="admin-report-progress-bar"
                            style="width: <?= min(
                                100,
                                max(0, $attentionRate)
                            ); ?>%;"
                        ></div>

                    </div>

                    <p>
                        <?= number_format($attentionResults); ?>
                        of
                        <?= number_format($totalScreenings); ?>
                        screening records are marked as requiring
                        attention.
                    </p>

                </article>

            </section>

            <!-- Average scores and elevated categories -->
            <section class="admin-report-two-column">

                <article class="admin-report-card">

                    <div class="admin-report-card-heading">

                        <div>

                            <span class="admin-section-label">
                                Average Assessment
                            </span>

                            <h2>Average DASS-21 Scores</h2>

                        </div>

                        <span>Selected period</span>

                    </div>

                    <div class="admin-report-average-grid">

                        <div>

                            <span>Depression</span>

                            <strong>
                                <?= number_format(
                                    $averageScores["depression"],
                                    1
                                ); ?>
                            </strong>

                        </div>

                        <div>

                            <span>Anxiety</span>

                            <strong>
                                <?= number_format(
                                    $averageScores["anxiety"],
                                    1
                                ); ?>
                            </strong>

                        </div>

                        <div>

                            <span>Stress</span>

                            <strong>
                                <?= number_format(
                                    $averageScores["stress"],
                                    1
                                ); ?>
                            </strong>

                        </div>

                    </div>

                    <div class="admin-report-disclaimer">

                        DASS-21 scores indicate symptom severity and are
                        not a clinical diagnosis.

                    </div>

                </article>

                <article class="admin-report-card">

                    <div class="admin-report-card-heading">

                        <div>

                            <span class="admin-section-label">
                                Risk Overview
                            </span>

                            <h2>Severe-Level Results</h2>

                        </div>

                    </div>

                    <div class="admin-risk-list">

                        <div class="admin-risk-item">

                            <div>

                                <span>Depression</span>

                                <strong>
                                    <?= number_format(
                                        $highDepression
                                    ); ?>
                                </strong>

                            </div>

                            <div class="admin-risk-bar">

                                <span
                                    style="width: <?= (
                                        $highDepression /
                                        $highestRiskTotal
                                    ) * 100; ?>%;"
                                ></span>

                            </div>

                        </div>

                        <div class="admin-risk-item">

                            <div>

                                <span>Anxiety</span>

                                <strong>
                                    <?= number_format(
                                        $highAnxiety
                                    ); ?>
                                </strong>

                            </div>

                            <div class="admin-risk-bar">

                                <span
                                    style="width: <?= (
                                        $highAnxiety /
                                        $highestRiskTotal
                                    ) * 100; ?>%;"
                                ></span>

                            </div>

                        </div>

                        <div class="admin-risk-item">

                            <div>

                                <span>Stress</span>

                                <strong>
                                    <?= number_format(
                                        $highStress
                                    ); ?>
                                </strong>

                            </div>

                            <div class="admin-risk-bar">

                                <span
                                    style="width: <?= (
                                        $highStress /
                                        $highestRiskTotal
                                    ) * 100; ?>%;"
                                ></span>

                            </div>

                        </div>

                    </div>

                </article>

            </section>

            <!-- Monthly activity -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Screening Trend
                        </span>

                        <h2>Monthly Screening Activity</h2>

                    </div>

                    <span class="admin-record-count">
                        Last 6 months
                    </span>

                </div>

                <div class="admin-report-card">

                    <div class="admin-monthly-chart">

                        <?php foreach (
                            $monthlyActivity as $monthData
                        ): ?>

                            <?php

                            $barHeight = (
                                $monthData["total"] /
                                $highestMonthlyTotal
                            ) * 100;

                            ?>

                            <div class="admin-monthly-column">

                                <div class="admin-monthly-value">
                                    <?= number_format(
                                        $monthData["total"]
                                    ); ?>
                                </div>

                                <div class="admin-monthly-track">

                                    <div
                                        class="admin-monthly-bar"
                                        style="height: <?= max(
                                            3,
                                            $barHeight
                                        ); ?>%;"
                                    ></div>

                                </div>

                                <span>
                                    <?= admin_escape(
                                        $monthData["label"]
                                    ); ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </section>

            <!-- Severity distribution -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Severity Analysis
                        </span>

                        <h2>DASS-21 Severity Distribution</h2>

                    </div>

                </div>

                <div class="admin-table-card">

                    <div class="table-responsive">

                        <table class="table admin-report-severity-table">

                            <thead>

                                <tr>

                                    <th>Severity Level</th>
                                    <th>Depression</th>
                                    <th>Anxiety</th>
                                    <th>Stress</th>
                                    <th>Total</th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $severityData as $level => $values
                                ): ?>

                                    <?php

                                    $severityTotal =
                                        $values["depression"] +
                                        $values["anxiety"] +
                                        $values["stress"];

                                    ?>

                                    <tr>

                                        <td>

                                            <span
                                                class="admin-severity-badge <?= severity_class(
                                                    $level
                                                ); ?>"
                                            >
                                                <?= admin_escape(
                                                    $level
                                                ); ?>
                                            </span>

                                        </td>

                                        <td>
                                            <?= number_format(
                                                $values["depression"]
                                            ); ?>
                                        </td>

                                        <td>
                                            <?= number_format(
                                                $values["anxiety"]
                                            ); ?>
                                        </td>

                                        <td>
                                            <?= number_format(
                                                $values["stress"]
                                            ); ?>
                                        </td>

                                        <td>

                                            <strong>
                                                <?= number_format(
                                                    $severityTotal
                                                ); ?>
                                            </strong>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </section>

            <!-- Course engagement -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Student Participation
                        </span>

                        <h2>Engagement by Course</h2>

                    </div>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($courseStatistics)): ?>

                        <div class="table-responsive">

                            <table class="table admin-course-report-table">

                                <thead>

                                    <tr>

                                        <th>Course</th>
                                        <th>Registered Students</th>
                                        <th>Total Screenings</th>
                                        <th>Average per Student</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $courseStatistics as $course
                                    ): ?>

                                        <?php

                                        $courseAverage = 0;

                                        if (
                                            (int) $course[
                                                "student_total"
                                            ] > 0
                                        ) {
                                            $courseAverage =
                                                (int) $course[
                                                    "screening_total"
                                                ] /
                                                (int) $course[
                                                    "student_total"
                                                ];
                                        }

                                        ?>

                                        <tr>

                                            <td>

                                                <strong>
                                                    <?= admin_escape(
                                                        $course[
                                                            "course_name"
                                                        ]
                                                    ); ?>
                                                </strong>

                                            </td>

                                            <td>
                                                <?= number_format(
                                                    $course[
                                                        "student_total"
                                                    ]
                                                ); ?>
                                            </td>

                                            <td>
                                                <?= number_format(
                                                    $course[
                                                        "screening_total"
                                                    ]
                                                ); ?>
                                            </td>

                                            <td>
                                                <?= number_format(
                                                    $courseAverage,
                                                    1
                                                ); ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php else: ?>

                        <div class="admin-empty-state">

                            <span>🎓</span>

                            <h3>No course information available</h3>

                            <p>
                                Course engagement data will appear after
                                students register and complete screenings.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <!-- Recent attention results -->
            <section class="admin-dashboard-section">

                <div class="admin-dashboard-heading">

                    <div>

                        <span class="admin-section-label">
                            Follow-Up Overview
                        </span>

                        <h2>Recent Results Requiring Attention</h2>

                    </div>

                    <a
                        href="screening_results.php?attention=1"
                        class="admin-view-all-link"
                    >
                        View all →
                    </a>

                </div>

                <div class="admin-table-card">

                    <?php if (!empty($recentAttentionResults)): ?>

                        <div class="table-responsive">

                            <table class="table admin-report-attention-table">

                                <thead>

                                    <tr>

                                        <th>Student</th>
                                        <th>Depression</th>
                                        <th>Anxiety</th>
                                        <th>Stress</th>
                                        <th>Date</th>
                                        <th>Action</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php foreach (
                                        $recentAttentionResults as $result
                                    ): ?>

                                        <tr>

                                            <td>

                                                <div class="admin-table-student">

                                                    <div class="admin-table-avatar">

                                                        <?= admin_escape(
                                                            strtoupper(
                                                                substr(
                                                                    trim(
                                                                        $result[
                                                                            "fullname"
                                                                        ]
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
                                                                $result[
                                                                    "fullname"
                                                                ]
                                                            ); ?>
                                                        </strong>

                                                        <small>
                                                            <?= admin_escape(
                                                                $result[
                                                                    "student_number"
                                                                ]
                                                            ); ?>
                                                        </small>

                                                    </div>

                                                </div>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $result[
                                                            "depression_level"
                                                        ]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $result[
                                                            "depression_level"
                                                        ]
                                                    ); ?>

                                                    (
                                                    <?= (int) $result[
                                                        "depression_score"
                                                    ]; ?>
                                                    )
                                                </span>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $result[
                                                            "anxiety_level"
                                                        ]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $result[
                                                            "anxiety_level"
                                                        ]
                                                    ); ?>

                                                    (
                                                    <?= (int) $result[
                                                        "anxiety_score"
                                                    ]; ?>
                                                    )
                                                </span>

                                            </td>

                                            <td>

                                                <span
                                                    class="admin-severity-badge <?= severity_class(
                                                        $result[
                                                            "stress_level"
                                                        ]
                                                    ); ?>"
                                                >
                                                    <?= admin_escape(
                                                        $result[
                                                            "stress_level"
                                                        ]
                                                    ); ?>

                                                    (
                                                    <?= (int) $result[
                                                        "stress_score"
                                                    ]; ?>
                                                    )
                                                </span>

                                            </td>

                                            <td>

                                                <span class="admin-table-date">

                                                    <?= date(
                                                        "d M Y",
                                                        strtotime(
                                                            $result[
                                                                "screening_date"
                                                            ]
                                                        )
                                                    ); ?>

                                                    <small>
                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $result[
                                                                    "screening_date"
                                                                ]
                                                            )
                                                        ); ?>
                                                    </small>

                                                </span>

                                            </td>

                                            <td>

                                                <a
                                                    href="student_details.php?id=<?= (int) $result[
                                                        "student_id"
                                                    ]; ?>"
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

                            <span>✅</span>

                            <h3>No attention results found</h3>

                            <p>
                                There are currently no screening records
                                marked as requiring attention.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <section class="admin-report-footer-note">

                <span>🔒</span>

                <div>

                    <strong>
                        Confidential report
                    </strong>

                    <p>
                        This report contains sensitive mental-health
                        information. It must only be accessed by
                        authorised personnel and used for appropriate
                        student wellbeing support.
                    </p>

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