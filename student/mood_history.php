<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

$statement = $conn->prepare(
    "SELECT
        mood,
        mood_date,
        created_at
     FROM moods
     WHERE student_id = ?
     ORDER BY mood_date DESC"
);

$statement->bind_param(
    "i",
    $studentId
);

$statement->execute();

$result = $statement->get_result();

$moods = [];

while ($row = $result->fetch_assoc()) {
    $moods[] = $row;
}

$statement->close();

$moodEmoji = [
    "Great" => "😄",
    "Good" => "🙂",
    "Okay" => "😐",
    "Low" => "😔",
    "Struggling" => "😢"
];
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mood History | Poly-Health</title>

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

<nav class="navbar student-navbar">

    <div class="container">

        <a
            class="navbar-brand student-logo"
            href="dashboard.php"
        >
            POLY-HEALTH
        </a>

        <a
            href="dashboard.php"
            class="btn btn-outline-poly btn-sm"
        >
            ← Dashboard
        </a>

    </div>

</nav>

<main class="student-dashboard">

    <div class="container">

        <section class="history-header">

            <span class="section-label">
                Personal Wellbeing
            </span>

            <h1>Mood History</h1>

            <p>
                Review your previous daily mood check-ins.
            </p>

        </section>

        <section class="mood-history-card">

            <?php if (empty($moods)): ?>

                <div class="empty-state">

                    <span>🌿</span>

                    <h2>No mood records yet</h2>

                    <p>
                        Record your first daily mood from the dashboard.
                    </p>

                    <a
                        href="dashboard.php"
                        class="btn btn-poly"
                    >
                        Return to Dashboard
                    </a>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table mood-history-table">

                        <thead>

                            <tr>
                                <th>Mood</th>
                                <th>Date</th>
                                <th>Recorded At</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($moods as $mood): ?>

                                <tr>

                                    <td>

                                        <div class="history-mood">

                                            <span>
                                                <?= $moodEmoji[$mood["mood"]]
                                                    ?? "🙂"; ?>
                                            </span>

                                            <strong>
                                                <?= escape($mood["mood"]); ?>
                                            </strong>

                                        </div>

                                    </td>

                                    <td>
                                        <?= date(
                                            "d F Y",
                                            strtotime($mood["mood_date"])
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= date(
                                            "h:i A",
                                            strtotime($mood["created_at"])
                                        ); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>