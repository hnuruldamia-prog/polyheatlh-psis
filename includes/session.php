<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Protect a student-only page.
 */
function require_student_login(): void
{
    if (!isset($_SESSION["student_id"])) {
        $_SESSION["error"] =
            "Please log in to access this page.";

        header("Location: ../login.php");
        exit;
    }
}

/**
 * Protect an admin-only page.
 */
function require_admin_login(): void
{
    if (!isset($_SESSION["admin_id"])) {
        $_SESSION["admin_error"] =
            "Please log in as an administrator.";

        header("Location: login.php");
        exit;
    }
}

/**
 * Check whether a student is logged in.
 */
function student_is_logged_in(): bool
{
    return isset($_SESSION["student_id"]);
}

/**
 * Check whether an administrator is logged in.
 */
function admin_is_logged_in(): bool
{
    return isset($_SESSION["admin_id"]);
}