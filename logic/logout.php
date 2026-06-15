<?php
// /logic/logout.php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to root index
header("Location: /index.php");
exit();