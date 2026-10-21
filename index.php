<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once "config/connection.php";
require_once "classes/User.php";

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$userId = (int)$_SESSION['user_id'];
$pillarRows = $user->ensureDefaultPillars($userId);
$tasks = $user->getTasks((int)$_SESSION['user_id']);
$tasks = is_array($tasks) ? $tasks : [];

$pillars = [];
foreach ($pillarRows as $pillarRow) {
    $pillars[(int)$pillarRow['pillar_id']] = $pillarRow['pillar_name'];
}
$statuses = ['Pending', 'In Progress', 'Completed', 'Postponed'];
$effortLevels = ['Low', 'Medium', 'High'];
$search = trim((string)($_GET['q'] ?? ''));
$pillarFilter = (int)($_GET['pillar'] ?? 0);
$statusFilter = trim((string)($_GET['status'] ?? ''));
$today = date('Y-m-d');
$now = time();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function taskPillarName($pillarId, array $pillars): string
{
    return $pillars[(int)$pillarId] ?? 'Academics';
}

$filteredTasks = array_values(array_filter($tasks, function ($task) use ($search, $pillarFilter, $statusFilter) {
    $matchesSearch = $search === ''
        || stripos((string)($task['title'] ?? ''), $search) !== false
        || stripos((string)($task['description'] ?? ''), $search) !== false;
    $matchesPillar = $pillarFilter === 0 || (int)($task['pillar_id'] ?? 1) === $pillarFilter;
    $matchesStatus = $statusFilter === '' || ($task['status'] ?? 'Pending') === $statusFilter;

    return $matchesSearch && $matchesPillar && $matchesStatus;
}));

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lifesync-tasks-' . $today . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Task', 'Description', 'Life area', 'Estimated minutes', 'Effort', 'Due date', 'Status']);

    foreach ($filteredTasks as $task) {
        fputcsv($output, [
            $task['title'] ?? '',
            $task['description'] ?? '',
            taskPillarName($task['pillar_id'] ?? 1, $pillars),
            $task['estimated_minutes'] ?? 0,
            $task['effort_level'] ?? 'Low',
            $task['due_date'] ?? '',
            $task['status'] ?? 'Pending',
        ]);
    }

    fclose($output);
    exit;
}

$totalTasks = count($tasks);
$completedTasks = 0;
$overdueTasks = 0;
$openMinutes = 0;
$pillarCounts = array_fill_keys(array_keys($pillars), 0);
$todayTasks = [];
$editTask = null;

foreach ($tasks as $task) {
    $status = $task['status'] ?? 'Pending';
    $dueTimestamp = !empty($task['due_date']) ? strtotime($task['due_date']) : false;

    if ($status === 'Completed') {
        $completedTasks++;
    } else {
        $openMinutes += (int)($task['estimated_minutes'] ?? 0);
        if ($dueTimestamp !== false && $dueTimestamp < $now) {
            $overdueTasks++;
        }
    }

    $pillarId = (int)($task['pillar_id'] ?? 1);
    if (isset($pillarCounts[$pillarId])) {
        $pillarCounts[$pillarId]++;
    }

    if (!empty($task['due_date']) && substr((string)$task['due_date'], 0, 10) === $today && $status !== 'Completed') {
        $todayTasks[] = $task;
    }

    if (isset($_GET['edit']) && (int)$_GET['edit'] === (int)($task['task_id'] ?? 0)) {
        $editTask = $task;
    }
}

