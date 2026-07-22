<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$csrfToken = $_POST["csrf_token"] ?? "";
$mood = trim($_POST["mood"] ?? "");

$allowedMoods = [
    "Great",
    "Good",
    "Okay",
    "Low",
    "Struggling"
];

if (!verify_csrf_token($csrfToken)) {
    set_flash_message(
        "danger",
        "Invalid form request. Please try again."
    );

    header("Location: dashboard.php");
    exit;
}

if (!in_array($mood, $allowedMoods, true)) {
    set_flash_message(
        "danger",
        "Please select a valid mood."
    );

    header("Location: dashboard.php");
    exit;
}

$studentId = (int) $_SESSION["student_id"];
$today = date("Y-m-d");

$checkStatement = $conn->prepare(
    "SELECT mood_id
     FROM moods
     WHERE student_id = ?
     AND mood_date = ?
     LIMIT 1"
);

if (!$checkStatement) {
    set_flash_message(
        "danger",
        "Unable to check today's mood."
    );

    header("Location: dashboard.php");
    exit;
}

$checkStatement->bind_param(
    "is",
    $studentId,
    $today
);

$checkStatement->execute();

$result = $checkStatement->get_result();

$existingMood = $result->fetch_assoc();

$checkStatement->close();

if ($existingMood) {
    set_flash_message(
        "warning",
        "You have already recorded your mood today."
    );

    header("Location: dashboard.php");
    exit;
}

$insertStatement = $conn->prepare(
    "INSERT INTO moods
    (
        student_id,
        mood,
        mood_date
    )
    VALUES (?, ?, ?)"
);

if (!$insertStatement) {
    set_flash_message(
        "danger",
        "Unable to save your mood."
    );

    header("Location: dashboard.php");
    exit;
}

$insertStatement->bind_param(
    "iss",
    $studentId,
    $mood,
    $today
);

if ($insertStatement->execute()) {
    set_flash_message(
        "success",
        "Your mood has been recorded successfully."
    );
} else {
    set_flash_message(
        "danger",
        "Your mood could not be saved. Please try again."
    );
}

$insertStatement->close();

header("Location: dashboard.php");
exit;