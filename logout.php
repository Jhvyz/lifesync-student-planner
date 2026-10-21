<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['user_id'])
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Invalid request. Please return to LifeSync and try again.');
}

$_SESSION = [];
session_regenerate_id(true);
session_destroy();

header('Location: login.php');
exit;
