<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];
$errors = [];
$submittedAnswers = [];

$questionStatement = $conn->prepare(
    "SELECT
        question_id,
        question_number,
        question_text,
        category
     FROM dass_questions
     ORDER BY question_number ASC"
);

if (!$questionStatement) {
    die("Unable to load DASS-21 questions.");
}

$questionStatement->execute();

$questionResult = $questionStatement->get_result();

$questions = [];

while ($row = $questionResult->fetch_assoc()) {
    $questions[] = $row;
}

$questionStatement->close();

if (count($questions) !== 21) {
    die(
        "The DASS-21 questionnaire is incomplete. "
        . "Please ensure all 21 questions exist in the database."
    );
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] =
            "Invalid form request. Please refresh the page and try again.";
    }

    foreach ($questions as $question) {

        $questionId = (int) $question["question_id"];
        $fieldName = "answer_" . $questionId;

        if (!isset($_POST[$fieldName])) {
            $errors[] =
                "Please answer question "
                . $question["question_number"]
                . ".";

            continue;
        }

        $answerValue = filter_var(
            $_POST[$fieldName],
            FILTER_VALIDATE_INT
        );

        if (
            $answerValue === false ||
            $answerValue < 0 ||
            $answerValue > 3
        ) {
            $errors[] =
                "Question "
                . $question["question_number"]
                . " contains an invalid answer.";

            continue;
        }

        $submittedAnswers[$questionId] = $answerValue;
    }

    if (empty($errors)) {

        $depressionRaw = 0;
        $anxietyRaw = 0;
        $stressRaw = 0;

        $question21Answer = 0;

        foreach ($questions as $question) {

            $questionId = (int) $question["question_id"];
            $questionNumber =
                (int) $question["question_number"];

            $answerValue =
                $submittedAnswers[$questionId];

            if ($question["category"] === "D") {
                $depressionRaw += $answerValue;
            } elseif ($question["category"] === "A") {
                $anxietyRaw += $answerValue;
            } elseif ($question["category"] === "S") {
                $stressRaw += $answerValue;
            }

            if ($questionNumber === 21) {
                $question21Answer = $answerValue;
            }
        }

        $depressionScore = $depressionRaw * 2;
        $anxietyScore = $anxietyRaw * 2;
        $stressScore = $stressRaw * 2;

        $depressionLevel = get_dass_severity(
            "D",
            $depressionScore
        );

        $anxietyLevel = get_dass_severity(
            "A",
            $anxietyScore
        );

        $stressLevel = get_dass_severity(
            "S",
            $stressScore
        );

        $requiresAttention = dass_requires_attention(
            $depressionLevel,
            $anxietyLevel,
            $stressLevel
        );

        if ($question21Answer > 0) {
            $requiresAttention = true;
        }

        $attentionValue =
            $requiresAttention ? 1 : 0;

        $conn->begin_transaction();

        try {

            $resultStatement = $conn->prepare(
                "INSERT INTO dass_results
                (
                    student_id,
                    depression_raw,
                    anxiety_raw,
                    stress_raw,
                    depression_score,
                    anxiety_score,
                    stress_score,
                    depression_level,
                    anxiety_level,
                    stress_level,
                    requires_attention
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$resultStatement) {
                throw new Exception(
                    "Unable to prepare the result."
                );
            }

            $resultStatement->bind_param(
                "iiiiiiisssi",
                $studentId,
                $depressionRaw,
                $anxietyRaw,
                $stressRaw,
                $depressionScore,
                $anxietyScore,
                $stressScore,
                $depressionLevel,
                $anxietyLevel,
                $stressLevel,
                $attentionValue
            );

            if (!$resultStatement->execute()) {
                throw new Exception(
                    "Unable to save the result."
                );
            }

            $resultId = $conn->insert_id;

            $resultStatement->close();

            $answerStatement = $conn->prepare(
                "INSERT INTO dass_answers
                (
                    result_id,
                    question_id,
                    answer_value
                )
                VALUES (?, ?, ?)"
            );

            if (!$answerStatement) {
                throw new Exception(
                    "Unable to prepare the answers."
                );
            }

            foreach ($submittedAnswers as $questionId => $answerValue) {

                $answerStatement->bind_param(
                    "iii",
                    $resultId,
                    $questionId,
                    $answerValue
                );

                if (!$answerStatement->execute()) {
                    throw new Exception(
                        "Unable to save all answers."
                    );
                }
            }

            $answerStatement->close();

            $conn->commit();

            unset($_SESSION["csrf_token"]);

            header(
                "Location: result.php?id="
                . $resultId
            );
            exit;

        } catch (Throwable $exception) {

            $conn->rollback();

            $errors[] =
                "The screening result could not be saved. "
                . "Please try again.";
        }
    }
}

