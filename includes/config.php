<?php
// DB configuration and helper functions
$DB_HOST = "localhost:3307"; // update if needed
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "notes";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    http_response_code(500);
    die("Database connection failed: " . mysqli_connect_error());
}

function send_json($payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES);
}

// You can add more shared helpers here (e.g. auth functions later)
?>