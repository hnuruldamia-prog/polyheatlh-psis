<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

$statement = $conn->prepare(
    "SELECT
        student_number,
        fullname,
        email,
        phone,
        course,
        semester
     FROM students
     WHERE student_id = ?
     LIMIT 1"
);

$statement->bind_param(
    "i",
    $studentId
);

$statement->execute();

$result = $statement->get_result();

$student = $result->fetch_assoc();

$statement->close();

if (!$student) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

$today = date("Y-m-d");

$moodStatement = $conn->prepare(
    "SELECT mood, mood_date
     FROM moods
     WHERE student_id = ?
     AND mood_date = ?
     LIMIT 1"
);

$moodStatement->bind_param(
    "is",
    $studentId,
    $today
);

$moodStatement->execute();

$moodResult = $moodStatement->get_result();

$todayMood = $moodResult->fetch_assoc();

$moodStatement->close();

$moodEmoji = [
    "Great" => "😄",
    "Good" => "🙂",
    "Okay" => "😐",
    "Low" => "😔",
    "Struggling" => "😢"
];

$csrfToken = generate_csrf_token();

$firstName = explode(
    " ",
    trim($student["fullname"])
)[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Dashboard | Poly-Health</title>

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
            data-bs-target="#studentNavigation"
            aria-controls="studentNavigation"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="studentNavigation"
        >

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a
                        class="nav-link active"
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
                        Screening
                    </a>
                </li>

                <li class="nav-item">
    <a
        class="nav-link"
        href="screening_history.php"
    >
        History
    </a>
</li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="journal.php"
                    >
                        Journal
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="profile.php"
                    >
                        Profile
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

<main class="student-dashboard">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="dashboard-welcome">

            <div>

                <span class="dashboard-date">
                    <?= date("l, d F Y"); ?>
                </span>

                <h1>
                    Hello, <?= escape($firstName); ?> 👋
                </h1>

                <p>
                    Take a moment to check in with yourself today.
                </p>

            </div>

            <div class="student-summary">

                <span>
                    Student Number
                </span>

                <strong>
                    <?= escape($student["student_number"]); ?>
                </strong>

                <small>
                    <?= escape($student["course"]); ?>
                </small>

            </div>

        </section>

        <section class="daily-checkin">

    <?php if ($todayMood): ?>

        <div class="today-mood-result">

            <div>

                <span class="section-label">
                    Today's Check-In
                </span>

                <h2>
                    Thank you for checking in.
                </h2>

                <p>
                    Your mood has been recorded for today.
                </p>

            </div>

            <div class="recorded-mood">

                <span class="recorded-mood-emoji">
                    <?= $moodEmoji[$todayMood["mood"]] ?? "🙂"; ?>
                </span>

                <div>

                    <small>Today's mood</small>

                    <strong>
                        <?= escape($todayMood["mood"]); ?>
                    </strong>

                    <span>
                        <?= date(
                            "d F Y",
                            strtotime($todayMood["mood_date"])
                        ); ?>
                    </span>

                </div>

            </div>

        </div>

        <a
            href="mood_history.php"
            class="mood-history-link"
        >
            View mood history →
        </a>

    <?php else: ?>

        <div>

            <span class="section-label">
                Daily Check-In
            </span>

            <h2>
                How are you feeling today?
            </h2>

            <p>
                Select the feeling that best represents your mood.
            </p>

        </div>

        <form
            method="POST"
            action="save_mood.php"
            class="mood-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escape($csrfToken); ?>"
            >

            <div class="mood-options">

                <button
                    type="submit"
                    name="mood"
                    value="Great"
                    class="mood-button"
                >
                    <span>😄</span>
                    Great
                </button>

                <button
                    type="submit"
                    name="mood"
                    value="Good"
                    class="mood-button"
                >
                    <span>🙂</span>
                    Good
                </button>

                <button
                    type="submit"
                    name="mood"
                    value="Okay"
                    class="mood-button"
                >
                    <span>😐</span>
                    Okay
                </button>

                <button
                    type="submit"
                    name="mood"
                    value="Low"
                    class="mood-button"
                >
                    <span>😔</span>
                    Low
                </button>

                <button
                    type="submit"
                    name="mood"
                    value="Struggling"
                    class="mood-button"
                >
                    <span>😢</span>
                    Struggling
                </button>

            </div>

        </form>

        <small class="mood-notice">
            You can record one mood each day.
        </small>

    <?php endif; ?>

</section>

            <div>

                <span class="section-label">
                    Daily Check-In
                </span>

                <h2>
                    How are you feeling today?
                </h2>

                <p>
                    Select the feeling that best represents your mood.
                </p>

            </div>

            <div class="mood-options">

                <button type="button" class="mood-button">
                    <span>😄</span>
                    Great
                </button>

                <button type="button" class="mood-button">
                    <span>🙂</span>
                    Good
                </button>

                <button type="button" class="mood-button">
                    <span>😐</span>
                    Okay
                </button>

                <button type="button" class="mood-button">
                    <span>😔</span>
                    Low
                </button>

                <button type="button" class="mood-button">
                    <span>😢</span>
                    Struggling
                </button>

            </div>

            <small class="mood-notice">
                Mood saving will be connected in the next stage.
            </small>

        </section>

        <section class="dashboard-features">

    <div class="row g-4">

        <div class="col-md-6 col-xl-4">

            <a
                href="screening.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    🧠
                </div>

                <div class="dashboard-card-content">

                    <h3>Mental Health Screening</h3>

                    <p>
                        Complete the DASS-21 assessment to understand
                        your depression, anxiety and stress levels.
                    </p>

                    <span>Start screening →</span>

                </div>

            </a>

        </div>

        <div class="col-md-6 col-xl-4">

            <a
                href="screening_history.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    📊
                </div>

                <div class="dashboard-card-content">

                    <h3>Screening History</h3>

                    <p>
                        Review your previous DASS-21 scores and personal
                        recommendations.
                    </p>

                    <span>View results →</span>

                </div>

            </a>

        </div>

        <div class="col-md-6 col-xl-4">

            <a
                href="journal.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    📖
                </div>

                <div class="dashboard-card-content">

                    <h3>Private Journal</h3>

                    <p>
                        Write about your thoughts, feelings and daily
                        experiences.
                    </p>

                    <span>Open journal →</span>

                </div>

            </a>

        </div>

        <div class="col-md-6 col-xl-4">

            <a
                href="therapy.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    🌿
                </div>

                <div class="dashboard-card-content">

                    <h3>Therapy Resources</h3>

                    <p>
                        Explore calming exercises and helpful mental
                        wellbeing resources.
                    </p>

                    <span>Explore resources →</span>

                </div>

            </a>

        </div>

        <div class="col-md-6 col-xl-4">

            <a
                href="motivation.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    ✨
                </div>

                <div class="dashboard-card-content">

                    <h3>Daily Motivation</h3>

                    <p>
                        Read positive reminders and encouraging messages
                        for your day.
                    </p>

                    <span>View motivation →</span>

                </div>

            </a>

        </div>

        <div class="col-md-6 col-xl-4">

            <a
                href="help.php"
                class="dashboard-card"
            >

                <div class="dashboard-card-icon">
                    🤝
                </div>

                <div class="dashboard-card-content">

                    <h3>Help and Support</h3>

                    <p>
                        Find UPPSIK contact information and available
                        student support.
                    </p>

                    <span>Get support →</span>

                </div>

            </a>

        </div>

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