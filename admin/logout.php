<?php

session_start();

unset(
    $_SESSION["admin_id"],
    $_SESSION["admin_fullname"],
    $_SESSION["admin_email"],
    $_SESSION["admin_csrf_token"]
);

session_regenerate_id(true);

header("Location: login.php");
exit;