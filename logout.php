<?php
require_once 'includes/auth.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit;
