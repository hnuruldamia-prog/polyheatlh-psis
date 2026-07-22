<?php

function admin_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function admin_count($conn, $query)
{
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

function admin_format_date($date)
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return "Not available";
    }

    return date("d M Y", $timestamp);
}

function admin_format_datetime($date)
{
    if (empty($date)) {
        return "Not available";
    }

    $timestamp = strtotime($date);

    if (!$timestamp) {
        return "Not available";
    }

    return date("d M Y, h:i A", $timestamp);
}

function admin_severity_class($level)
{
    $level = strtolower(trim((string) $level));

    switch ($level) {
        case "normal":
            return "severity-normal";

        case "mild":
            return "severity-mild";

        case "moderate":
            return "severity-moderate";

        case "severe":
        case "extremely severe":
            return "severity-severe";

        default:
            return "severity-default";
    }
}

function admin_table_exists($conn, $table)
{
    $table = $conn->real_escape_string($table);

    $result = $conn->query(
        "SHOW TABLES LIKE '{$table}'"
    );

    return $result && $result->num_rows > 0;
}

function admin_safe_count($conn, $table, $condition = "1")
{
    if (!admin_table_exists($conn, $table)) {
        return 0;
    }

    $allowedTables = [
        "students",
        "dass_results",
        "screening_results",
        "journals",
        "moods"
    ];

    if (!in_array($table, $allowedTables, true)) {
        return 0;
    }

    $query = "
        SELECT COUNT(*) AS total
        FROM {$table}
        WHERE {$condition}
    ";

    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}