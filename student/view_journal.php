<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

$journalId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$journalId || $journalId < 1) {
    set_flash_message(
        "danger",
        "The selected journal entry is invalid."
    );

    header("Location: journal.php");
    exit;
}

$statement = $conn->prepare(
    "SELECT
        journal_id,
        title,
        content,
        mood,
        ai_feedback,
        created_at
     FROM journals
     WHERE journal_id = ?
     AND student_id = ?
     LIMIT 1"
);

if (!$statement) {
    die("Unable to load the journal entry.");
}

$statement->bind_param(
    "ii",
    $journalId,
    $studentId
);

$statement->execute();

$journal = $statement
    ->get_result()
    ->fetch_assoc();

$statement->close();

if (!$journal) {
    set_flash_message(
        "danger",
        "The journal entry was not found."
    );

    header("Location: journal.php");
    exit;
}

function view_journal_mood_icon(string $mood): string
{
    switch ($mood) {
        case "Great":
            return "😄";

        case "Good":
            return "🙂";

        case "Okay":
            return "😐";

        case "Low":
            return "😔";

        case "Struggling":
            return "😢";

        default:
            return "📝";
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

    <title>View Journal | Poly-Health</title>

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
                href="journal.php"
                class="btn btn-outline-poly btn-sm rounded-pill px-3"
            >
                Back to Journal
            </a>

            <a
                href="dashboard.php"
                class="btn btn-poly btn-sm rounded-pill px-3"
            >
                Dashboard
            </a>

        </div>

    </div>

</nav>

<main class="view-journal-page">

    <div class="container">

        <section class="view-journal-header">

            <div>

                <span class="section-label">
                    Private Journal Entry
                </span>

                <h1>
                    <?= escape($journal["title"]); ?>
                </h1>

                <p>
                    Written on
                    <?= date(
                        "d F Y, h:i A",
                        strtotime($journal["created_at"])
                    ); ?>
                </p>

            </div>

            <a
                href="edit_journal.php?id=<?=
                    (int) $journal["journal_id"];
                ?>"
                class="btn btn-outline-secondary rounded-pill px-4"
            >
                Edit Entry
            </a>

        </section>

        <article class="view-journal-card">

            <div class="view-journal-meta">

                <div class="view-journal-mood">

                    <span>
                        <?= view_journal_mood_icon(
                            $journal["mood"] ?? ""
                        ); ?>
                    </span>

                    <div>

                        <small>Mood</small>

                        <strong>
                            <?= !empty($journal["mood"])
                                ? escape($journal["mood"])
                                : "Not selected"; ?>
                        </strong>

                    </div>

                </div>

                <div class="view-journal-date">

                    <small>Created</small>

                    <strong>
                        <?= date(
                            "d M Y",
                            strtotime($journal["created_at"])
                        ); ?>
                    </strong>

                </div>

            </div>

            <div class="view-journal-content">

                <?= nl2br(
                    escape($journal["content"])
                ); ?>

            </div>

            <?php if (!empty($journal["ai_feedback"])): ?>

                <section class="view-journal-feedback">

                    <span class="section-label">
                        Poly-Health Feedback
                    </span>

                    <h2>Supportive reflection</h2>

                    <p>
                        <?= nl2br(
                            escape($journal["ai_feedback"])
                        ); ?>
                    </p>

                </section>

            <?php endif; ?>

        </article>

        <section class="view-journal-notice">

            <strong>Your privacy matters</strong>

            <p>
                This journal entry is accessible only through your
                authenticated account. Remember to log out when using
                a shared device.
            </p>

        </section>

        <div class="view-journal-actions">

            <a
                href="journal.php"
                class="btn btn-outline-poly"
            >
                Back to Journal
            </a>

            <a
                href="edit_journal.php?id=<?=
                    (int) $journal["journal_id"];
                ?>"
                class="btn btn-poly"
            >
                Edit This Entry
            </a>

        </div>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>