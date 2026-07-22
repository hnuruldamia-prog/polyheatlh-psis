<?php

$pageTitle = $pageTitle ?? "Administrator Portal";
$topbarTitle = $topbarTitle ?? $pageTitle;

$adminName = $adminName ?? "Administrator";
$adminInitial = $adminInitial ?? "A";



$topbarTitle = $topbarTitle ?? $pageTitle;

?>

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

            <strong>
                <?= admin_escape($topbarTitle); ?>
            </strong>

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