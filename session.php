<?php
// ============================================================
// session.php — Session management and auth helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if not logged in.
 * Optionally restrict to a specific role ('admin' or 'customer').
 */
function require_login(string $role = '') {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: unauthorized.php');
        exit;
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_customer(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_customer_id(): ?int {
    return $_SESSION['customer_id'] ?? null;
}

function current_username(): string {
    return $_SESSION['username'] ?? '';
}

function current_role(): string {
    return $_SESSION['role'] ?? '';
}
?>