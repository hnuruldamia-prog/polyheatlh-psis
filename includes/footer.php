<footer class="poly-footer mt-auto">

    <div class="container">

        <div class="footer-content">

            <div>

                <strong>POLY-HEALTH</strong>

                <p>
                    Smart Mental Health Screening and Support
                </p>

            </div>

            <div>

                <p>
                    &copy; <?= date("Y"); ?>
                    Poly-Health. All rights reserved.
                </p>

                <a
                    href="<?= isset($assetPrefix)
                        ? escape($assetPrefix)
                        : ""; ?>admin/login.php"
                    class="admin-footer-link"
                >
                    Admin Login
                </a>

            </div>

        </div>

    </div>

</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="<?= isset($assetPrefix)
        ? escape($assetPrefix)
        : ""; ?>assets/js/script.js"
></script>

</body>
</html>