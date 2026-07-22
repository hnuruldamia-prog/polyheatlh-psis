<?php

require_once __DIR__ . "/../includes/session.php";

$_SESSION = [];

if (ini_get("session.use_cookies")) {

    $cookieParameters =
        session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $cookieParameters["path"],
        $cookieParameters["domain"],
        $cookieParameters["secure"],
        $cookieParameters["httponly"]
    );
}

session_destroy();

session_start();

$_SESSION["flash"] = [
    "type" => "success",
    "message" => "You have logged out successfully."
];

header("Location: ../login.php");
exit;