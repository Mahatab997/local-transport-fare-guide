<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_login();

// Prevent admin from accessing this page (they have their own)
if (is_admin()) {
    redirect('../admin/profile/index.php');
}

$pdo = db_connect();

// Get current user data
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('../logout.php');
}

$errors = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate name
    if (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check if email is already used by another user
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $check->execute(['email' => $email, 'id' => $user['id']]);
        if ($check->fetch()) {
            $errors[] = 'This email is already in use by another account.';
        }
    }

    // If password fields are filled, validate them
    $passwordUpdate = false;
    if (!empty($newPassword) || !empty($confirmPassword) || !empty($currentPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required to change password.';
        } else {
            // Verify current password
            $passStmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
            $passStmt->execute(['id' => $user['id']]);
            $userPass = $passStmt->fetchColumn();
            if (!password_verify($currentPassword, $userPass)) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        if (empty($newPassword)) {
            $errors[] = 'New password cannot be empty.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            $passwordUpdate = true;
        }
    }

    if (empty($errors)) {
        try {
            $params = [':name' => $name, ':email' => $email, ':id' => $user['id']];
            $sql = 'UPDATE users SET name = :name, email = :email';

            if ($passwordUpdate) {
                $sql .= ', password = :password';
                $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id';

            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);

            // Update session name if changed
            $_SESSION['user_name'] = $name;

            $success = 'Profile updated successfully.';
            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<style>
    /* ── Two‑column profile layout copied from admin profile (colors adapted) ── */
    .profile-container {
        --navy: #0f2545;
        --navy-2: #123a70;
        --sky: #0ea5e9;
        --sky-light: #e0f4ff;
        --green: #10b981;
        --green-light: #ecfdf5;
        --orange: #f97316;
        --orange-light: #fff3e8;
        --ink: #0f2545;
        --muted: #5c7290;
        --line: #dfeaf5;

        max-width: 1100px;
        margin: 30px auto;
        padding: 0 20px 60px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: var(--ink);
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #fff;
        color: var(--navy-2);
        border: 1.5px solid var(--sky);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        text-decoration: none;
        transition: background .15s ease, transform .12s ease, color .15s ease;
        margin-bottom: 22px;
    }
    .back-btn:hover {
        background: var(--sky);
        color: #fff;
        transform: translateX(-2px);
    }

    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 28px;
        align-items: start;
    }

    .profile-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 34px -14px rgba(15, 37, 69, 0.25);
        border: 1px solid var(--line);
        overflow: hidden;
        margin-bottom: 0;
    }

    .profile-header {
        position: relative;
        background: linear-gradient(135deg, var(--navy), var(--navy-2) 55%, var(--sky) 130%);
        color: #fff;
        padding: 34px 24px 28px;
        text-align: center;
        overflow: hidden;
    }

    .profile-header::after {
        content: "";
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(249,115,22,0.35), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.14);
        border: 2px solid rgba(255,255,255,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin: 0 auto 12px;
        position: relative;
        z-index: 1;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .profile-header h2 { margin: 0 0 6px; font-size: 1.3rem; font-weight: 800; }

    .profile-header .role-badge { background: var(--orange); color: #fff; padding: 3px 14px; border-radius: 999px; font-weight:700; }

    .profile-body { padding: 22px 20px 24px; }

    .info-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
    .info-item { background: var(--sky-light); border: 1px solid #cdeaff; border-radius: 10px; padding: 10px 14px; }
    .info-item .label { font-size: 0.7rem; text-transform: uppercase; color: var(--navy-2); font-weight:700; }
    .info-item .value { font-size: 0.95rem; font-weight:700; color:var(--navy); }

    .right-column { display:flex; flex-direction:column; gap:20px; }
    .section-card { background:#fff; border:1px solid var(--line); border-radius:18px; padding:26px 28px; box-shadow:0 4px 18px -12px rgba(15,37,69,0.18); }
    .section-title { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
    .section-title .icon-badge { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; color:#fff; }
    .section-title.details .icon-badge { background: var(--sky); }
    .section-title.security .icon-badge { background: var(--orange); }
    .section-title h3 { margin:0; font-size:1.05rem; color:var(--navy); }
    .section-title p { margin:1px 0 0; font-size:0.8rem; color:var(--muted); }

    .form-group { margin-bottom:16px; }
    .form-group label { display:block; font-weight:700; margin-bottom:6px; color:var(--navy); }
    .form-group input { width:100%; padding:11px 14px; border:1.5px solid var(--line); border-radius:10px; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .helper { font-size:0.78rem; color:var(--muted); margin-top:6px; }

    .btn-save { background: linear-gradient(135deg, var(--green), #059669); color:#fff; border:none; border-radius:10px; padding:13px 30px; font-weight:700; }

    .alert { border-radius:12px; padding:13px 16px; font-size:0.9rem; margin-bottom:20px; }
    .alert.error { background:#fff1ee; color:#c2410c; border:1px solid #fed7c3; }
    .alert.success { background: var(--green-light); color:#047857; border:1px solid #a7f3d0; display:flex; align-items:center; gap:8px; }

    /* Make page header texts white as requested */
    .page-header .eyebrow, .page-header h1 { color: #fff !important; }

    @media (max-width: 820px) { .profile-grid { grid-template-columns: 1fr; } .form-row { grid-template-columns:1fr; } }
    @media (max-width: 480px) { .profile-container { padding:0 12px 40px; } }
</style>

<div class="profile-container">
    <a href="<?= APP_URL ?>/user/dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <!-- Alerts (full width) -->
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <strong>Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><i class="fas fa-circle-check"></i> <?= sanitize($success) ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?= sanitize($user['name']) ?></h2>
                <div class="role-badge"><i class="fas fa-user-tag"></i> <?= ucfirst($user['role']) ?></div>
            </div>
            <div class="profile-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label"><i class="fas fa-id-badge"></i> Full Name</span>
                        <span class="value"><?= sanitize($user['name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="value"><?= sanitize($user['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-user-tag"></i> Role</span>
                        <span class="value"><?= ucfirst($user['role']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-calendar"></i> Member Since</span>
                        <span class="value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-column">
            <form method="post" autocomplete="off">
                <div class="section-card">
                    <div class="section-title details">
                        <div class="icon-badge"><i class="fas fa-user-pen"></i></div>
                        <div>
                            <h3>Profile Details</h3>
                            <p>Update your name and email address.</p>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= sanitize($user['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= sanitize($user['email']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title security">
                        <div class="icon-badge"><i class="fas fa-lock"></i></div>
                        <div>
                            <h3>Change Password <span class="optional">(optional)</span></h3>
                            <p>Leave these blank to keep your current password.</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                        </div>
                    </div>
                    <div class="helper">Both current and new password are required if you're changing it.</div>
                </div>

                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Update Profile</button>
            </form>
        </div>
    </div>
</div>
