<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        $base = defined('ROOT_BASE') ? ROOT_BASE : (defined('APP_BASE') ? APP_BASE : '');
        header('Location: ' . $base . '/index.php');
        exit;
    }
}