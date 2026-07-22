<?php

require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/functions.php";

if (student_is_logged_in()) {
    header("Location: student/dashboard.php");
    exit;
}

$studentNumber = "";
$fullname = "";
$email = "";
$phone = "";
$course = "";
$semester = "";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = "Invalid form request. Please refresh the page and try again.";
    }

    $studentNumber = trim($_POST["student_number"] ?? "");
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $semester = trim($_POST["semester"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($studentNumber === "") {
        $errors[] = "Student number is required.";
    }

    if ($fullname === "") {
        $errors[] = "Full name is required.";
    }

    if ($email === "") {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($course === "") {
        $errors[] = "Course is required.";
    }

    if ($semester === "") {
        $errors[] = "Semester is required.";
    } elseif (
        !filter_var(
            $semester,
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1,
                    "max_range" => 10
                ]
            ]
        )
    ) {
        $errors[] = "Semester must be between 1 and 10.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if ($confirmPassword === "") {
        $errors[] = "Please confirm your password.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {

        $checkStatement = $conn->prepare(
            "SELECT student_id
             FROM students
             WHERE student_number = ?
             OR email = ?
             LIMIT 1"
        );

        if (!$checkStatement) {
            $errors[] = "Unable to process registration.";
        } else {

            $checkStatement->bind_param(
                "ss",
                $studentNumber,
                $email
            );

            $checkStatement->execute();

            $checkStatement->store_result();

            if ($checkStatement->num_rows > 0) {
                $errors[] =
                    "The student number or email address is already registered.";
            }

            $checkStatement->close();
        }
    }

    if (empty($errors)) {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $insertStatement = $conn->prepare(
            "INSERT INTO students
            (
                student_number,
                fullname,
                email,
                phone,
                course,
                semester,
                password
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$insertStatement) {
            $errors[] = "Unable to create your account.";
        } else {

            $semesterNumber = (int) $semester;

            $insertStatement->bind_param(
                "sssssis",
                $studentNumber,
                $fullname,
                $email,
                $phone,
                $course,
                $semesterNumber,
                $hashedPassword
            );

            if ($insertStatement->execute()) {

                set_flash_message(
                    "success",
                    "Registration successful. You may now log in."
                );

                $insertStatement->close();

                header("Location: login.php");
                exit;
            }

            if ($insertStatement->errno === 1062) {
                $errors[] =
                    "The student number or email address is already registered.";
            } else {
                $errors[] =
                    "Registration failed. Please try again.";
            }

            $insertStatement->close();
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

    <title>Student Registration | Poly-Health</title>

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
        href="assets/css/style.css"
    >

</head>

<body class="auth-body">

<main class="auth-page">

    <div class="container">

        <div class="auth-container">

            <section class="auth-information">

                <a href="index.php" class="auth-logo">
                    POLY-HEALTH
                </a>

                <div class="auth-information-content">

                    <span class="auth-label">
                        Student Wellbeing Platform
                    </span>

                    <h1>
                        Create your safe space.
                    </h1>

                    <p>
                        Register your account to access mental-health
                        screening, mood tracking, journaling, motivation,
                        therapy resources, and student support.
                    </p>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Private and secure student account
                    </div>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Simple mental-health screening
                    </div>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Access to wellbeing resources
                    </div>

                </div>

            </section>

            <section class="auth-form-section">

                <div class="auth-form-header">

                    <h2>Create Account</h2>

                    <p>
                        Enter your student information below.
                    </p>

                </div>

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

                <form method="POST" action="register.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape($csrfToken); ?>"
                    >

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label
                                for="student_number"
                                class="form-label"
                            >
                                Student Number
                            </label>

                            <input
                                type="text"
                                class="form-control poly-input"
                                id="student_number"
                                name="student_number"
                                maxlength="20"
                                value="<?= escape($studentNumber); ?>"
                                placeholder="Example: 18DDT22F1001"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label
                                for="fullname"
                                class="form-label"
                            >
                                Full Name
                            </label>

                            <input
                                type="text"
                                class="form-control poly-input"
                                id="fullname"
                                name="fullname"
                                maxlength="100"
                                value="<?= escape($fullname); ?>"
                                placeholder="Enter your full name"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email Address
                            </label>

                            <input
                                type="email"
                                class="form-control poly-input"
                                id="email"
                                name="email"
                                maxlength="100"
                                value="<?= escape($email); ?>"
                                placeholder="student@email.com"
                                required
                            >

                        </div>

                        <div class="col-md-6">

                            <label
                                for="phone"
                                class="form-label"
                            >
                                Phone Number
                            </label>

                            <input
                                type="tel"
                                class="form-control poly-input"
                                id="phone"
                                name="phone"
                                maxlength="20"
                                value="<?= escape($phone); ?>"
                                placeholder="Example: 0123456789"
                            >

                        </div>

                        <select
    name="course"
    id="course"
    class="form-control"
    required
>

    <option value="">
        Select Programme
    </option>

    <option value="DIT">
        DIT
    </option>

    <option value="DSK">
        DSK
    </option>

    <option value="DSB">
        DSB
    </option>

    <option value="DKA">
        DKA
    </option>

    <option value="DAS">
        DAS
    </option>

    <option value="DTK">
        DTK
    </option>

    <option value="DEP">
        DEP
    </option>

    <option value="DIB">
        DIB
    </option>

    <option value="DIF">
        DIF
    </option>

    <option value="DUP">
        DUP
    </option>

    <option value="DHF">
        DHF
    </option>

    <option value="ASASI">
        ASASI
    </option>

    <option value="FTV">
        FTV
    </option>

    <option value="LAIN-LAIN">
        LAIN-LAIN
    </option>

</select>

                        <div class="col-md-4">

                            <label
                                for="semester"
                                class="form-label"
                            >
                                Semester
                            </label>

                            <select
                                class="form-select poly-input"
                                id="semester"
                                name="semester"
                                required
                            >

                                <option value="">
                                    Select
                                </option>

                                <?php for ($number = 1; $number <= 6; $number++): ?>

                                    <option
                                        value="<?= $number; ?>"
                                        <?= (string) $semester === (string) $number
                                            ? "selected"
                                            : ""; ?>
                                    >
                                        Semester <?= $number; ?>
                                    </option>

                                <?php endfor; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label
                                for="password"
                                class="form-label"
                            >
                                Password
                            </label>

                            <div class="password-field">

                                <input
                                    type="password"
                                    class="form-control poly-input"
                                    id="password"
                                    name="password"
                                    minlength="8"
                                    placeholder="At least 8 characters"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-target="password"
                                    aria-label="Show or hide password"
                                >
                                    Show
                                </button>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <label
                                for="confirm_password"
                                class="form-label"
                            >
                                Confirm Password
                            </label>

                            <div class="password-field">

                                <input
                                    type="password"
                                    class="form-control poly-input"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    placeholder="Repeat your password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-target="confirm_password"
                                    aria-label="Show or hide password"
                                >
                                    Show
                                </button>

                            </div>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-poly auth-submit-button"
                    >
                        Create Student Account
                    </button>

                </form>

                <p class="auth-bottom-text">

                    Already registered?

                    <a href="login.php">
                        Log in here
                    </a>

                </p>

                <p class="auth-home-link">

                    <a href="index.php">
                        ← Return to homepage
                    </a>

                </p>

            </section>

        </div>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script src="assets/js/script.js"></script>

</body>
</html>