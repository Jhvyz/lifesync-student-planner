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
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['auth_error']);

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
    <title>LifeSync: Student Time &amp; Energy Planner | Create account</title>
    <style>
        :root {
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
            --red: #a94e45;
            --red-pale: #fff0ed;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh; margin: 0; padding: 42px 24px;
            background: radial-gradient(ellipse at 15% 10%, #e8f0e9 0, transparent 34%), var(--canvas);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        a { color: inherit; text-decoration: none; }
        .page { width: min(780px, 100%); min-height: calc(100vh - 84px); margin: 0 auto; display: flex; flex-direction: column; justify-content: center; gap: 20px; }
        .page-title { margin: 0; color: var(--green-dark); text-align: center; font-size: clamp(25px, 4vw, 34px); font-weight: 800; letter-spacing: -.045em; line-height: 1.2; }
        .card { width: 100%; padding: clamp(26px, 4vw, 40px); border: 1px solid var(--line); border-radius: 18px; background: var(--paper); box-shadow: 0 18px 45px rgba(31,51,44,.07); }
        .card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; margin-bottom: 25px; }
        .heading { margin: 0; font-size: 27px; letter-spacing: -.04em; }
        .subtitle { margin: 7px 0 0; color: #5e6d64; font-size: 15px; }
        .step-label { flex: 0 0 auto; padding: 8px 11px; border-radius: 999px; background: var(--green-pale); color: var(--green-dark); font-size: 12px; font-weight: 750; }
        .error { margin-bottom: 18px; padding: 12px 13px; border-radius: 9px; background: var(--red-pale); color: #8d3b33; font-size: 14px; line-height: 1.5; }
        .section-block { padding: 20px; border: 1px solid #edf0eb; border-radius: 13px; background: #fcfdfa; }
        .section-block + .section-block { margin-top: 15px; }
        .section-heading { display: flex; align-items: center; gap: 11px; margin-bottom: 16px; }
        .section-number { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: var(--green-pale); color: var(--green-dark); font-size: 12px; font-weight: 800; }
        .section-title { margin: 0; font-size: 16px; font-weight: 750; }
        .section-caption { margin: 3px 0 0; color: #5e6d64; font-size: 13px; }
        .fields { display: grid; gap: 15px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        input, select {
            width: 100%; min-height: 44px; padding: 9px 11px; border: 1px solid var(--line);
            border-radius: 8px; outline: none; background: #fff; color: var(--ink);
            font: inherit; font-size: 14px;
        }
        input::placeholder { color: #737f77; opacity: 1; }
        input:focus, select:focus { border-color: #83b19a; box-shadow: 0 0 0 3px rgba(40,123,93,.1); }
        .profile-bottom { display: grid; grid-template-columns: minmax(145px, .38fr) minmax(0, 1fr); gap: 15px; }
        .password-hint { margin: -1px 0 0; color: #5e6d64; font-size: 13px; line-height: 1.5; }
        .submit {
            width: 100%; min-height: 49px; margin-top: 17px; border: 0; border-radius: 9px;
            background: var(--green); color: #fff; cursor: pointer; font: inherit;
            font-size: 16px; font-weight: 750; box-shadow: 0 6px 12px rgba(40,123,93,.15);
        }
        .submit:hover { background: var(--green-dark); }
        .submit span { margin-right: 7px; font-size: 14px; vertical-align: -1px; }
        .switch { margin: 17px 0 0; color: #5e6d64; text-align: center; font-size: 14px; }
        .switch a { color: var(--green); font-weight: 750; }
        @media (max-width: 520px) {
            body { padding: 25px 14px; }
            .page { min-height: auto; gap: 15px; }
            .page-title { font-size: 24px; }
            .card { padding: 22px 17px; }
            .section-block { padding: 15px; }
            .row { gap: 9px; }
            .profile-bottom { grid-template-columns: minmax(100px, .42fr) minmax(0, 1fr); gap: 9px; }
        }
        @media (max-width: 360px) {
            .row, .profile-bottom { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="page">
        <h1 class="page-title">LifeSync: Student Time &amp; Energy Planner</h1>
        <section class="card" aria-label="Create a LifeSync account">
            <div class="card-heading">
                <div>
                    <h2 class="heading">Create your account</h2>
                    <p class="subtitle">A few details to get your planner ready.</p>
                </div>
                <span class="step-label">GET STARTED</span>
            </div>
            <?php if ($authError !== ''): ?>
                <div class="error" role="alert"><?php echo h($authError); ?></div>
            <?php endif; ?>

            <form action="backend/register.php" method="POST" class="fields">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

                <section class="section-block">
                    <div class="section-heading">
                        <span class="section-number">01</span>
                        <div><h3 class="section-title">Your details</h3><p class="section-caption">Tell us a little about yourself.</p></div>
                    </div>
                    <div class="fields">
                        <div class="row">
                            <div class="field">
                                <label for="first_name">First name</label>
                                <input id="first_name" name="first_name" type="text" maxlength="50" autocomplete="given-name" placeholder="e.g. Juan" value="<?php echo h($old['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="field">
                                <label for="last_name">Last name</label>
                                <input id="last_name" name="last_name" type="text" maxlength="50" autocomplete="family-name" placeholder="e.g. Dela Cruz" value="<?php echo h($old['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="field email-field">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" maxlength="100" autocomplete="email" placeholder="juan@email.com" value="<?php echo h($old['email'] ?? ''); ?>" required>
                        </div>
                        <div class="profile-bottom">
                            <div class="field">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="" disabled <?php echo empty($old['gender']) ? 'selected' : ''; ?>>Select</option>
                                    <option value="Male" <?php echo ($old['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($old['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($old['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    <option value="Prefer not to say" <?php echo ($old['gender'] ?? '') === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="address">Address</label>
                                <input id="address" name="address" type="text" maxlength="255" autocomplete="street-address" placeholder="Cebu City" value="<?php echo h($old['address'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="section-block">
                    <div class="section-heading">
                        <span class="section-number">02</span>
                        <div><h3 class="section-title">Login details</h3><p class="section-caption">Keep your account secure.</p></div>
                    </div>
                    <div class="fields">
                        <div class="field">
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]{3,50}" autocomplete="username" placeholder="Choose a username" value="<?php echo h($old['username'] ?? ''); ?>" required>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="password">Password</label>
                                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Create a password" required>
                            </div>
                            <div class="field">
                                <label for="confirm_password">Confirm password</label>
                                <input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" placeholder="Re-enter password" required>
                            </div>
                        </div>
                        <p class="password-hint">Choose at least 8 characters and enter the same password twice.</p>
                    </div>
                </section>

                <button class="submit" type="submit"><span aria-hidden="true">+</span>Create account</button>
            </form>
            <p class="switch">Already have an account? <a href="login.php">Log in</a></p>
        </section>
    </main>
</body>
</html>
