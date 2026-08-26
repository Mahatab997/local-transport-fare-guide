<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Login';
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $pdo = db_connect();
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            login_user($user);
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            }
            redirect('user/dashboard.php');
        }

        $error = 'Invalid login credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · Local Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&family=Archivo:wght@800&family=IBM+Plex+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #132441;
            --navy-2: #1E355C;
            --amber: #E8A33D;
            --teal: #2F8F6E;
            --danger: #C0392B;
            --paper: #EFF2F6;
            --card: #FFFFFF;
            --ink: #182236;
            --muted: #5C6779;
            --line: #DCE1E9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--paper);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-wrap {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(19,36,65,.12);
            overflow: hidden;
        }

        .auth-card-header {
            background: linear-gradient(135deg, var(--navy), var(--navy-2));
            color: #fff;
            padding: 28px 32px 22px;
            text-align: center;
        }

        .auth-card-header .eyebrow {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--amber);
        }

        .auth-card-header h2 {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 24px;
            margin: 8px 0 4px;
        }

        .auth-card-header p {
            font-size: 13px;
            color: rgba(255,255,255,.75);
            margin: 0;
        }

        .auth-card-body {
            padding: 30px 32px 32px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FCEDEB;
            border: 1px solid #F3C9C2;
            color: var(--danger);
            font-size: 13.5px;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert i {
            margin-top: 2px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid var(--line);
            border-radius: 8px;
            font-size: 14.5px;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--ink);
            background: var(--paper);
            transition: border-color .15s ease, background .15s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--amber);
            background: #fff;
        }

        .btn-submit {
            width: 100%;
            background: var(--amber);
            color: var(--navy);
            border: none;
            padding: 13px 0;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            font-family: 'Inter', system-ui, sans-serif;
            cursor: pointer;
            margin-top: 6px;
            transition: background .15s ease, transform .15s ease;
        }

        .btn-submit:hover {
            background: #f0b158;
            transform: translateY(-1px);
        }

        .btn-submit:focus-visible {
            outline: 3px solid var(--teal);
            outline-offset: 2px;
        }

        .auth-switch {
            text-align: center;
            font-size: 13.5px;
            color: var(--muted);
            margin: 20px 0 0;
        }

        .auth-switch a {
            color: var(--navy);
            font-weight: 600;
            text-decoration: none;
        }

        .auth-switch a:hover {
            color: #C07C22;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .auth-card-body { padding: 20px; }
            .auth-card-header { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="auth-wrap">
    <section class="auth-card">

        <div class="auth-card-header">
            <span class="eyebrow">Local Transport Fare Guide</span>
            <h2>Welcome back</h2>
            <p>Log in to access your saved routes and preferences.</p>
        </div>

        <div class="auth-card-body">

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <span><?= sanitize($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= APP_URL ?>/auth/login.php">

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input id="email" name="email" type="email" value="<?= isset($email) ? sanitize($email) : '' ?>" placeholder="you@example.com" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input id="password" name="password" type="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <input class="btn-submit" type="submit" value="Log In">

            </form>

            <p class="auth-switch">
                Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Register now</a>
            </p>

        </div>

    </section>
</div>

</body>
</html>