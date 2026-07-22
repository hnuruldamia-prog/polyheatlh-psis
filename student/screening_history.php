<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

/*
|--------------------------------------------------------------------------
| Retrieve screening history
|--------------------------------------------------------------------------
*/

$statement = $conn->prepare(
    "SELECT
        result_id,
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

if (!$statement) {
    die("Unable to load screening history.");
}

$statement->bind_param(
    "i",
    $studentId
);

$statement->execute();

$result = $statement->get_result();

$screeningHistory = [];

while ($row = $result->fetch_assoc()) {
    $screeningHistory[] = $row;
}

$statement->close();

/*
|--------------------------------------------------------------------------
| Display helper
|--------------------------------------------------------------------------
*/

function history_level_class(string $level): string
{
    switch ($level) {
        case "Normal":
            return "history-normal";

        case "Ringan":
            return "history-mild";

        case "Sederhana":
            return "history-moderate";

        case "Teruk":
            return "history-severe";

        case "Sangat Teruk":
            return "history-extreme";

        default:
            return "history-unknown";
    }
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

    <title>DASS-21 History | Poly-Health</title>

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

<body class="dashboard-body">

<nav class="navbar navbar-expand-lg student-navbar">

    <div class="container">

        <a
            class="navbar-brand student-logo"
            href="dashboard.php"
        >
            POLY-HEALTH
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#historyNavigation"
            aria-controls="historyNavigation"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="historyNavigation"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="dashboard.php"
                    >
                        Dashboard
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="screening.php"
                    >
                        New Screening
                    </a>

                </li>

                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="screening_history.php"
                    >
                        Screening History
                    </a>

                </li>

                <li class="nav-item ms-lg-3">

                    <a
                        class="btn btn-outline-danger btn-sm rounded-pill px-3"
                        href="logout.php"
                    >
                        Logout
                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>

<main class="screening-history-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="screening-history-header">

            <div>

                <span class="section-label">
                    Personal Assessment Records
                </span>

                <h1>DASS-21 Screening History</h1>

                <p>
                    Review your previous depression, anxiety, and stress
                    screening results.
                </p>

            </div>

            <a
                href="screening.php"
                class="btn btn-poly"
            >
                Complete New Screening
            </a>

        </section>

        <?php if (empty($screeningHistory)): ?>

            <section class="screening-history-empty">

                <span class="history-empty-icon">
                    🧠
                </span>

                <h2>No screening results yet</h2>

                <p>
                    Complete your first DASS-21 assessment to view your
                    wellbeing results here.
                </p>

                <a
                    href="screening.php"
                    class="btn btn-poly"
                >
                    Start DASS-21 Screening
                </a>

            </section>

        <?php else: ?>

            <section class="screening-history-summary">

                <div class="history-summary-card">

                    <span>Total Screenings</span>

                    <strong>
                        <?= count($screeningHistory); ?>
                    </strong>

                </div>

                <div class="history-summary-card">

                    <span>Latest Screening</span>

                    <strong>
                        <?= date(
                            "d M Y",
                            strtotime(
                                $screeningHistory[0]["screening_date"]
                            )
                        ); ?>
                    </strong>

                </div>

                <div class="history-summary-card">

                    <span>Records Requiring Attention</span>

                    <strong>

                        <?php
                        $attentionCount = 0;

                        foreach ($screeningHistory as $screening) {
                            if (
                                (int) $screening["requires_attention"] === 1
                            ) {
                                $attentionCount++;
                            }
                        }

                        echo $attentionCount;
                        ?>

                    </strong>

                </div>

            </section>

            <section class="screening-history-list">

                <?php foreach ($screeningHistory as $index => $screening): ?>

                    <article class="screening-history-item">

                        <div class="history-item-number">

                            <span>
                                <?= count($screeningHistory) - $index; ?>
                            </span>

                        </div>

                        <div class="history-item-content">

                            <div class="history-item-heading">

                                <div>

                                    <small>
                                        DASS-21 Assessment
                                    </small>

                                    <h2>
                                        <?= date(
                                            "d F Y",
                                            strtotime(
                                                $screening["screening_date"]
                                            )
                                        ); ?>
                                    </h2>

                                    <span>
                                        <?= date(
                                            "h:i A",
                                            strtotime(
                                                $screening["screening_date"]
                                            )
                                        ); ?>
                                    </span>

                                </div>

                                <?php if (
                                    (int) $screening["requires_attention"] === 1
                                ): ?>

                                    <span class="history-attention-badge">
                                        Support Recommended
                                    </span>

                                <?php else: ?>

                                    <span class="history-completed-badge">
                                        Completed
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="history-score-grid">

                                <div class="history-score-box">

                                    <span>Depression</span>

                                    <strong>
                                        <?= (int) $screening[
                                            "depression_score"
                                        ]; ?>
                                    </strong>

                                    <small
                                        class="<?=
                                            escape(
                                                history_level_class(
                                                    $screening[
                                                        "depression_level"
                                                    ]
                                                )
                                            );
                                        ?>"
                                    >
                                        <?= escape(
                                            $screening[
                                                "depression_level"
                                            ]
                                        ); ?>
                                    </small>

                                </div>

                                <div class="history-score-box">

                                    <span>Anxiety</span>

                                    <strong>
                                        <?= (int) $screening[
                                            "anxiety_score"
                                        ]; ?>
                                    </strong>

                                    <small
                                        class="<?=
                                            escape(
                                                history_level_class(
                                                    $screening[
                                                        "anxiety_level"
                                                    ]
                                                )
                                            );
                                        ?>"
                                    >
                                        <?= escape(
                                            $screening[
                                                "anxiety_level"
                                            ]
                                        ); ?>
                                    </small>

                                </div>

                                <div class="history-score-box">

                                    <span>Stress</span>

                                    <strong>
                                        <?= (int) $screening[
                                            "stress_score"
                                        ]; ?>
                                    </strong>

                                    <small
                                        class="<?=
                                            escape(
                                                history_level_class(
                                                    $screening[
                                                        "stress_level"
                                                    ]
                                                )
                                            );
                                        ?>"
                                    >
                                        <?= escape(
                                            $screening[
                                                "stress_level"
                                            ]
                                        ); ?>
                                    </small>

                                </div>

                            </div>

                            <div class="history-item-actions">

                                <a
                                    href="result.php?id=<?=
                                        (int) $screening["result_id"];
                                    ?>"
                                    class="btn btn-outline-poly btn-sm"
                                >
                                    View Full Result
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <section class="screening-history-notice">

            <strong>Privacy notice</strong>

            <p>
                Your screening history is visible only through your
                authenticated student account and authorized UPPSIK
                personnel.
            </p>

        </section>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>