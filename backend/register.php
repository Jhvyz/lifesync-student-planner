<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Invalid request. Please return to the registration page and try again.');
}

require_once "../config/connection.php";
require_once "../classes/User.php";

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$firstName = trim((string)($_POST['first_name'] ?? ''));
$lastName = trim((string)($_POST['last_name'] ?? ''));
$username = trim((string)($_POST['username'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$gender = trim((string)($_POST['gender'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');
$allowedGenders = ['Male', 'Female', 'Other', 'Prefer not to say'];
$errors = [];

if ($firstName === '' || strlen($firstName) > 50) {
    $errors[] = 'Enter a first name (up to 50 characters).';
}
if ($lastName === '' || strlen($lastName) > 50) {
    $errors[] = 'Enter a last name (up to 50 characters).';
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    $errors[] = 'Username must be 3–50 characters and use only letters, numbers, dots, dashes, or underscores.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = 'Enter a valid email address (up to 100 characters).';
}
if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = 'Select a gender from the list.';
}
if ($address === '' || strlen($address) > 255) {
    $errors[] = 'Enter an address (up to 255 characters).';
}
if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($password !== $confirmPassword) {
    $errors[] = 'Password and confirm password do not match.';
}

if ($errors) {
    $_SESSION['register_old'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'username' => $username,
        'email' => $email,
        'gender' => $gender,
        'address' => $address,
    ];
    $_SESSION['auth_error'] = implode(' ', $errors);
    header('Location: ../register.php');
    exit;
}

try {
    $db->beginTransaction();
    $userId = $user->createUser($username, $email, $password, $firstName, $lastName, $gender, $address);
    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    if ($exception instanceof PDOException && $exception->getCode() === '23000') {
        $_SESSION['register_old'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'gender' => $gender,
            'address' => $address,
        ];
        $_SESSION['auth_error'] = 'That username or email is already registered.';
        header('Location: ../register.php');
        exit;
    }

    throw $exception;
}

unset($_SESSION['register_old']);
session_regenerate_id(true);
unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name']);
$_SESSION['auth_success'] = 'Your LifeSync account is ready. Please log in to continue.';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
session_write_close();

header('Location: ../login.php');
exit;
