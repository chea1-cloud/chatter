<?php
require_once __DIR__ . '/auth.php';

$_SESSION = [];
session_destroy();

session_start();
$_SESSION['flash'] = ['type' => 'success', 'text' => 'You have been logged out.'];
header('Location: index.php');
exit;
