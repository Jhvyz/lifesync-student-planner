<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['csrf_token'])
    || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    exit('Invalid request. Please return to LifeSync and try again.');
}

require_once "../config/connection.php";
require_once "../classes/User.php";

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$userId = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add_task') {
    $pillarId = (int)($_POST['pillar_id'] ?? 0);
    if (!$user->ownsPillar($pillarId, $userId)) {
        $_SESSION['task_error'] = 'Please select a valid life area for your account.';
        header('Location: ../index.php');
        exit;
    }

    $user->addTask(
        $userId,
        $pillarId,
        trim((string)($_POST['title'] ?? '')),
        trim((string)($_POST['description'] ?? '')),
        (int)($_POST['estimated_minutes'] ?? 0),
        (string)($_POST['effort_level'] ?? 'Low'),
        (string)($_POST['due_date'] ?? ''),
        (string)($_POST['status'] ?? 'Pending')
    );
} elseif ($action === 'update_task') {
    $pillarId = (int)($_POST['pillar_id'] ?? 0);
    if (!$user->ownsPillar($pillarId, $userId)) {
        $_SESSION['task_error'] = 'Please select a valid life area for your account.';
        header('Location: ../index.php');
        exit;
    }

    $user->updateTask(
        (int)($_POST['task_id'] ?? 0),
        $userId,
        $pillarId,
        trim((string)($_POST['title'] ?? '')),
        trim((string)($_POST['description'] ?? '')),
        (int)($_POST['estimated_minutes'] ?? 0),
        (string)($_POST['effort_level'] ?? 'Low'),
        (string)($_POST['due_date'] ?? ''),
        (string)($_POST['status'] ?? 'Pending')
    );
} elseif ($action === 'delete_task') {
    $user->deleteTask((int)($_POST['task_id'] ?? 0), $userId);
}

header('Location: ../index.php');
exit;
