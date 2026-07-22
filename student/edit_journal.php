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

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {
    $journalId = filter_input(
        INPUT_POST,
        "journal_id",
        FILTER_VALIDATE_INT
    );
}

if (!$journalId || $journalId < 1) {
    set_flash_message(
        "danger",
        "The selected journal entry is invalid."
    );

    header("Location: journal.php");
    exit;
}

$allowedMoods = [
    "Great",
    "Good",
    "Okay",
    "Low",
    "Struggling"
];

$errors = [];

/*
|--------------------------------------------------------------------------
| Retrieve existing journal
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
     WHERE journal_id = ?
     AND student_id = ?
     LIMIT 1"
);

if (!$journalStatement) {
    die("Unable to load the journal entry.");
}

$journalStatement->bind_param(
    "ii",
    $journalId,
    $studentId
);

$journalStatement->execute();

$journal = $journalStatement
    ->get_result()
    ->fetch_assoc();

$journalStatement->close();

if (!$journal) {
    set_flash_message(
        "danger",
        "The journal entry was not found."
    );

    header("Location: journal.php");
    exit;
}

$title = $journal["title"];
$content = $journal["content"];
$mood = $journal["mood"] ?? "";

/*
|--------------------------------------------------------------------------
| Update journal
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] =
            "Invalid request. Please refresh the page and try again.";
    }

    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $mood = trim($_POST["mood"] ?? "");

    if ($title === "") {
        $errors[] = "Please enter a journal title.";
    }

    if (strlen($title) > 150) {
        $errors[] =
            "The journal title must not exceed 150 characters.";
    }

    if ($content === "") {
        $errors[] =
            "Please write something in your journal.";
    }

    if (
        $mood !== "" &&
        !in_array($mood, $allowedMoods, true)
    ) {
        $errors[] =
            "The selected mood is invalid.";
    }

    if (empty($errors)) {
        $updateStatement = $conn->prepare(
            "UPDATE journals
             SET
                title = ?,
                content = ?,
                mood = ?
             WHERE journal_id = ?
             AND student_id = ?"
        );

        if (!$updateStatement) {
            $errors[] =
                "Unable to prepare the journal update.";
        } else {
            $updateStatement->bind_param(
                "sssii",
                $title,
                $content,
                $mood,
                $journalId,
                $studentId
            );

            if ($updateStatement->execute()) {
                $updateStatement->close();

                set_flash_message(
                    "success",
                    "Your journal entry was updated successfully."
                );

                header(
                    "Location: view_journal.php?id="
                    . $journalId
                );
                exit;
            }

            $updateStatement->close();

            $errors[] =
                "The journal entry could not be updated.";
        }
    }
}

$csrfToken = generate_csrf_token();

function edit_journal_mood_icon(string $mood): string
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

    <title>Edit Journal | Poly-Health</title>

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
                href="view_journal.php?id=<?=
                    (int) $journalId;
                ?>"
                class="btn btn-outline-poly btn-sm rounded-pill px-3"
            >
                Cancel
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

<main class="edit-journal-page">

    <div class="container">

        <section class="edit-journal-header">

            <div>

                <span class="section-label">
                    Private Journal Entry
                </span>

                <h1>Edit Journal</h1>

                <p>
                    Update your title, mood or journal content.
                </p>

            </div>

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

        <section class="edit-journal-card">

            <form
                method="POST"
                action="edit_journal.php"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escape($csrfToken); ?>"
                >

                <input
                    type="hidden"
                    name="journal_id"
                    value="<?= (int) $journalId; ?>"
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
                        required
                    >

                </div>

                <div class="mb-4">

                    <label
                        for="mood"
                        class="form-label"
                    >
                        How were you feeling?
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
                                <?= edit_journal_mood_icon(
                                    $moodOption
                                ); ?>
                                <?= escape($moodOption); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="mb-4">

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
                        rows="12"
                        required
                    ><?= escape($content); ?></textarea>

                </div>

                <div class="edit-journal-notice">

                    <strong>
                        Private reflection
                    </strong>

                    <p>
                        Your journal entry remains linked to your student
                        account and cannot be accessed through another
                        student's account.
                    </p>

                </div>

                <div class="edit-journal-actions">

                    <a
                        href="view_journal.php?id=<?=
                            (int) $journalId;
                        ?>"
                        class="btn btn-outline-secondary rounded-pill px-4"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-poly rounded-pill px-4"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </section>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>