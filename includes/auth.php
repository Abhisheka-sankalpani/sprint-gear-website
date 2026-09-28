<?php
// includes/auth.php - Session Management & Authentication Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate guest session ID if not set
if (empty($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = bin2hex(random_bytes(16));
}

// Get logged-in user
function getLoggedInUser() {
    if (!empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }
    return null;
}

// Check if logged in
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

// Require login redirect
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header("Location: $redirectUrl");
        exit;
    }
}

// Require admin redirect
function requireAdmin($redirectUrl = '../login.php') {
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Access denied. Admin authorization required.';
        header("Location: $redirectUrl");
        exit;
    }
}

// CSRF Protection Helpers
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
