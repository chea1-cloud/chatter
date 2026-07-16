<?php
/**
 * auth.php
 * Small helper library around PHP sessions so the rest of the
 * app can just ask "isLoggedIn()?" or "currentUserId()".
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUsername() {
    return $_SESSION['username'] ?? null;
}

/** Redirect to login.php if the visitor is not logged in. */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/** Escape output for safe HTML display. */
function h($str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/** Pick a consistent avatar color for a username (like LINE/SMS contact colors). */
function avatarColor(string $username): string {
    $palette = ['#f87171', '#fb923c', '#fbbf24', '#4ade80', '#38bdf8', '#818cf8', '#f472b6', '#a78bfa'];
    $hash = 0;
    foreach (str_split($username) as $ch) {
        $hash = ord($ch) + (($hash << 5) - $hash);
    }
    return $palette[abs($hash) % count($palette)];
}

/** Format a stored 'Y-m-d H:i:s' timestamp as a short chat-style time (e.g. "3:41 PM"). */
function formatChatTime(string $datetime): string {
    $ts = strtotime($datetime . ' UTC');
    return date('g:i A', $ts);
}