$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DASS-21 Screening | Poly-Health</title>

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

        <div class="ms-auto">

            <a
                href="dashboard.php"
                class="btn btn-outline-poly btn-sm"
            >
                ← Dashboard
            </a>

        </div>

    </div>

</nav>

<main class="screening-page">

    <div class="container">

        <section class="screening-header">

            <span class="section-label">
                Mental Health Screening
            </span>

            <h1>DASS-21 Assessment</h1>

            <p>
                Please read every statement and select the answer that
                best describes your experience during the past week.
            </p>

        </section>

        <section class="screening-instructions">

            <h2>Answer options</h2>

            <div class="instruction-grid">

                <div>
                    <strong>0</strong>
                    <span>Tidak Pernah</span>
                </div>

                <div>
                    <strong>1</strong>
                    <span>Jarang</span>
                </div>

                <div>
                    <strong>2</strong>
                    <span>Kerap</span>
                </div>

                <div>
                    <strong>3</strong>
                    <span>Sangat Kerap</span>
                </div>

            </div>

            <p>
                Answer all 21 questions honestly. There are no right or
                wrong answers.
            </p>

        </section>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <strong>
                    Please complete the questionnaire.
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

        <form
            method="POST"
            action="screening.php"
            id="dassForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escape($csrfToken); ?>"
            >

            <div class="screening-questions">

                <?php foreach ($questions as $question): ?>

                    <?php
                    $questionId =
                        (int) $question["question_id"];

                    $questionNumber =
                        (int) $question["question_number"];

                    $selectedAnswer =
                        $submittedAnswers[$questionId]
                        ?? null;
                    ?>

                    <article
                        class="screening-question"
                        id="question-<?= $questionNumber; ?>"
                    >

                        <div class="question-number">
                            <?= $questionNumber; ?>
                        </div>

                        <div class="question-content">

                            <p>
                                <?= escape(
                                    $question["question_text"]
                                ); ?>
                            </p>

                            <div class="answer-options">

                                <?php
                                $answerLabels = [
                                    0 => "Tidak Pernah",
                                    1 => "Jarang",
                                    2 => "Kerap",
                                    3 => "Sangat Kerap"
                                ];
                                ?>

                                <?php foreach ($answerLabels as $value => $label): ?>

                                    <label class="answer-choice">

                                        <input
                                            type="radio"
                                            name="answer_<?= $questionId; ?>"
                                            value="<?= $value; ?>"
                                            <?= $selectedAnswer === $value
                                                ? "checked"
                                                : ""; ?>
                                            required
                                        >

                                        <span class="answer-value">
                                            <?= $value; ?>
                                        </span>

                                        <small>
                                            <?= escape($label); ?>
                                        </small>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

            <section class="screening-notice">

                <strong>Important notice</strong>

                <p>
                    DASS-21 is an initial screening tool and does not
                    provide a medical diagnosis. A qualified mental-health
                    professional should assess any ongoing concerns.
                </p>

            </section>

            <button
                type="submit"
                class="btn btn-poly screening-submit"
                id="screeningSubmit"
            >
                Submit DASS-21 Assessment
            </button>

        </form>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="../assets/js/script.js"></script>

</body>
</html>