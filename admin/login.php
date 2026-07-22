<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

/*
|--------------------------------------------------------------------------
| Redirect logged-in admin
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["admin_csrf_token"];

$errors = [];
$email = "";

/*
|--------------------------------------------------------------------------
| Login process
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        empty($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $errors[] = "Invalid request. Please refresh the page and try again.";
    }

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "") {
        $errors[] = "Please enter your admin email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($password === "") {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {
        $statement = $conn->prepare(
            "SELECT
                admin_id,
                fullname,
                email,
                password
             FROM admins
             WHERE email = ?
             LIMIT 1"
        );

        if (!$statement) {
            $errors[] = "Unable to process the login request.";
        } else {
            $statement->bind_param("s", $email);
            $statement->execute();

            $result = $statement->get_result();
            $admin = $result->fetch_assoc();

            $statement->close();

            if (
                $admin &&
                password_verify($password, $admin["password"])
            ) {
                session_regenerate_id(true);

                $_SESSION["admin_id"] =
                    (int) $admin["admin_id"];

                $_SESSION["admin_fullname"] =
                    $admin["fullname"];

                $_SESSION["admin_email"] =
                    $admin["email"];

                unset($_SESSION["admin_csrf_token"]);

                header("Location: dashboard.php");
                exit;
            }

            $errors[] = "Incorrect email address or password.";
        }
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

    <title>Admin Login | Poly-Health</title>

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

<body class="admin-login-body">

<main class="admin-login-page">

    <div class="container">

        <div class="admin-login-wrapper">

            <section class="admin-login-intro">

                <a
                    href="../index.php"
                    class="admin-login-logo"
                >
                    POLY-HEALTH
                </a>

                <span class="admin-login-label">
                    Administrator Portal
                </span>

                <h1>
                    Manage student wellbeing securely.
                </h1>

                <p>
                    Access student records, DASS-21 screening results,
                    reports and administrative tools from one secure
                    dashboard.
                </p>

                <div class="admin-login-feature-list">

                    <div class="admin-login-feature">

                        <span>✓</span>

                        <p>
                            View student registrations and profiles
                        </p>

                    </div>

                    <div class="admin-login-feature">

                        <span>✓</span>

                        <p>
                            Monitor DASS-21 screening results
                        </p>

                    </div>

                    <div class="admin-login-feature">

                        <span>✓</span>

                        <p>
                            Identify students requiring attention
                        </p>

                    </div>

                    <div class="admin-login-feature">

                        <span>✓</span>

                        <p>
                            Generate reports and system statistics
                        </p>

                    </div>

                </div>

                <div class="admin-login-privacy">

                    <span>🔒</span>

                    <p>
                        This portal is restricted to authorised
                        Poly-Health administrators only.
                    </p>

                </div>

            </section>

            <section class="admin-login-card">

                <div class="admin-login-card-header">

                    <div class="admin-login-icon">
                        🛡️
                    </div>

                    <span class="section-label">
                        Secure Access
                    </span>

                    <h2>Admin Login</h2>

                    <p>
                        Enter your registered administrator credentials.
                    </p>

                </div>

                <?php if (!empty($errors)): ?>

                    <div
                        class="alert alert-danger admin-login-alert"
                        role="alert"
                    >

                        <strong>Login unsuccessful</strong>

                        <ul class="mb-0 mt-2">

                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?= htmlspecialchars(
                                        $error,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>

                <form
                    method="POST"
                    action="login.php"
                    class="admin-login-form"
                    novalidate
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                    >

                    <div class="mb-4">

                        <label
                            for="email"
                            class="form-label"
                        >
                            Admin Email
                        </label>

                        <div class="admin-login-input-group">

                            <span class="admin-login-input-icon">
                                ✉️
                            </span>

                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                maxlength="150"
                                value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                placeholder="admin@polyhealth.com"
                                autocomplete="email"
                                required
                            >

                        </div>

                    </div>

                    <div class="mb-4">

                        <label
                            for="password"
                            class="form-label"
                        >
                            Password
                        </label>

                        <div class="admin-login-input-group">

                            <span class="admin-login-input-icon">
                                🔑
                            </span>

                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="admin-login-password-toggle"
                                id="toggleAdminPassword"
                                aria-label="Show password"
                            >
                                Show
                            </button>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-poly admin-login-button"
                    >
                        Login to Admin Dashboard
                    </button>

                </form>

                <div class="admin-login-help">

                    <p>
                        Student account?
                    </p>

                    <a href="../login.php">
                        Go to student login
                    </a>

                </div>

            </section>

        </div>

    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const passwordInput =
        document.getElementById("password");

    const toggleButton =
        document.getElementById("toggleAdminPassword");

    if (!passwordInput || !toggleButton) {
        return;
    }

    toggleButton.addEventListener("click", function () {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleButton.textContent = "Hide";
            toggleButton.setAttribute(
                "aria-label",
                "Hide password"
            );
        } else {
            passwordInput.type = "password";
            toggleButton.textContent = "Show";
            toggleButton.setAttribute(
                "aria-label",
                "Show password"
            );
        }
    });
});
</script>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>