<?php

require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/functions.php";

if (student_is_logged_in()) {
    header("Location: student/dashboard.php");
    exit;
}

$studentNumber = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] =
            "Invalid form request. Please refresh the page and try again.";
    }

    $studentNumber = trim(
        $_POST["student_number"] ?? ""
    );

    $password = $_POST["password"] ?? "";

    if ($studentNumber === "") {
        $errors[] = "Student number is required.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {

        $statement = $conn->prepare(
            "SELECT
                student_id,
                student_number,
                fullname,
                email,
                password
             FROM students
             WHERE student_number = ?
             LIMIT 1"
        );

        if (!$statement) {
            $errors[] =
                "Unable to process your login. Please try again.";
        } else {

            $statement->bind_param(
                "s",
                $studentNumber
            );

            $statement->execute();

            $result = $statement->get_result();

            $student = $result->fetch_assoc();

            $statement->close();

            if (
                $student &&
                password_verify(
                    $password,
                    $student["password"]
                )
            ) {

                session_regenerate_id(true);

                $_SESSION["student_id"] =
                    (int) $student["student_id"];

                $_SESSION["student_number"] =
                    $student["student_number"];

                $_SESSION["student_name"] =
                    $student["fullname"];

                $_SESSION["student_email"] =
                    $student["email"];

                unset($_SESSION["csrf_token"]);

                set_flash_message(
                    "success",
                    "Welcome back, "
                    . $student["fullname"]
                    . "."
                );

                header(
                    "Location: student/dashboard.php"
                );
                exit;
            }

            $errors[] =
                "Incorrect student number or password.";
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

    <title>Student Login | Poly-Health</title>

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

        <div class="auth-container login-container">

            <section class="auth-information">

                <a href="index.php" class="auth-logo">
                    POLY-HEALTH
                </a>

                <div class="auth-information-content">

                    <span class="auth-label">
                        Welcome Back
                    </span>

                    <h1>
                        Your wellbeing journey continues here.
                    </h1>

                    <p>
                        Log in to complete your mental-health screening,
                        record your mood, write in your private journal,
                        and access helpful support resources.
                    </p>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Secure student account
                    </div>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Private screening results
                    </div>

                    <div class="auth-benefit">
                        <span>✓</span>
                        Wellbeing tools and support
                    </div>

                </div>

            </section>

            <section class="auth-form-section login-form-section">

                <div class="auth-form-header">

                    <h2>Student Login</h2>

                    <p>
                        Enter your student number and password.
                    </p>

                </div>

                <?php display_flash_message(); ?>

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">

                        <strong>
                            Login unsuccessful
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

                <form method="POST" action="login.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape($csrfToken); ?>"
                    >

                    <div class="mb-3">

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
                            autocomplete="username"
                            required
                            autofocus
                        >

                    </div>

                    <div class="mb-3">

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
                                placeholder="Enter your password"
                                autocomplete="current-password"
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

                    <button
                        type="submit"
                        class="btn btn-poly auth-submit-button"
                    >
                        Log In
                    </button>

                </form>

                <p class="auth-bottom-text">

                    Do not have an account?

                    <a href="register.php">
                        Register here
                    </a>

                </p>

                <p class="auth-home-link">

                    <a href="index.php">
                        ← Return to homepage
                    </a>

                </p>

                <div class="student-help-note">

                    <strong>Need assistance?</strong>

                    <p>
                        Contact UPPSIK if you cannot access your account.
                    </p>

                </div>

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