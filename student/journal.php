<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];

$errors = [];

$title = "";
$content = "";
$mood = "";

/*
|--------------------------------------------------------------------------
| Allowed moods
|--------------------------------------------------------------------------
*/

$allowedMoods = [
    "Great",
    "Good",
    "Okay",
    "Low",
    "Struggling"
];

/*
|--------------------------------------------------------------------------
| Add journal entry
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "add"
) {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = "Invalid request. Please refresh the page.";
    }

    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $mood = trim($_POST["mood"] ?? "");

    if ($title === "") {
        $errors[] = "Please enter a journal title.";
    }

    if (strlen($title) > 150) {
        $errors[] = "The journal title must not exceed 150 characters.";
    }

    if ($content === "") {
        $errors[] = "Please write something in your journal.";
    }

    if (
        $mood !== "" &&
        !in_array($mood, $allowedMoods, true)
    ) {
        $errors[] = "The selected mood is invalid.";
    }

    if (empty($errors)) {
        $aiFeedback = null;

        $insertStatement = $conn->prepare(
            "INSERT INTO journals
            (
                student_id,
                title,
                content,
                mood,
                ai_feedback
            )
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$insertStatement) {
            $errors[] = "Unable to prepare the journal entry.";
        } else {
            $insertStatement->bind_param(
                "issss",
                $studentId,
                $title,
                $content,
                $mood,
                $aiFeedback
            );

            if ($insertStatement->execute()) {
                $insertStatement->close();

                set_flash_message(
                    "success",
                    "Your journal entry was saved successfully."
                );

                header("Location: journal.php");
                exit;
            }

            $insertStatement->close();

            $errors[] = "The journal entry could not be saved.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Delete journal entry
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "delete"
) {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        set_flash_message(
            "danger",
            "Invalid request. Please refresh the page."
        );

        header("Location: journal.php");
        exit;
    }

    $journalId = filter_input(
        INPUT_POST,
        "journal_id",
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

    $deleteStatement = $conn->prepare(
        "DELETE FROM journals
         WHERE journal_id = ?
         AND student_id = ?"
    );

    if (!$deleteStatement) {
        set_flash_message(
            "danger",
            "Unable to delete the journal entry."
        );

        header("Location: journal.php");
        exit;
    }

    $deleteStatement->bind_param(
        "ii",
        $journalId,
        $studentId
    );

    $deleteStatement->execute();

    if ($deleteStatement->affected_rows > 0) {
        set_flash_message(
            "success",
            "The journal entry was deleted successfully."
        );
    } else {
        set_flash_message(
            "danger",
            "The journal entry was not found."
        );
    }

    $deleteStatement->close();

    header("Location: journal.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Retrieve journal entries
|--------------------------------------------------------------------------
*/

$journalStatement = $conn->prepare(
    "SELECT
        journal_id,
        title,
        content,
        mood,
        ai_feedback,
        created_at
     FROM journals
     WHERE student_id = ?
     ORDER BY created_at DESC"
);

if (!$journalStatement) {
    die("Unable to load journal entries.");
}

$journalStatement->bind_param(
    "i",
    $studentId
);

$journalStatement->execute();

$journalResult = $journalStatement->get_result();

$journalEntries = [];

while ($row = $journalResult->fetch_assoc()) {
    $journalEntries[] = $row;
}

$journalStatement->close();

$csrfToken = generate_csrf_token();

/*
|--------------------------------------------------------------------------
| Mood display helper
|--------------------------------------------------------------------------
*/

function journal_mood_icon(string $mood): string
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

    <title>My Journal | Poly-Health</title>

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
                class="btn btn-outline-poly btn-sm"
            >
                Dashboard
            </a>

            <a
                href="logout.php"
                class="btn btn-outline-danger btn-sm"
            >
                Logout
            </a>

        </div>

    </div>

</nav>

