<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Invalid request. Please return to the login page and try again.');
}

require_once "../config/connection.php";
require_once "../classes/User.php";

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$login = trim((string)($_POST['login'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$account = $login !== '' ? $user->findUserByLogin($login) : false;

if (!$account || !password_verify($password, $account['password_hash'])) {
    $_SESSION['auth_error'] = 'We could not sign you in with those details.';
    header('Location: ../login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$account['user_id'];
$_SESSION['username'] = $account['username'];
$_SESSION['full_name'] = $account['full_name'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
session_write_close();

header('Location: ../index.php');
exit;
