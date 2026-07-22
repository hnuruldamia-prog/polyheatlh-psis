<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentName = $_SESSION["fullname"] ?? "Student";

$motivations = [
    [
        "icon" => "🌱",
        "title" => "Small progress still matters",
        "message" =>
            "You do not need to solve everything today. One small step forward is still progress."
    ],
    [
        "icon" => "💚",
        "title" => "Be patient with yourself",
        "message" =>
            "You are learning, growing and doing your best. Give yourself the same kindness you would offer a friend."
    ],
    [
        "icon" => "☀️",
        "title" => "A difficult day is not a failed day",
        "message" =>
            "Resting, slowing down and asking for help are also meaningful ways of taking care of yourself."
    ],
    [
        "icon" => "🌿",
        "title" => "Your feelings are valid",
        "message" =>
            "It is okay to feel tired, worried or uncertain. Your emotions deserve attention, not judgement."
    ],
    [
        "icon" => "✨",
        "title" => "You are more capable than you feel",
        "message" =>
            "Doubt can feel strong, but it does not define your ability or your future."
    ],
    [
        "icon" => "🫶",
        "title" => "You do not have to face everything alone",
        "message" =>
            "Talking to someone you trust can make difficult moments feel lighter and more manageable."
    ]
];

$dailyIndex = (int) date("z") % count($motivations);
$dailyMotivation = $motivations[$dailyIndex];
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Daily Motivation | Poly-Health</title>

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

        <div class="ms-auto d-flex gap-2">

            <a
                href="dashboard.php"
                class="btn btn-outline-poly btn-sm rounded-pill px-3"
            >
                Dashboard
            </a>

            <a
                href="help.php"
                class="btn btn-poly btn-sm rounded-pill px-3"
            >
                Get Support
            </a>

        </div>

    </div>

</nav>

<main class="motivation-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="motivation-hero">

            <div class="motivation-hero-content">

                <span class="section-label">
                    Daily Encouragement
                </span>

                <h1>
                    A gentle reminder for today
                </h1>

                <p>
                    Hello, <?= escape($studentName); ?>. Take a moment to
                    pause, breathe and read something supportive.
                </p>

            </div>

            <div class="motivation-date-card">

                <small>Today</small>

                <strong>
                    <?= date("d F Y"); ?>
                </strong>

                <span>
                    One day at a time
                </span>

            </div>

        </section>

        <section class="daily-motivation-card">

            <span class="daily-motivation-icon">
                <?= $dailyMotivation["icon"]; ?>
            </span>

            <div>

                <span class="section-label">
                    Today's Message
                </span>

                <h2>
                    <?= escape($dailyMotivation["title"]); ?>
                </h2>

                <p>
                    <?= escape($dailyMotivation["message"]); ?>
                </p>

            </div>

        </section>

        <section class="motivation-section">

            <div class="motivation-section-heading">

                <span class="section-label">
                    More Positive Reminders
                </span>

                <h2>Messages for difficult moments</h2>

                <p>
                    Read any reminder that feels helpful today.
                </p>

            </div>

            <div class="motivation-grid">

                <?php foreach ($motivations as $motivation): ?>

                    <article class="motivation-card">

                        <span class="motivation-card-icon">
                            <?= $motivation["icon"]; ?>
                        </span>

                        <h3>
                            <?= escape($motivation["title"]); ?>
                        </h3>

                        <p>
                            <?= escape($motivation["message"]); ?>
                        </p>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

        <section class="motivation-actions-section">

            <div class="motivation-action-card">

                <span>📖</span>

                <div>

                    <h3>Write about your day</h3>

                    <p>
                        Use your private journal to reflect on your
                        thoughts, feelings and experiences.
                    </p>

                    <a
                        href="journal.php"
                        class="btn btn-outline-poly rounded-pill px-4"
                    >
                        Open Journal
                    </a>

                </div>

            </div>

            <div class="motivation-action-card">

                <span>🌿</span>

                <div>

                    <h3>Try a calming exercise</h3>

                    <p>
                        Explore breathing, grounding and mindfulness
                        activities.
                    </p>

                    <a
                        href="therapy.php"
                        class="btn btn-outline-poly rounded-pill px-4"
                    >
                        View Exercises
                    </a>

                </div>

            </div>

        </section>

        <section class="motivation-support-card">

            <div class="motivation-support-icon">
                🤝
            </div>

            <div>

                <span class="section-label">
                    Support is available
                </span>

                <h2>You do not have to manage everything alone.</h2>

                <p>
                    Contact UPPSIK or speak with someone you trust when
                    difficult feelings begin to affect your studies,
                    relationships or daily activities.
                </p>

                <a
                    href="help.php"
                    class="btn btn-light rounded-pill px-4"
                >
                    View Support Options
                </a>

            </div>

        </section>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>