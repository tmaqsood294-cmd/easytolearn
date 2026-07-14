<?php
// Session ko poori website par start rakhne ke liye
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// InfinityFree Database Details (Apni details se replace karein)
$host = "sql101.infinityfree.com"; 
$username = "if0_42241533";          
$password = "Zm5nGkjqmTLGG0a";       
$dbname = "if0_42241533_school_db";  

$conn = new mysqli($host, $username, $password, $dbname);

// Connection Check
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>