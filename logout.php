<?php
require_once 'config/db.php';
// Saare session variables khatam karna
$_SESSION = array();
session_destroy();
// Login page par wapas bhejna
header("Location: login.php");
exit;
?>