document.addEventListener("DOMContentLoaded", function () {
    const passwordButtons =
        document.querySelectorAll("[data-password-target]");

    passwordButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const targetId =
                button.getAttribute("data-password-target");

            const passwordInput =
                document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                button.textContent = "Hide";
            } else {
                passwordInput.type = "password";
                button.textContent = "Show";
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const dassForm = document.getElementById("dassForm");
    const submitButton =
        document.getElementById("screeningSubmit");

    if (!dassForm || !submitButton) {
        return;
    }

    dassForm.addEventListener("submit", function () {
        submitButton.disabled = true;
        submitButton.textContent = "Calculating Result...";
    });
});