</main>

</div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById(
        "adminSidebar"
    );

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

        document.body.classList.add(
            "admin-sidebar-open"
        );
    }

    function closeSidebar() {
        if (!sidebar || !overlay) {
            return;
        }

        sidebar.classList.remove("show");
        overlay.classList.remove("show");

        document.body.classList.remove(
            "admin-sidebar-open"
        );
    }

    if (openButton) {
        openButton.addEventListener(
            "click",
            openSidebar
        );
    }

    if (closeButton) {
        closeButton.addEventListener(
            "click",
            closeSidebar
        );
    }

    if (overlay) {
        overlay.addEventListener(
            "click",
            closeSidebar
        );
    }
});
</script>

<?php if (!empty($additionalScripts)): ?>

    <?= $additionalScripts; ?>

<?php endif; ?>

</body>
</html>