$completionRate = $totalTasks > 0 ? (int)round(($completedTasks / $totalTasks) * 100) : 0;
$openHours = round($openMinutes / 60, 1);
$totalPillarTasks = array_sum($pillarCounts);
$defaultPillarId = (int)(array_key_first($pillars) ?? 0);
$taskError = $_SESSION['task_error'] ?? '';
unset($_SESSION['task_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f4">
    <title>LifeSync | Student planner</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #1f332c;
            --muted: #718078;
            --line: #e5ebe5;
            --paper: #ffffff;
            --canvas: #f6f7f4;
            --green: #287b5d;
            --green-dark: #1f6349;
            --green-pale: #e8f3ed;
            --orange: #d8874d;
            --orange-pale: #fff2e8;
            --blue: #6587bd;
            --blue-pale: #edf2fb;
            --red: #bd6559;
            --red-pale: #fff0ed;
            --shadow: 0 12px 35px rgba(31, 51, 44, .055);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .shell { width: min(1200px, calc(100% - 48px)); margin: 0 auto; }
        .topbar { border-bottom: 1px solid var(--line); background: rgba(255,255,255,.78); }
        .topbar-inner { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-mark {
            width: 42px; height: 42px; display: grid; place-items: center; border-radius: 14px;
            color: white; background: var(--green); font-size: 21px; font-weight: 800;
        }
        .brand-name { margin: 0; font-size: 20px; letter-spacing: -.04em; line-height: 1.1; }
        .brand-subtitle { margin: 4px 0 0; color: #5e6d64; font-size: 13px; }
        .topbar-note { color: #5e6d64; font-size: 14px; }
        main { padding: 38px 0 60px; }
        .welcome { display: flex; align-items: end; justify-content: space-between; gap: 24px; margin-bottom: 25px; }
        .eyebrow { margin: 0 0 9px; color: var(--green-dark); font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(27px, 4vw, 38px); letter-spacing: -.055em; line-height: 1.12; }
        .welcome-copy { margin: 10px 0 0; color: #5e6d64; max-width: 650px; font-size: 16px; }
        .today-chip { flex: 0 0 auto; padding: 11px 15px; border: 1px solid var(--line); border-radius: 999px; background: var(--paper); color: #5e6d64; font-size: 14px; }
        .today-chip strong { color: var(--ink); }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
        .stat-card, .panel { border: 1px solid var(--line); border-radius: 17px; background: var(--paper); box-shadow: var(--shadow); }
        .stat-card { min-height: 139px; padding: 20px; }
        .stat-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #53645b; font-size: 14px; font-weight: 700; }
        .stat-icon { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 11px; background: var(--green-pale); color: var(--green); font-weight: 800; }
        .stat-icon.orange { color: var(--orange); background: var(--orange-pale); }
        .stat-icon.blue { color: var(--blue); background: var(--blue-pale); }
        .stat-value { margin-top: 14px; font-size: 29px; font-weight: 760; letter-spacing: -.05em; line-height: 1; }
        .stat-foot { margin-top: 9px; color: #5e6d64; font-size: 13px; }
        .main-grid { display: grid; grid-template-columns: minmax(280px, .76fr) minmax(0, 1.6fr); align-items: start; gap: 20px; }
        .left-column, .right-column { display: grid; gap: 18px; }
        .panel { padding: 22px; }
        .error { padding: 13px 14px; border: 1px solid #efd0ca; border-radius: 11px; background: var(--red-pale); color: #8d3b33; font-size: 14px; }
        .panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 18px; }
        h2 { margin: 0; font-size: 19px; letter-spacing: -.025em; }
        .panel-hint { margin: 6px 0 0; color: #5e6d64; font-size: 13px; }
        .form-stack { display: grid; gap: 15px; }
        .field { display: grid; gap: 7px; }
        .field label { color: #3f5147; font-size: 14px; font-weight: 700; }
        .control {
            width: 100%; min-height: 47px; padding: 10px 12px; border: 1px solid var(--line);
            border-radius: 9px; outline: none; background: #fbfcfa; color: var(--ink); font-size: 15px;
        }
        .control::placeholder { color: #64736b; opacity: 1; }
        .control:focus { border-color: #83b19a; box-shadow: 0 0 0 3px rgba(40,123,93,.1); }
        textarea.control { min-height: 88px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .button {
            display: inline-flex; min-height: 39px; align-items: center; justify-content: center; gap: 7px;
            padding: 9px 13px; border: 1px solid transparent; border-radius: 9px; cursor: pointer;
            font-size: 14px; font-weight: 750; transition: background .15s ease, border-color .15s ease;
        }
        .button-primary { width: 100%; color: white; background: var(--green); }
        .button-primary:hover { background: var(--green-dark); }
        .button-light { border-color: var(--line); background: white; color: #52635a; }
        .button-light:hover { border-color: #bdc9bf; background: #f8faf8; }
        .button-danger { color: var(--red); border-color: #f0d8d3; background: white; }
        .button-danger:hover { background: var(--red-pale); }
        .text-link { color: var(--green-dark); font-size: 13px; font-weight: 750; }
        .text-link:hover { text-decoration: underline; }
        .pillar-list { display: grid; gap: 17px; }
        .pillar-top { display: flex; justify-content: space-between; gap: 10px; margin-bottom: 8px; font-size: 14px; }
        .pillar-top span:last-child { color: var(--muted); }
        .bar { height: 7px; overflow: hidden; border-radius: 999px; background: #edf1ed; }
        .bar-fill { height: 100%; border-radius: inherit; background: var(--green); }
        .bar-fill.chores { background: var(--orange); }
        .bar-fill.selfcare { background: var(--blue); }
        .balance-note { margin: 17px 0 0; padding: 13px 14px; border-radius: 10px; background: #f5f8f5; color: #53645b; font-size: 13px; }
        .filters { display: grid; grid-template-columns: minmax(160px, 1fr) 160px 150px auto auto; gap: 9px; align-items: center; margin-bottom: 16px; }
        .filters .control { min-height: 44px; }
        .task-list { display: grid; gap: 11px; }
        .task-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; padding: 16px; border: 1px solid var(--line); border-radius: 12px; background: #fff; }
        .task-main { min-width: 0; }
        .task-title-line { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
        .task-title { margin: 0; overflow-wrap: anywhere; font-size: 16px; font-weight: 750; }
        .task-description { margin: 6px 0 0; color: #5e6d64; font-size: 14px; overflow-wrap: anywhere; }
        .task-meta { display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 11px; color: #53645b; font-size: 13px; }
        .tag { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; background: #f1f4f1; color: #46584d; font-size: 12px; font-weight: 700; }
        .tag-academics { background: var(--green-pale); color: var(--green-dark); }
        .tag-chores { background: var(--orange-pale); color: #a7622d; }
        .tag-selfcare { background: var(--blue-pale); color: #5878ac; }
        .status { background: #f1f4f1; color: #65736b; }
        .status-progress { background: #fff4df; color: #9a7025; }
        .status-completed { background: var(--green-pale); color: var(--green-dark); }
        .status-postponed { background: var(--red-pale); color: var(--red); }
        .overdue { color: var(--red); font-weight: 750; }
        .task-actions { display: flex; align-items: flex-start; gap: 6px; }
        .task-actions .button { min-height: 38px; padding: 7px 11px; font-size: 13px; }
        details.delete-confirm { position: relative; }
        details.delete-confirm summary { list-style: none; }
        details.delete-confirm summary::-webkit-details-marker { display: none; }
        .confirm-box { position: absolute; z-index: 2; top: 37px; right: 0; width: 190px; padding: 12px; border: 1px solid var(--line); border-radius: 10px; background: white; box-shadow: var(--shadow); }
        .confirm-box p { margin: 0 0 10px; color: #53645b; font-size: 13px; }
        .today-list { display: grid; gap: 10px; }
        .today-item { display: flex; align-items: flex-start; gap: 10px; }
        .today-dot { width: 8px; height: 8px; flex: 0 0 auto; margin-top: 5px; border-radius: 50%; background: var(--orange); }
        .today-item strong { display: block; font-size: 14px; }
        .today-item span { color: #53645b; font-size: 13px; }
        .empty-state { padding: 28px 18px; border: 1px dashed #dce5dd; border-radius: 12px; color: #53645b; text-align: center; font-size: 14px; }
        .empty-state strong { display: block; margin-bottom: 5px; color: var(--ink); font-size: 16px; }
        .footer { padding-top: 22px; color: #5e6d64; text-align: center; font-size: 12px; }
        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
            .left-column { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 650px) {
            .shell { width: min(100% - 28px, 560px); }
            main { padding-top: 25px; }
            .topbar-inner { min-height: 66px; }
            .topbar-note { display: none; }
            .welcome { align-items: flex-start; flex-direction: column; gap: 13px; }
            .stats { grid-template-columns: 1fr; gap: 10px; }
            .stat-card { min-height: 118px; }
            .left-column { grid-template-columns: 1fr; }
            .panel { padding: 17px; }
            .filters { grid-template-columns: 1fr 1fr; }
            .filters .search-control { grid-column: 1 / -1; }
            .filters .button { width: 100%; }
            .task-card { grid-template-columns: 1fr; }
            .task-actions { justify-content: flex-end; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="shell topbar-inner">
            <a class="brand" href="index.php" aria-label="LifeSync home">
                <span class="brand-mark" aria-hidden="true">L</span>
                <span>
                    <strong class="brand-name">LifeSync</strong>
                    <span class="brand-subtitle">Student time &amp; energy planner</span>
                </span>
            </a>
            <div style="display:flex;align-items:center;gap:14px">
                <span class="topbar-note">Hi, <?php echo h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student'); ?></span>
                <form action="logout.php" method="POST" style="margin:0">
                    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                    <button class="button button-light" type="submit">Log out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="shell">
        <section class="welcome">
            <div>
                <p class="eyebrow">Your day, in balance</p>
                <h1>A little progress adds up.</h1>
                <p class="welcome-copy">Keep assignments, home life, and recovery in one calm place. Start with what you have energy for today.</p>
            </div>
            <div class="today-chip"><strong><?php echo h(date('l')); ?></strong>&nbsp; · &nbsp;<?php echo h(date('F j, Y')); ?></div>
        </section>

        <section class="stats" aria-label="Task overview">
            <article class="stat-card">
                <div class="stat-head"><span>Open workload</span><span class="stat-icon">◷</span></div>
                <div class="stat-value"><?php echo h(number_format($openHours, 1)); ?><span style="font-size:15px;letter-spacing:0"> hrs</span></div>
                <div class="stat-foot">Estimated time across unfinished tasks</div>
            </article>
            <article class="stat-card">
                <div class="stat-head"><span>Tasks completed</span><span class="stat-icon blue">✓</span></div>
                <div class="stat-value"><?php echo h($completionRate); ?><span style="font-size:15px;letter-spacing:0">%</span></div>
                <div class="stat-foot"><?php echo h($completedTasks); ?> completed out of <?php echo h($totalTasks); ?> total</div>
            </article>
            <article class="stat-card">
                <div class="stat-head"><span>Past due</span><span class="stat-icon orange">!</span></div>
                <div class="stat-value"><?php echo h($overdueTasks); ?></div>
                <div class="stat-foot"><?php echo $overdueTasks === 0 ? 'You are caught up on due dates.' : 'Take one small step on an overdue task.'; ?></div>
            </article>
        </section>

        <div class="main-grid">
            <aside class="left-column">
                <?php if ($taskError !== ''): ?>
                    <div class="error" role="alert"><?php echo h($taskError); ?></div>
                <?php endif; ?>
                <section class="panel" id="task-form">
                    <div class="panel-heading">
                        <div>
                            <h2><?php echo $editTask ? 'Edit task' : 'Add a task'; ?></h2>
                            <p class="panel-hint"><?php echo $editTask ? 'Update the details below.' : 'Break a big responsibility into a next step.'; ?></p>
                        </div>
                        <?php if ($editTask): ?>
                            <a class="text-link" href="index.php">Cancel</a>
                        <?php else: ?>
                            <span class="stat-icon">+</span>
                        <?php endif; ?>
                    </div>
                    <form action="backend/user_code.php" method="POST" class="form-stack">
                        <input type="hidden" name="action" value="<?php echo $editTask ? 'update_task' : 'add_task'; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                        <?php if ($editTask): ?>
                            <input type="hidden" name="task_id" value="<?php echo h($editTask['task_id']); ?>">
                        <?php endif; ?>
                        <div class="field">
                            <label for="title">Task name</label>
                            <input class="control" id="title" type="text" name="title" maxlength="255" required placeholder="e.g. Draft project proposal" value="<?php echo h($editTask['title'] ?? ''); ?>">
                        </div>
                        <div class="field">
                            <label for="description">Details <span style="font-weight:400">(optional)</span></label>
                            <textarea class="control" id="description" name="description" rows="2" placeholder="What is the next manageable step?"><?php echo h($editTask['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="field">
                            <label for="pillar_id">Life area</label>
                            <select class="control" id="pillar_id" name="pillar_id" required>
                                <?php foreach ($pillars as $id => $name): ?>
                                    <option value="<?php echo h($id); ?>" <?php echo (int)($editTask['pillar_id'] ?? $defaultPillarId) === $id ? 'selected' : ''; ?>><?php echo h($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="field">
                                <label for="estimated_minutes">Estimated minutes</label>
                                <input class="control" id="estimated_minutes" type="number" name="estimated_minutes" min="1" max="1440" required value="<?php echo h($editTask['estimated_minutes'] ?? 45); ?>">
                            </div>
                            <div class="field">
                                <label for="effort_level">Mental effort</label>
                                <select class="control" id="effort_level" name="effort_level" required>
                                    <?php foreach ($effortLevels as $effort): ?>
                                        <option value="<?php echo h($effort); ?>" <?php echo ($editTask['effort_level'] ?? 'Medium') === $effort ? 'selected' : ''; ?>><?php echo h($effort); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field">
                            <label for="due_date">Due date &amp; time</label>
                            <input class="control" id="due_date" type="datetime-local" name="due_date" required value="<?php echo !empty($editTask['due_date']) ? h(date('Y-m-d\TH:i', strtotime($editTask['due_date']))) : ''; ?>">
                        </div>
                        <div class="field">
                            <label for="status">Progress</label>
                            <select class="control" id="status" name="status" required>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo h($status); ?>" <?php echo ($editTask['status'] ?? 'Pending') === $status ? 'selected' : ''; ?>><?php echo h($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="button button-primary" type="submit"><?php echo $editTask ? 'Save changes' : 'Add to my tasks'; ?></button>
                    </form>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Your life areas</h2>
                            <p class="panel-hint">A quick look at where your tasks are landing.</p>
                        </div>
                    </div>
                    <div class="pillar-list">
                        <?php
                        $pillarClasses = [
                            'Academics' => '',
                            'Household Chores' => 'chores',
                            'Recovery / Self-Care' => 'selfcare',
                        ];
                        foreach ($pillars as $id => $name):
                            $percent = $totalPillarTasks > 0 ? (int)round(($pillarCounts[$id] / $totalPillarTasks) * 100) : 0;
                        ?>
                            <div>
                                <div class="pillar-top"><strong><?php echo h($name); ?></strong><span><?php echo h($pillarCounts[$id]); ?> tasks</span></div>
                                <div class="bar"><div class="bar-fill <?php echo h($pillarClasses[$name] ?? ''); ?>" style="width:<?php echo h($percent); ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="balance-note">Rest and self-care are part of a productive plan, not a reward you have to earn.</p>
                </section>
            </aside>

            <section class="right-column">
                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Today’s focus</h2>
                            <p class="panel-hint">Unfinished tasks with a due date today.</p>
                        </div>
                        <span class="tag"><?php echo h(count($todayTasks)); ?> scheduled</span>
                    </div>
                    <?php if ($todayTasks): ?>
                        <div class="today-list">
                            <?php foreach ($todayTasks as $task): ?>
                                <div class="today-item">
                                    <span class="today-dot" aria-hidden="true"></span>
                                    <div>
                                        <strong><?php echo h($task['title'] ?? 'Untitled task'); ?></strong>
                                        <span><?php echo !empty($task['due_date']) ? h(date('g:i A', strtotime($task['due_date']))) : 'Time not set'; ?> · <?php echo h($task['estimated_minutes'] ?? 0); ?> min · <?php echo h($task['effort_level'] ?? 'Low'); ?> effort</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state"><strong>No due tasks today</strong>Use the breathing room for a break, a little planning, or something you enjoy.</div>
                    <?php endif; ?>
                </section>

                <section class="panel">
                    <div class="panel-heading">
                        <div>
                            <h2>Your task list</h2>
                            <p class="panel-hint"><?php echo h(count($filteredTasks)); ?> of <?php echo h($totalTasks); ?> tasks shown · Edit or update as your day changes.</p>
                        </div>
                        <a class="text-link" href="?<?php echo h(http_build_query(array_filter(['q' => $search, 'pillar' => $pillarFilter ?: '', 'status' => $statusFilter, 'export' => 'csv']))); ?>">Export CSV</a>
                    </div>

                    <form method="GET" action="index.php" class="filters">
                        <input class="control search-control" type="search" name="q" value="<?php echo h($search); ?>" placeholder="Search tasks or notes" aria-label="Search tasks or notes">
                        <select class="control" name="pillar" aria-label="Filter by life area">
                            <option value="0">All life areas</option>
                            <?php foreach ($pillars as $id => $name): ?>
                                <option value="<?php echo h($id); ?>" <?php echo $pillarFilter === $id ? 'selected' : ''; ?>><?php echo h($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="control" name="status" aria-label="Filter by progress">
                            <option value="">All progress</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo h($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo h($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button button-primary" type="submit" style="width:auto">Apply</button>
                        <a class="button button-light" href="index.php">Clear</a>
                    </form>

                    <?php if ($filteredTasks): ?>
                        <div class="task-list">
                            <?php foreach ($filteredTasks as $task):
                                $pillarId = (int)($task['pillar_id'] ?? 1);
                                $pillarName = taskPillarName($pillarId, $pillars);
                                $pillarClass = $pillarName === 'Household Chores' ? 'tag-chores' : ($pillarName === 'Recovery / Self-Care' ? 'tag-selfcare' : 'tag-academics');
                                $status = $task['status'] ?? 'Pending';
                                $statusClass = $status === 'Completed' ? 'status-completed' : ($status === 'In Progress' ? 'status-progress' : ($status === 'Postponed' ? 'status-postponed' : ''));
                                $dueTimestamp = !empty($task['due_date']) ? strtotime($task['due_date']) : false;
                                $isOverdue = $status !== 'Completed' && $dueTimestamp !== false && $dueTimestamp < $now;
                            ?>
                                <article class="task-card">
                                    <div class="task-main">
                                        <div class="task-title-line">
                                            <h3 class="task-title"><?php echo h($task['title'] ?? 'Untitled task'); ?></h3>
                                            <span class="tag <?php echo h($pillarClass); ?>"><?php echo h($pillarName); ?></span>
                                            <span class="tag status <?php echo h($statusClass); ?>"><?php echo h($status); ?></span>
                                        </div>
                                        <?php if (!empty($task['description'])): ?>
                                            <p class="task-description"><?php echo nl2br(h($task['description'])); ?></p>
                                        <?php endif; ?>
                                        <div class="task-meta">
                                            <span><?php echo h($task['estimated_minutes'] ?? 0); ?> min</span>
                                            <span><?php echo h($task['effort_level'] ?? 'Low'); ?> effort</span>
                                            <span class="<?php echo $isOverdue ? 'overdue' : ''; ?>"><?php echo $dueTimestamp !== false ? ($isOverdue ? 'Past due · ' : 'Due · ') . h(date('M j, g:i A', $dueTimestamp)) : 'No due date'; ?></span>
                                        </div>
                                    </div>
                                    <div class="task-actions">
                                        <a class="button button-light" href="?edit=<?php echo h((int)($task['task_id'] ?? 0)); ?>#task-form">Edit</a>
                                        <details class="delete-confirm">
                                            <summary class="button button-danger">Delete</summary>
                                            <div class="confirm-box">
                                                <p>Delete this task permanently?</p>
                                                <form action="backend/user_code.php" method="POST">
                                                    <input type="hidden" name="action" value="delete_task">
                                                    <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="task_id" value="<?php echo h((int)($task['task_id'] ?? 0)); ?>">
                                                    <button class="button button-danger" type="submit">Confirm delete</button>
                                                </form>
                                            </div>
                                        </details>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <strong><?php echo $totalTasks === 0 ? 'Your task list is clear.' : 'No tasks match those filters.'; ?></strong>
                            <?php echo $totalTasks === 0 ? 'Add one small task to get started.' : 'Try a different search or clear the filters.'; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </div>
        <footer class="footer">LifeSync · Build a schedule that leaves space to live.</footer>
    </main>
</body>
</html>
