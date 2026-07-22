<?php

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/functions.php";

require_student_login();

$studentId = (int) $_SESSION["student_id"];
$errors = [];

/*
|--------------------------------------------------------------------------
| Load student information
|--------------------------------------------------------------------------
*/

$studentStatement = $conn->prepare(
    "SELECT
        student_id,
        student_number,
        fullname,
        email,
        phone,
        course,
        semester,
        password,
        created_at
     FROM students
     WHERE student_id = ?
     LIMIT 1"
);

if (!$studentStatement) {
    die("Unable to load student profile.");
}

$studentStatement->bind_param("i", $studentId);
$studentStatement->execute();

$student = $studentStatement
    ->get_result()
    ->fetch_assoc();

$studentStatement->close();

if (!$student) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Default form values
|--------------------------------------------------------------------------
*/

$fullname = $student["fullname"];
$email = $student["email"];
$phone = $student["phone"] ?? "";
$course = $student["course"] ?? "";
$semester = $student["semester"] ?? "";

/*
|--------------------------------------------------------------------------
| Update profile
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!verify_csrf_token($csrfToken)) {
        $errors[] =
            "Invalid request. Please refresh the page and try again.";
    }

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $semester = trim($_POST["semester"] ?? "");

    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | Personal information validation
    |--------------------------------------------------------------------------
    */

    if ($fullname === "") {
        $errors[] = "Please enter your full name.";
    } elseif (strlen($fullname) > 150) {
        $errors[] =
            "Your full name must not exceed 150 characters.";
    }

    if ($email === "") {
        $errors[] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (strlen($email) > 150) {
        $errors[] =
            "Your email address must not exceed 150 characters.";
    }

    if ($phone !== "" && strlen($phone) > 30) {
        $errors[] =
            "Your telephone number must not exceed 30 characters.";
    }

    if ($course !== "" && strlen($course) > 150) {
        $errors[] =
            "Your course name must not exceed 150 characters.";
    }

    if ($semester !== "" && strlen($semester) > 30) {
        $errors[] =
            "Your semester must not exceed 30 characters.";
    }

    /*
    |--------------------------------------------------------------------------
    | Check whether email is already used
    |--------------------------------------------------------------------------
    */

    if (
        $email !== "" &&
        filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $emailStatement = $conn->prepare(
            "SELECT student_id
             FROM students
             WHERE email = ?
             AND student_id != ?
             LIMIT 1"
        );

        if ($emailStatement) {
            $emailStatement->bind_param(
                "si",
                $email,
                $studentId
            );

            $emailStatement->execute();

            $existingEmail = $emailStatement
                ->get_result()
                ->fetch_assoc();

            $emailStatement->close();

            if ($existingEmail) {
                $errors[] =
                    "This email address is already registered.";
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Password validation
    |--------------------------------------------------------------------------
    | Password fields may be left empty when the student does not want
    | to change the password.
    */

    $passwordChangeRequested =
        $currentPassword !== "" ||
        $newPassword !== "" ||
        $confirmPassword !== "";

    if ($passwordChangeRequested) {
        if ($currentPassword === "") {
            $errors[] =
                "Please enter your current password.";
        } elseif (
            !password_verify(
                $currentPassword,
                $student["password"]
            )
        ) {
            $errors[] =
                "Your current password is incorrect.";
        }

        if ($newPassword === "") {
            $errors[] =
                "Please enter a new password.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] =
                "Your new password must contain at least 8 characters.";
        }

        if ($confirmPassword === "") {
            $errors[] =
                "Please confirm your new password.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] =
                "The new password confirmation does not match.";
        }

        if (
            $currentPassword !== "" &&
            $newPassword !== "" &&
            $currentPassword === $newPassword
        ) {
            $errors[] =
                "Your new password must be different from your current password.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save profile
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        if ($passwordChangeRequested) {
            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $updateStatement = $conn->prepare(
                "UPDATE students
                 SET
                    fullname = ?,
                    email = ?,
                    phone = ?,
                    course = ?,
                    semester = ?,
                    password = ?
                 WHERE student_id = ?"
            );

            if ($updateStatement) {
                $updateStatement->bind_param(
                    "ssssssi",
                    $fullname,
                    $email,
                    $phone,
                    $course,
                    $semester,
                    $hashedPassword,
                    $studentId
                );
            }
        } else {
            $updateStatement = $conn->prepare(
                "UPDATE students
                 SET
                    fullname = ?,
                    email = ?,
                    phone = ?,
                    course = ?,
                    semester = ?
                 WHERE student_id = ?"
            );

            if ($updateStatement) {
                $updateStatement->bind_param(
                    "sssssi",
                    $fullname,
                    $email,
                    $phone,
                    $course,
                    $semester,
                    $studentId
                );
            }
        }

        if (!$updateStatement) {
            $errors[] =
                "Unable to prepare the profile update.";
        } elseif ($updateStatement->execute()) {
            $updateStatement->close();

            $_SESSION["fullname"] = $fullname;
            $_SESSION["email"] = $email;

            set_flash_message(
                "success",
                "Your profile was updated successfully."
            );

            header("Location: profile.php");
            exit;
        } else {
            $updateStatement->close();

            $errors[] =
                "Your profile could not be updated. Please try again.";
        }
    }
}

$csrfToken = generate_csrf_token();

$memberSince = date(
    "d F Y",
    strtotime($student["created_at"])
);

$initial = strtoupper(
    substr(trim($fullname), 0, 1)
);
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Profile | Poly-Health</title>

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
                href="logout.php"
                class="btn btn-poly btn-sm rounded-pill px-3"
            >
                Logout
            </a>

        </div>

    </div>

</nav>

<main class="profile-page">

    <div class="container">

        <?php display_flash_message(); ?>

        <section class="profile-header">

            <div>

                <span class="section-label">
                    Student Account
                </span>

                <h1>My Profile</h1>

                <p>
                    Manage your personal information and account security.
                </p>

            </div>

        </section>

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger profile-alert">

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

        <div class="profile-layout">

            <!-- Profile summary -->
            <aside class="profile-summary-card">

                <div class="profile-avatar">

                    <?= escape($initial); ?>

                </div>

                <h2>
                    <?= escape($fullname); ?>
                </h2>

                <p>
                    <?= escape($student["student_number"]); ?>
                </p>

                <div class="profile-summary-divider"></div>

                <div class="profile-summary-item">

                    <small>Email</small>

                    <strong>
                        <?= escape($email); ?>
                    </strong>

                </div>

                <div class="profile-summary-item">

                    <small>Course</small>

                    <strong>
                        <?= $course !== ""
                            ? escape($course)
                            : "Not provided"; ?>
                    </strong>

                </div>

                <div class="profile-summary-item">

                    <small>Semester</small>

                    <strong>
                        <?= $semester !== ""
                            ? escape($semester)
                            : "Not provided"; ?>
                    </strong>

                </div>

                <div class="profile-summary-item">

                    <small>Member since</small>

                    <strong>
                        <?= escape($memberSince); ?>
                    </strong>

                </div>

                <div class="profile-privacy-note">

                    <span>🔒</span>

                    <p>
                        Your profile information is linked securely to
                        your Poly-Health student account.
                    </p>

                </div>

            </aside>

            <!-- Profile form -->
            <section class="profile-form-card">

                <form
                    method="POST"
                    action="profile.php"
                    novalidate
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape($csrfToken); ?>"
                    >

                    <div class="profile-form-section">

                        <div class="profile-form-heading">

                            <span>👤</span>

                            <div>

                                <h2>Personal Information</h2>

                                <p>
                                    Update your student contact and
                                    academic information.
                                </p>

                            </div>

                        </div>

                        <div class="row g-4">

                            <div class="col-12">

                                <label
                                    for="student_number"
                                    class="form-label"
                                >
                                    Student Number
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="student_number"
                                    value="<?=
                                        escape(
                                            $student["student_number"]
                                        );
                                    ?>"
                                    disabled
                                >

                                <div class="form-text">
                                    Your student number cannot be changed.
                                </div>

                            </div>

                            <div class="col-12">

                                <label
                                    for="fullname"
                                    class="form-label"
                                >
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="fullname"
                                    name="fullname"
                                    maxlength="150"
                                    value="<?= escape($fullname); ?>"
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
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                                    value="<?= escape($email); ?>"
                                    required
                                >

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="phone"
                                    class="form-label"
                                >
                                    Telephone Number
                                </label>

                                <input
                                    type="tel"
                                    class="form-control"
                                    id="phone"
                                    name="phone"
                                    maxlength="30"
                                    value="<?= escape($phone); ?>"
                                    placeholder="Example: 012-345 6789"
                                >

                            </div>

                            <div class="col-md-8">

                                <label
                                    for="course"
                                    class="form-label"
                                >
                                    Course
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="course"
                                    name="course"
                                    maxlength="150"
                                    value="<?= escape($course); ?>"
                                    placeholder="Example: Diploma in Information Technology"
                                >

                            </div>

                            <div class="col-md-4">

                                <label
                                    for="semester"
                                    class="form-label"
                                >
                                    Semester
                                </label>

                                <select
                                    class="form-select"
                                    id="semester"
                                    name="semester"
                                >

                                    <option value="">
                                        Select semester
                                    </option>

                                    <?php for ($number = 1; $number <= 6; $number++): ?>

                                        <?php
                                        $semesterValue =
                                            "Semester " . $number;
                                        ?>

                                        <option
                                            value="<?=
                                                escape(
                                                    $semesterValue
                                                );
                                            ?>"
                                            <?= $semester === $semesterValue
                                                ? "selected"
                                                : ""; ?>
                                        >
                                            <?= escape(
                                                $semesterValue
                                            ); ?>
                                        </option>

                                    <?php endfor; ?>

                                </select>

                            </div>

                        </div>

                    </div>

                    <div class="profile-form-divider"></div>

                    <div class="profile-form-section">

                        <div class="profile-form-heading">

                            <span>🔐</span>

                            <div>

                                <h2>Change Password</h2>

                                <p>
                                    Leave these fields empty when you do
                                    not want to change your password.
                                </p>

                            </div>

                        </div>

                        <div class="row g-4">

                            <div class="col-12">

                                <label
                                    for="current_password"
                                    class="form-label"
                                >
                                    Current Password
                                </label>

                                <div class="profile-password-field">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="current_password"
                                        name="current_password"
                                        autocomplete="current-password"
                                    >

                                    <button
                                        type="button"
                                        class="profile-password-toggle"
                                        data-password-target="current_password"
                                        aria-label="Show current password"
                                    >
                                        Show
                                    </button>

                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="new_password"
                                    class="form-label"
                                >
                                    New Password
                                </label>

                                <div class="profile-password-field">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="new_password"
                                        name="new_password"
                                        minlength="8"
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="profile-password-toggle"
                                        data-password-target="new_password"
                                        aria-label="Show new password"
                                    >
                                        Show
                                    </button>

                                </div>

                                <div class="form-text">
                                    Use at least 8 characters.
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label
                                    for="confirm_password"
                                    class="form-label"
                                >
                                    Confirm New Password
                                </label>

                                <div class="profile-password-field">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="confirm_password"
                                        name="confirm_password"
                                        minlength="8"
                                        autocomplete="new-password"
                                    >

                                    <button
                                        type="button"
                                        class="profile-password-toggle"
                                        data-password-target="confirm_password"
                                        aria-label="Show password confirmation"
                                    >
                                        Show
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="profile-form-actions">

                        <a
                            href="dashboard.php"
                            class="btn btn-outline-secondary rounded-pill px-4"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-poly rounded-pill px-4"
                        >
                            Save Profile
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </div>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleButtons = document.querySelectorAll(
        ".profile-password-toggle"
    );

    toggleButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.getAttribute(
                "data-password-target"
            );

            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                button.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                button.textContent = "Show";
            }
        });
    });
});
</script>

<script src="../assets/js/script.js"></script>

</body>
</html>