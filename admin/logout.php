<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
session_destroy();
$base = defined('ROOT_BASE') ? ROOT_BASE : APP_BASE;
header('Location: ' . $base . '/index.php');
exit;
