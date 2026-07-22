<?php

$adminName = $adminName ?? "Administrator";
$adminInitial = $adminInitial ?? "A";

$currentPage = basename($_SERVER["PHP_SELF"]);

function admin_nav_active(string $page, string $currentPage): string
{
    return $page === $currentPage ? "active" : "";
}

?>



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

            <small>
                System Administrator
            </small>

        </div>

    </div>

    <nav class="admin-sidebar-nav">

        <span class="admin-nav-label">
            Main Menu
        </span>

        <a
            href="dashboard.php"
            class="admin-nav-link <?= admin_nav_active(
                "dashboard.php",
                $currentPage
            ); ?>"
        >
            <span>🏠</span>
            Dashboard
        </a>

        <a
            href="students.php"
            class="admin-nav-link <?= admin_nav_active(
                "students.php",
                $currentPage
            ); ?>"
        >
            <span>🎓</span>
            Manage Students
        </a>

        <a
            href="screening_results.php"
            class="admin-nav-link <?= admin_nav_active(
                "screening_results.php",
                $currentPage
            ); ?>"
        >
            <span>📊</span>
            DASS-21 Results
        </a>

        <a
            href="journals.php"
            class="admin-nav-link <?= admin_nav_active(
                "journals.php",
                $currentPage
            ); ?>"
        >
            <span>📖</span>
            Journal Entries
        </a>

        <a
            href="reports.php"
            class="admin-nav-link <?= admin_nav_active(
                "reports.php",
                $currentPage
            ); ?>"
        >
            <span>📈</span>
            Reports
        </a>

        <span class="admin-nav-label admin-nav-label-second">
            Account
        </span>

        <a
            href="profile.php"
            class="admin-nav-link <?= admin_nav_active(
                "profile.php",
                $currentPage
            ); ?>"
        >
            <span>👤</span>
            My Profile
        </a>

        <a
            href="../index.php"
            class="admin-nav-link"
            target="_blank"
        >
            <span>🌐</span>
            View Website
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
            Student wellbeing information is confidential.
        </p>

    </div>

</aside>

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>