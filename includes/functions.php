<?php

/**
 * Safely display data inside HTML.
 */
function escape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/**
 * Remove unnecessary whitespace.
 */
function clean_input(string $value): string
{
    return trim($value);
}

/**
 * Redirect the user.
 */
function redirect(string $location): never
{
    header("Location: " . $location);
    exit;
}

/**
 * Check whether the current request is POST.
 */
function is_post_request(): bool
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}

/**
 * Store a temporary message in the session.
 */
function set_flash_message(
    string $type,
    string $message
): void {
    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message
    ];
}

/**
 * Display and remove the current flash message.
 */
function display_flash_message(): void
{
    if (!isset($_SESSION["flash"])) {
        return;
    }

    $flash = $_SESSION["flash"];

    $allowedTypes = [
        "success",
        "danger",
        "warning",
        "info"
    ];

    $type = in_array(
        $flash["type"],
        $allowedTypes,
        true
    )
        ? $flash["type"]
        : "info";

    echo '<div class="alert alert-'
        . escape($type)
        . ' alert-dismissible fade show" role="alert">';

    echo escape($flash["message"]);

    echo '
        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
            aria-label="Close">
        </button>
    ';

    echo "</div>";

    unset($_SESSION["flash"]);
}

/**
 * Generate a CSRF security token.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] =
            bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

/**
 * Validate a submitted CSRF token.
 */
function verify_csrf_token(
    ?string $submittedToken
): bool {
    if (
        empty($_SESSION["csrf_token"]) ||
        empty($submittedToken)
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $submittedToken
    );
}

/**
 * Display the PHQ-9 severity based on score.
 */
/**
 * Return the DASS-21 severity level.
 *
 * The score supplied to this function must already
 * be multiplied by two.
 */
function get_dass_severity(
    string $category,
    int $score
): string {
    $category = strtoupper($category);

    if ($category === "D") {
        if ($score <= 9) {
            return "Normal";
        }

        if ($score <= 13) {
            return "Ringan";
        }

        if ($score <= 20) {
            return "Sederhana";
        }

        if ($score <= 27) {
            return "Teruk";
        }

        return "Sangat Teruk";
    }

    if ($category === "A") {
        if ($score <= 7) {
            return "Normal";
        }

        if ($score <= 9) {
            return "Ringan";
        }

        if ($score <= 14) {
            return "Sederhana";
        }

        if ($score <= 19) {
            return "Teruk";
        }

        return "Sangat Teruk";
    }

    if ($category === "S") {
        if ($score <= 14) {
            return "Normal";
        }

        if ($score <= 18) {
            return "Ringan";
        }

        if ($score <= 25) {
            return "Sederhana";
        }

        if ($score <= 33) {
            return "Teruk";
        }

        return "Sangat Teruk";
    }

    return "Tidak Diketahui";
}

/**
 * Check whether the result requires UPPSIK attention.
 */
function dass_requires_attention(
    string $depressionLevel,
    string $anxietyLevel,
    string $stressLevel
): bool {
    $attentionLevels = [
        "Teruk",
        "Sangat Teruk"
    ];

    return
        in_array(
            $depressionLevel,
            $attentionLevels,
            true
        ) ||
        in_array(
            $anxietyLevel,
            $attentionLevels,
            true
        ) ||
        in_array(
            $stressLevel,
            $attentionLevels,
            true
        );
}