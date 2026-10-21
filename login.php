<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$authError = $_SESSION['auth_error'] ?? '';
$authSuccess = $_SESSION['auth_success'] ?? '';
unset($_SESSION['auth_error']);
unset($_SESSION['auth_success']);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f4">
    <title>Log in | LifeSync</title>
    <style>
        :root {
            --ink: #1f332c;
            --muted: #718078;
            --line: #e5ebe5;
            --paper: #fff;
            --canvas: #f6f7f4;
            --green: #287b5d;
            --green-dark: #1f6349;
            --green-pale: #e8f3ed;
            --red: #a94e45;
            --red-pale: #fff0ed;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px;
            background: radial-gradient(ellipse at 16% 12%, #eaf2eb 0, transparent 35%), var(--canvas);
            color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        .layout { width: min(980px, 100%); display: grid; grid-template-columns: 1fr 420px; align-items: center; gap: 70px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 14px; color: #fff; background: var(--green); font-size: 22px; font-weight: 800; }
        .brand-name { font-size: 20px; letter-spacing: -.04em; }
        .brand-tag { display: block; margin-top: 3px; color: #5e6d64; font-size: 13px; }
        .intro { margin-top: 43px; }
        .eyebrow { color: var(--green-dark); font-size: 13px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 12px 0; font-size: clamp(33px, 5vw, 48px); letter-spacing: -.06em; line-height: 1.08; }
        .intro-copy { max-width: 420px; color: #5e6d64; line-height: 1.7; font-size: 16px; }
        .promise { display: grid; gap: 12px; margin-top: 29px; color: #43564b; font-size: 14px; }
        .promise span::before { content: "✓"; margin-right: 9px; color: var(--green); font-weight: 800; }
        .card { padding: 30px; border: 1px solid var(--line); border-radius: 19px; background: var(--paper); box-shadow: 0 20px 60px rgba(31,51,44,.09); }
        h2 { margin: 0; font-size: 21px; letter-spacing: -.04em; }
        .subheading { margin: 8px 0 23px; color: #5e6d64; font-size: 14px; }
        .error { margin-bottom: 16px; padding: 12px 13px; border-radius: 9px; background: var(--red-pale); color: #8d3b33; font-size: 14px; line-height: 1.5; }
        .success { margin-bottom: 16px; padding: 12px 13px; border: 1px solid #cfe5d7; border-radius: 9px; background: var(--green-pale); color: var(--green-dark); font-size: 14px; line-height: 1.5; }
        .fields { display: grid; gap: 17px; }
        .field { display: grid; gap: 6px; }
        label { color: #3f5147; font-size: 14px; font-weight: 700; }
        input { width: 100%; min-height: 49px; padding: 11px 13px; border: 1px solid var(--line); border-radius: 9px; outline: none; background: #fbfcfa; color: var(--ink); font: inherit; font-size: 15px; }
        input::placeholder { color: #64736b; opacity: 1; }
        input:focus { border-color: #83b19a; box-shadow: 0 0 0 3px rgba(40,123,93,.1); }
        .submit { width: 100%; min-height: 49px; margin-top: 5px; border: 0; border-radius: 9px; background: var(--green); color: white; cursor: pointer; font: inherit; font-size: 15px; font-weight: 750; }
        .submit:hover { background: var(--green-dark); }
        .switch { margin: 20px 0 0; color: #5e6d64; text-align: center; font-size: 13px; }
        .switch a { color: var(--green); font-weight: 750; }
        .hint { margin: 11px 0 0; color: var(--muted); font-size: 10px; line-height: 1.5; }
        @media (max-width: 760px) {
            body { padding: 18px; }
            .layout { grid-template-columns: 1fr; gap: 24px; max-width: 430px; }
            .intro { margin-top: 23px; }
            .intro-copy, .promise { display: none; }
            h1 { margin: 10px 0 0; font-size: 31px; }
            .intro-copy { font-size: 15px; }
            .card { padding: 25px 21px; }
            .card { padding: 23px; }
        }
    </style>
</head>
<body>
    <main class="layout">
        <section>
            <a class="brand" href="login.php">
                <span class="brand-mark" aria-hidden="true">L</span>
                <span><strong class="brand-name">LifeSync</strong><span class="brand-tag">Student time &amp; energy planner</span></span>
            </a>
            <div class="intro">
                <p class="eyebrow">Make room for what matters</p>
                <h1>A more balanced<br>way to get things done.</h1>
                <p class="intro-copy">Bring your studies, home responsibilities, and personal wellbeing together in one simple planner.</p>
                <div class="promise">
                    <span>Plan your school and personal tasks together</span>
                    <span>Make realistic room for rest and recovery</span>
                    <span>Take big projects one step at a time</span>
                </div>
            </div>
        </section>
        <section class="card">
            <h2>Welcome back</h2>
            <p class="subheading">Log in to pick up where you left off.</p>
            <?php if ($authError !== ''): ?>
                <div class="error" role="alert"><?php echo h($authError); ?></div>
            <?php endif; ?>
            <?php if ($authSuccess !== ''): ?>
                <div class="success" role="status"><?php echo h($authSuccess); ?></div>
            <?php endif; ?>
            <form action="backend/login.php" method="POST" class="fields">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <div class="field">
                    <label for="login">Username or email</label>
                    <input id="login" name="login" type="text" autocomplete="username" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="submit" type="submit">Log in</button>
            </form>
            <p class="switch">New to LifeSync? <a href="register.php">Create an account</a></p>
        </section>
    </main>
</body>
</html>
