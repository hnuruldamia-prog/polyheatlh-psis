<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../includes/config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: ../login.php");
    exit;
}

$adminId = (int) $_SESSION["admin_id"];

$adminName = $_SESSION["admin_fullname"]
    ?? "Administrator";

$adminEmail = $_SESSION["admin_email"]
    ?? "";

$adminInitial = strtoupper(
    substr(trim($adminName), 0, 1)
);