<main class="journal-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="journal-header">

            <div>

                <span class="section-label">
                    Private Reflection Space
                </span>

                <h1>My Journal</h1>

                <p>
                    Write about your thoughts, emotions and daily
                    experiences. Your entries are linked to your private
                    student account.
                </p>

            </div>

            <button
                type="button"
                class="btn btn-poly"
                data-bs-toggle="modal"
                data-bs-target="#addJournalModal"
            >
                Write New Entry
            </button>

        </section>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>
                    Please correct the following:
                </strong>

                <ul class="mb-0 mt-2">

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= escape($error); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <?php if (empty($journalEntries)): ?>

            <section class="journal-empty">

                <span class="journal-empty-icon">
                    📖
                </span>

                <h2>Your journal is empty</h2>

                <p>
                    Start writing your thoughts, feelings or experiences
                    in your private journal.
                </p>

                <button
                    type="button"
                    class="btn btn-poly"
                    data-bs-toggle="modal"
                    data-bs-target="#addJournalModal"
                >
                    Write First Entry
                </button>

            </section>

        <?php else: ?>

            <section class="journal-grid">

                <?php foreach ($journalEntries as $entry): ?>

                    <article class="journal-card">

                        <div class="journal-card-top">

                            <span class="journal-mood-icon">
                                <?= journal_mood_icon(
                                    $entry["mood"] ?? ""
                                ); ?>
                            </span>

                            <?php if (!empty($entry["mood"])): ?>

                                <span class="journal-mood-label">
                                    <?= escape($entry["mood"]); ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <h2>
                            <?= escape($entry["title"]); ?>
                        </h2>

                        <div class="journal-content-preview">
                            <?= nl2br(
                                escape($entry["content"])
                            ); ?>
                        </div>

                        <?php if (!empty($entry["ai_feedback"])): ?>

                            <div class="journal-ai-feedback">

                                <strong>
                                    Poly-Health Feedback
                                </strong>

                                <p>
                                    <?= nl2br(
                                        escape(
                                            $entry["ai_feedback"]
                                        )
                                    ); ?>
                                </p>

                            </div>

                        <?php endif; ?>

                        <div class="journal-card-footer">

                            <div>

                                <small>Created</small>

                                <span>
                                    <?= date(
                                        "d M Y, h:i A",
                                        strtotime(
                                            $entry["created_at"]
                                        )
                                    ); ?>
                                </span>

                            </div>

                            <div class="journal-card-actions">

                                <a
                                    href="view_journal.php?id=<?=
                                        (int) $entry["journal_id"];
                                    ?>"
                                    class="btn btn-outline-poly btn-sm"
                                >
                                    View
                                </a>

                                <a
                                    href="edit_journal.php?id=<?=
                                        (int) $entry["journal_id"];
                                    ?>"
                                    class="btn btn-outline-secondary btn-sm"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="journal.php"
                                    onsubmit="return confirm('Are you sure you want to delete this journal entry?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= escape($csrfToken); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="journal_id"
                                        value="<?=
                                            (int) $entry["journal_id"];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </section>

        <?php endif; ?>

        <section class="journal-privacy-notice">

            <strong>Privacy notice</strong>

            <p>
                Always log out after using Poly-Health on a shared
                computer or device.
            </p>

        </section>

    </div>

</main>

<div
    class="modal fade"
    id="addJournalModal"
    tabindex="-1"
    aria-labelledby="addJournalModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content journal-modal">

            <div class="modal-header">

                <div>

                    <span class="section-label">
                        Private Journal
                    </span>

                    <h2
                        class="modal-title"
                        id="addJournalModalLabel"
                    >
                        Write New Entry
                    </h2>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <form
                method="POST"
                action="journal.php"
            >

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape($csrfToken); ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >

                    <div class="mb-4">

                        <label
                            for="title"
                            class="form-label"
                        >
                            Journal Title
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="title"
                            name="title"
                            maxlength="150"
                            value="<?= escape($title); ?>"
                            placeholder="Example: How I felt today"
                            required
                        >

                    </div>

                    <div class="mb-4">

                        <label
                            for="mood"
                            class="form-label"
                        >
                            How are you feeling?
                        </label>

                        <select
                            class="form-select"
                            id="mood"
                            name="mood"
                        >

                            <option value="">
                                Select a mood
                            </option>

                            <?php foreach ($allowedMoods as $moodOption): ?>

                                <option
                                    value="<?= escape($moodOption); ?>"
                                    <?= $mood === $moodOption
                                        ? "selected"
                                        : ""; ?>
                                >
                                    <?= journal_mood_icon(
                                        $moodOption
                                    ); ?>
                                    <?= escape($moodOption); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label
                            for="content"
                            class="form-label"
                        >
                            Journal Content
                        </label>

                        <textarea
                            class="form-control"
                            id="content"
                            name="content"
                            rows="8"
                            placeholder="Write your thoughts and feelings here..."
                            required
                        ><?= escape($content); ?></textarea>

                    </div>

                    <div class="journal-form-notice">

                        <strong>
                            Write honestly
                        </strong>

                        <p>
                            There are no right or wrong answers. Use this
                            space to reflect on your experiences.
                        </p>

                    </div>

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
                        class="btn btn-poly"
                    >
                        Save Journal Entry
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<?php if (!empty($errors)): ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var journalModalElement =
        document.getElementById("addJournalModal");

    if (journalModalElement) {
        var journalModal =
            new bootstrap.Modal(journalModalElement);

        journalModal.show();
    }
});
</script>

<?php endif; ?>

<script src="../assets/js/script.js"></script>

</body>
</html>