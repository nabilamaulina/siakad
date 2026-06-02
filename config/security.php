<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generator
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitasi Input XSS
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Middleware Proteksi Halaman
function middleware($allowed_roles = []) {
    if (!isset($_SESSION['id_user'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../auth/login.php?error=unauthorized");
        exit();
    }
    
    // Auto Logout (15 Menit Inaktif)
    $timeout = 900; 
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?error=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();
}