<?php

session_start();

require_once __DIR__ . "/../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminId = (int) $_SESSION["admin_id"];

function admin_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| CSRF protection
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["profile_csrf_token"])) {
    $_SESSION["profile_csrf_token"] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION["profile_csrf_token"];

/*
|--------------------------------------------------------------------------
| Flash messages
|--------------------------------------------------------------------------
*/

$successMessage = $_SESSION["profile_success"] ?? "";
$errorMessage = $_SESSION["profile_error"] ?? "";

unset(
    $_SESSION["profile_success"],
    $_SESSION["profile_error"]
);

/*
|--------------------------------------------------------------------------
| Retrieve admin account
|--------------------------------------------------------------------------
*/

$adminStatement = $conn->prepare(
    "SELECT
        admin_id,
        fullname,
        email,
        password,
        created_at
     FROM admins
     WHERE admin_id = ?
     LIMIT 1"
);

if (!$adminStatement) {
    die("Unable to retrieve administrator account.");
}

$adminStatement->bind_param("i", $adminId);
$adminStatement->execute();

$adminResult = $adminStatement->get_result();
$admin = $adminResult->fetch_assoc();

$adminStatement->close();

if (!$admin) {
    session_destroy();

    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Update profile
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $_SESSION["profile_error"] =
            "Invalid request. Please try again.";

        header("Location: profile.php");
        exit;
    }

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $currentPassword = $_POST["current_password"] ?? "";
    $newPassword = $_POST["new_password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $errors = [];

    if ($fullname === "") {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($fullname) > 100) {
        $errors[] = "Full name must not exceed 100 characters.";
    }

    if ($email === "") {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    /*
    |--------------------------------------------------------------------------
    | Check whether email is already used
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        $emailStatement = $conn->prepare(
            "SELECT admin_id
             FROM admins
             WHERE email = ?
             AND admin_id != ?
             LIMIT 1"
        );

        if ($emailStatement) {
            $emailStatement->bind_param(
                "si",
                $email,
                $adminId
            );

            $emailStatement->execute();

            $emailResult = $emailStatement->get_result();

            if ($emailResult->num_rows > 0) {
                $errors[] =
                    "This email address is already used by another administrator.";
            }

            $emailStatement->close();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Password validation
    |--------------------------------------------------------------------------
    */

    $changingPassword =
        $currentPassword !== "" ||
        $newPassword !== "" ||
        $confirmPassword !== "";

    if ($changingPassword) {
        if ($currentPassword === "") {
            $errors[] =
                "Enter your current password to create a new password.";
        } elseif (
            !password_verify(
                $currentPassword,
                $admin["password"]
            )
        ) {
            $errors[] = "The current password is incorrect.";
        }

        if ($newPassword === "") {
            $errors[] = "Enter a new password.";
        } elseif (strlen($newPassword) < 8) {
            $errors[] =
                "The new password must contain at least 8 characters.";
        }

        if ($confirmPassword === "") {
            $errors[] = "Confirm your new password.";
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
                "The new password must be different from the current password.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save profile
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {
        if ($changingPassword) {
            $hashedPassword = password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

            $updateStatement = $conn->prepare(
                "UPDATE admins
                 SET
                    fullname = ?,
                    email = ?,
                    password = ?
                 WHERE admin_id = ?"
            );

            if ($updateStatement) {
                $updateStatement->bind_param(
                    "sssi",
                    $fullname,
                    $email,
                    $hashedPassword,
                    $adminId
                );
            }
        } else {
            $updateStatement = $conn->prepare(
                "UPDATE admins
                 SET
                    fullname = ?,
                    email = ?
                 WHERE admin_id = ?"
            );

            if ($updateStatement) {
                $updateStatement->bind_param(
                    "ssi",
                    $fullname,
                    $email,
                    $adminId
                );
            }
        }

        if (
            isset($updateStatement) &&
            $updateStatement &&
            $updateStatement->execute()
        ) {
            $_SESSION["admin_fullname"] = $fullname;
            $_SESSION["admin_email"] = $email;

            $_SESSION["profile_success"] =
                $changingPassword
                    ? "Profile and password updated successfully."
                    : "Profile updated successfully.";

            $updateStatement->close();

            header("Location: profile.php");
            exit;
        }

        if (isset($updateStatement) && $updateStatement) {
            $updateStatement->close();
        }

        $errors[] =
            "Unable to update your profile. Please try again.";
    }

    if (!empty($errors)) {
        $_SESSION["profile_error"] = implode(
            " ",
            $errors
        );

        header("Location: profile.php");
        exit;
    }
}

$adminName = $_SESSION["admin_fullname"] ??
    $admin["fullname"];

$adminEmail = $_SESSION["admin_email"] ??
    $admin["email"];

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
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

    <title>Admin Profile | Poly-Health</title>

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

<body class="admin-dashboard-body">

<div class="admin-dashboard-layout">

    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >

        <div class="admin-sidebar-header">

            <a
                href="dashboard.php"
                class="admin-sidebar-logo"
            >
                <span class="admin-sidebar-logo-icon">
                    +
                </span>

                <span>POLY-HEALTH</span>
            </a>

            <button
                type="button"
                class="admin-sidebar-close"
                id="closeAdminSidebar"
                aria-label="Close sidebar"
            >
                ×
            </button>

        </div>

        <div class="admin-sidebar-profile">

            <div class="admin-sidebar-avatar">
                <?= admin_escape($adminInitial); ?>
            </div>

            <div>

                <strong>
                    <?= admin_escape($adminName); ?>
                </strong>

                <small>System Administrator</small>

            </div>

        </div>

        <nav class="admin-sidebar-nav">

            <span class="admin-nav-label">
                Main Menu
            </span>

            <a
                href="dashboard.php"
                class="admin-nav-link"
            >
                <span>🏠</span>
                Dashboard
            </a>

            <a
                href="students.php"
                class="admin-nav-link"
            >
                <span>🎓</span>
                Manage Students
            </a>

            <a
                href="screening_results.php"
                class="admin-nav-link"
            >
                <span>📊</span>
                DASS-21 Results
            </a>

            <a
                href="journals.php"
                class="admin-nav-link"
            >
                <span>📖</span>
                Journal Entries
            </a>

            <a
                href="reports.php"
                class="admin-nav-link"
            >
                <span>📈</span>
                Reports
            </a>

            <span class="admin-nav-label admin-nav-label-second">
                Account
            </span>

            <a
                href="profile.php"
                class="admin-nav-link active"
            >
                <span>👤</span>
                My Profile
            </a>

        

            <a
                href="logout.php"
                class="admin-nav-link admin-logout-link"
            >
                <span>🚪</span>
                Logout
            </a>

        </nav>

        <div class="admin-sidebar-footer">

            <span>🔒</span>

            <p>
                Keep your administrator password secure and private.
            </p>

        </div>

    </aside>

    <div
        class="admin-sidebar-overlay"
        id="adminSidebarOverlay"
    ></div>

    <div class="admin-dashboard-main">

        <header class="admin-topbar">

            <button
                type="button"
                class="admin-menu-button"
                id="openAdminSidebar"
                aria-label="Open sidebar"
            >
                ☰
            </button>

            <div class="admin-topbar-title">

                <span>Administrator Portal</span>

                <strong>My Profile</strong>

            </div>

            <div class="admin-topbar-account">

                <div class="admin-topbar-avatar">
                    <?= admin_escape($adminInitial); ?>
                </div>

                <div class="admin-topbar-user">

                    <strong>
                        <?= admin_escape($adminName); ?>
                    </strong>

                    <small>Administrator</small>

                </div>

                <a
                    href="logout.php"
                    class="btn admin-topbar-logout"
                >
                    Logout
                </a>

            </div>

        </header>

        <main class="admin-dashboard-content">

            <section class="admin-page-header">

                <div>

                    <span class="admin-section-label">
                        Account Management
                    </span>

                    <h1>Administrator Profile</h1>

                    <p>
                        Update your personal information and secure
                        your administrator account.
                    </p>

                </div>

                <a
                    href="dashboard.php"
                    class="btn admin-secondary-button"
                >
                    ← Dashboard
                </a>

            </section>

            <?php if ($successMessage !== ""): ?>

                <div
                    class="alert alert-success admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($successMessage); ?>
                </div>

            <?php endif; ?>

            <?php if ($errorMessage !== ""): ?>

                <div
                    class="alert alert-danger admin-page-alert"
                    role="alert"
                >
                    <?= admin_escape($errorMessage); ?>
                </div>

            <?php endif; ?>

            <section class="admin-profile-page-grid">

                <aside class="admin-profile-summary-card">

                    <div class="admin-profile-large-avatar">
                        <?= admin_escape($adminInitial); ?>
                    </div>

                    <h2>
                        <?= admin_escape($adminName); ?>
                    </h2>

                    <p>
                        <?= admin_escape($adminEmail); ?>
                    </p>

                    <span class="admin-profile-role">
                        System Administrator
                    </span>

                    <div class="admin-profile-summary-list">

                        <div>

                            <span>Account ID</span>

                            <strong>
                                #<?= number_format($adminId); ?>
                            </strong>

                        </div>

                        <div>

                            <span>Account Created</span>

                            <strong>
                                <?= !empty($admin["created_at"])
                                    ? date(
                                        "d M Y",
                                        strtotime(
                                            $admin["created_at"]
                                        )
                                    )
                                    : "Not available"; ?>
                            </strong>

                        </div>

                        <div>

                            <span>Account Status</span>

                            <strong class="admin-profile-active">
                                Active
                            </strong>

                        </div>

                    </div>

                    <div class="admin-profile-security-note">

                        <span>🔐</span>

                        <p>
                            Use a strong password and avoid sharing your
                            administrator login with other users.
                        </p>

                    </div>

                </aside>

                <section class="admin-profile-form-card">

                    <div class="admin-profile-form-heading">

                        <span>👤</span>

                        <div>

                            <small>Account Settings</small>

                            <h2>Edit Profile</h2>

                        </div>

                    </div>

                    <form
                        method="POST"
                        action="profile.php"
                        class="admin-profile-form"
                        autocomplete="off"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= admin_escape(
                                $csrfToken
                            ); ?>"
                        >

                        <div class="admin-profile-form-section">

                            <h3>Personal Information</h3>

                            <p>
                                Update the name and email displayed in
                                the administrator portal.
                            </p>

                            <div class="admin-profile-form-grid">

                                <div>

                                    <label for="fullname">
                                        Full Name
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="fullname"
                                        name="fullname"
                                        value="<?= admin_escape(
                                            $admin["fullname"]
                                        ); ?>"
                                        maxlength="100"
                                        required
                                    >

                                </div>

                                <div>

                                    <label for="email">
                                        Email Address
                                    </label>

                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="<?= admin_escape(
                                            $admin["email"]
                                        ); ?>"
                                        maxlength="150"
                                        required
                                    >

                                </div>

                            </div>

                        </div>

                        <div class="admin-profile-form-section">

                            <h3>Change Password</h3>

                            <p>
                                Leave all password fields empty when you
                                do not want to change your password.
                            </p>

                            <div class="admin-profile-form-grid">

                                <div class="admin-profile-full-field">

                                    <label for="current_password">
                                        Current Password
                                    </label>

                                    <div class="admin-password-field">

                                        <input
                                            type="password"
                                            class="form-control"
                                            id="current_password"
                                            name="current_password"
                                            autocomplete="current-password"
                                        >

                                        <button
                                            type="button"
                                            class="admin-password-toggle"
                                            data-password-target="current_password"
                                            aria-label="Show current password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>

                                <div>

                                    <label for="new_password">
                                        New Password
                                    </label>

                                    <div class="admin-password-field">

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
                                            class="admin-password-toggle"
                                            data-password-target="new_password"
                                            aria-label="Show new password"
                                        >
                                            Show
                                        </button>

                                    </div>

                                    <small class="admin-field-help">
                                        At least 8 characters.
                                    </small>

                                </div>

                                <div>

                                    <label for="confirm_password">
                                        Confirm New Password
                                    </label>

                                    <div class="admin-password-field">

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
                                            class="admin-password-toggle"
                                            data-password-target="confirm_password"
                                            aria-label="Show password confirmation"
                                        >
                                            Show
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="admin-profile-form-actions">

                            <a
                                href="dashboard.php"
                                class="btn admin-filter-reset"
                            >
                                Cancel
                            </a>

                            <button
                                type="submit"
                                name="update_profile"
                                class="btn btn-poly"
                            >
                                Save Changes
                            </button>

                        </div>

                    </form>

                </section>

            </section>

        </main>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById(
        "adminSidebarOverlay"
    );
    const openButton = document.getElementById(
        "openAdminSidebar"
    );
    const closeButton = document.getElementById(
        "closeAdminSidebar"
    );

    function openSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.add("show");
        overlay.classList.add("show");
        document.body.classList.add("admin-sidebar-open");
    }

    function closeSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove("show");
        overlay.classList.remove("show");
        document.body.classList.remove("admin-sidebar-open");
    }

    if (openButton) {
        openButton.addEventListener("click", openSidebar);
    }

    if (closeButton) {
        closeButton.addEventListener("click", closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    const passwordButtons = document.querySelectorAll(
        ".admin-password-toggle"
    );

    passwordButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId = button.getAttribute(
                "data-password-target"
            );

            const passwordInput = document.getElementById(
                targetId
            );

            if (!passwordInput) {
                return;
            }

            const isHidden =
                passwordInput.type === "password";

            passwordInput.type =
                isHidden ? "text" : "password";

            button.textContent =
                isHidden ? "Hide" : "Show";
        });
    });
});
</script>

</body>
</html>