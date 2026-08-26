<?php
$pageTitle = 'Admin Profile';
require_once __DIR__ . '/../../includes/header.php';
require_login();

if (!is_admin()) {
    redirect('../index.php');
}

$pdo = db_connect();

// Get current admin data
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id');
$stmt->execute(['id' => $_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin) {
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
        $check->execute(['email' => $email, 'id' => $admin['id']]);
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
            $passStmt->execute(['id' => $admin['id']]);
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
            $params = [':name' => $name, ':email' => $email, ':id' => $admin['id']];
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
            // Refresh admin data
            $admin['name'] = $name;
            $admin['email'] = $email;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<style>
    /* ==============================================
       Hide Sidebar on Profile Page
    ============================================== */
    .sidebar {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 30px 40px !important;
        width: 100% !important;
    }
    /* If your header uses a page-shell wrapper, adjust it */
    .page-shell {
        max-width: 100% !important;
        padding: 0 !important;
    }
    /* Ensure the container is centered */
    .profile-container {
        max-width: 1120px;
        margin: 0 auto;
        padding: 0 20px 60px;
    }

    /* ==============================================
       Admin Profile – Clean & Professional UI
    ============================================== */

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

        max-width: 1120px;
        margin: 0 auto;
        padding: 0 20px 60px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--ink);
    }

    /* ---- Page Header ---- */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 28px;
        padding: 20px 24px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(15,37,69,0.06);
        border: 1px solid var(--line);
    }

    .page-header .title-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .page-header .eyebrow {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted);
        font-weight: 600;
    }
    .page-header h1 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--navy);
        letter-spacing: -0.01em;
    }

    /* ---- Back Button (inside header) ---- */
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--sky-light);
        color: var(--navy-2);
        border: 1.5px solid var(--sky);
        padding: 8px 20px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        transition: background 0.15s, transform 0.12s, box-shadow 0.15s;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.08);
    }
    .back-btn:hover {
        background: var(--sky);
        color: #fff;
        transform: translateX(-2px);
        box-shadow: 0 4px 16px rgba(14, 165, 233, 0.2);
    }

    /* ---- Alerts ---- */
    .alert {
        border-radius: 12px;
        padding: 14px 18px;
        font-size: 0.9rem;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        border: 1px solid transparent;
    }
    .alert.error {
        background: #fff1ee;
        color: #c2410c;
        border-color: #fed7c3;
    }
    .alert.error strong { color: #9a2f0c; }
    .alert.error ul { margin: 4px 0 0 18px; padding: 0; }
    .alert.success {
        background: var(--green-light);
        color: #047857;
        border-color: #a7f3d0;
    }
    .alert.success i { font-size: 1.1rem; }

    /* ---- Two‑column grid ---- */
    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 30px;
        align-items: start;
    }

    /* ---- Left column: Profile Card ---- */
    .profile-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 12px 40px -12px rgba(15,37,69,0.15);
        border: 1px solid var(--line);
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .profile-card:hover {
        box-shadow: 0 16px 48px -12px rgba(15,37,69,0.2);
    }

    .profile-header {
        position: relative;
        background: linear-gradient(145deg, var(--navy), var(--navy-2) 60%, var(--sky) 140%);
        color: #fff;
        padding: 32px 20px 26px;
        text-align: center;
        overflow: hidden;
    }
    .profile-header::after {
        content: "";
        position: absolute;
        top: -40px;
        right: -40px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(249,115,22,0.2), transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(4px);
        border: 2px solid rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 34px;
        margin: 0 auto 12px;
        position: relative;
        z-index: 1;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transition: transform 0.2s;
    }
    .profile-avatar:hover {
        transform: scale(1.05);
    }

    .profile-header h2 {
        margin: 0 0 4px;
        font-size: 1.3rem;
        font-weight: 700;
        position: relative;
        z-index: 1;
        letter-spacing: -0.01em;
    }

    .profile-header .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--orange);
        color: #fff;
        padding: 4px 16px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 12px rgba(249,115,22,0.3);
    }

    .profile-body {
        padding: 20px 18px 22px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 2px;
        background: var(--sky-light);
        border: 1px solid #d4e8fa;
        border-radius: 10px;
        padding: 10px 14px;
        transition: border-color 0.15s;
    }
    .info-item:hover {
        border-color: var(--sky);
    }

    .info-item .label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--navy-2);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .info-item .value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--navy);
        word-break: break-word;
    }

    /* ---- Right column: Form Cards Side‑by‑Side ---- */
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        align-items: start;
    }

    .section-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 22px 24px;
        box-shadow: 0 4px 20px -12px rgba(15,37,69,0.08);
        transition: box-shadow 0.2s;
        height: 100%;
    }
    .section-card:hover {
        box-shadow: 0 8px 28px -12px rgba(15,37,69,0.12);
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }
    .section-title .icon-badge {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        color: #fff;
        flex-shrink: 0;
    }
    .section-title.details .icon-badge { background: var(--sky); }
    .section-title.security .icon-badge { background: var(--orange); }

    .section-title h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--navy);
    }
    .section-title p {
        margin: 1px 0 0;
        font-size: 0.75rem;
        color: var(--muted);
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 14px;
    }
    .form-group:last-child { margin-bottom: 0; }

    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 0.82rem;
        margin-bottom: 4px;
        color: var(--navy);
    }
    .form-group .optional {
        font-weight: 400;
        color: var(--muted);
        font-size: 0.75rem;
    }

    .form-group input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid var(--line);
        border-radius: 10px;
        font-size: 0.9rem;
        font-family: inherit;
        color: var(--ink);
        background: #fafcff;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        box-sizing: border-box;
        outline: none;
    }
    .form-group input:focus {
        border-color: var(--sky);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(14,165,233,0.1);
    }
    .form-group input::placeholder {
        color: #b0c4d8;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .helper {
        font-size: 0.75rem;
        color: var(--muted);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .helper i {
        color: var(--sky);
        font-size: 0.85rem;
    }

    /* Save Button */
    .btn-save {
        background: linear-gradient(135deg, var(--green), #059669);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 14px 32px;
        font-weight: 700;
        font-size: 0.95rem;
        cursor: pointer;
        transition: box-shadow 0.2s, transform 0.1s;
        box-shadow: 0 8px 24px -8px rgba(16,185,129,0.4);
        display: inline-flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        justify-content: center;
        margin-top: 4px;
    }
    .btn-save:hover {
        box-shadow: 0 12px 32px -8px rgba(16,185,129,0.5);
        transform: translateY(-2px);
    }
    .btn-save:active {
        transform: scale(0.97);
    }
    .btn-save i {
        font-size: 1.05rem;
    }

    /* ---- Responsive ---- */
    @media (max-width: 820px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        .profile-card {
            margin-bottom: 0;
        }
        .form-grid {
            grid-template-columns: 1fr;
        }
        .form-row {
            grid-template-columns: 1fr;
        }
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 480px) {
        .profile-container {
            padding: 0 12px 40px;
        }
        .profile-header {
            padding: 20px 16px 18px;
        }
        .profile-body {
            padding: 16px;
        }
        .section-card {
            padding: 18px 16px;
        }
        .btn-save {
            padding: 12px 20px;
            font-size: 0.9rem;
        }
    }
</style>

<div class="profile-container">

    <!-- Page Header with Back Button -->
    <div class="page-header">
        <div class="title-group">
            <span class="eyebrow">Admin Settings</span>
            <h1>Profile</h1>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Alerts (full width) -->
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <strong>Please fix the following:</strong>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= sanitize($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success">
            <i class="fas fa-circle-check"></i>
            <span><?= sanitize($success) ?></span>
        </div>
    <?php endif; ?>

    <!-- Two‑column grid -->
    <div class="profile-grid">

        <!-- LEFT COLUMN: Avatar + Info (stacked) -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2><?= sanitize($admin['name']) ?></h2>
                <div class="role-badge"><i class="fas fa-shield-halved"></i> <?= ucfirst($admin['role']) ?></div>
            </div>
            <div class="profile-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="label"><i class="fas fa-id-badge"></i> Full Name</span>
                        <span class="value"><?= sanitize($admin['name']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="value"><?= sanitize($admin['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-shield-halved"></i> Role</span>
                        <span class="value"><?= ucfirst($admin['role']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><i class="fas fa-calendar"></i> Member Since</span>
                        <span class="value"><?= date('M d, Y', strtotime($admin['created_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Side‑by‑Side Form Cards -->
        <div class="right-column">

            <form method="post" autocomplete="off">

                <div class="form-grid">

                    <!-- Profile Details Card -->
                    <div class="section-card">
                        <div class="section-title details">
                            <div class="icon-badge"><i class="fas fa-user-pen"></i></div>
                            <div>
                                <h3>Profile Details</h3>
                                <p>Update your name and email.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?= sanitize($admin['name']) ?>" required autocomplete="name">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= sanitize($admin['email']) ?>" required autocomplete="email">
                        </div>
                    </div>

                    <!-- Security Card -->
                    <div class="section-card">
                        <div class="section-title security">
                            <div class="icon-badge"><i class="fas fa-lock"></i></div>
                            <div>
                                <h3>Change Password <span class="optional">(optional)</span></h3>
                                <p>Leave blank to keep current.</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password" autocomplete="current-password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Min. 8 characters" autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" autocomplete="new-password">
                        </div>

                        <div class="helper">
                            <i class="fas fa-info-circle"></i>
                            Current + new password required to change.
                        </div>
                    </div>

                </div>

                <!-- Save Button -->
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Update Profile
                </button>

            </form>

        </div>

    </div>
</div>
