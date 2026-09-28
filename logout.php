<?php
// logout.php - Session Logout Handler
require_once __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();

session_start();
setFlash('info', 'You have been logged out.');
header("Location: index.php");
exit;
