<?php
$pageTitle = 'Submit a Report';
require_once __DIR__ . '/../includes/header.php';
require_login();

if (is_admin()) {
    redirect(APP_URL . '/admin/dashboard.php');
}

$pdo = db_connect();

// ----- Create reports table if it does not exist -----
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        severity VARCHAR(20) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        route_name VARCHAR(100) DEFAULT NULL,
        details TEXT NOT NULL,
        status ENUM('pending', 'reviewing', 'resolved', 'rejected') DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Table may already exist; ignore
}

$currentUser = get_current_user_record();
$statusMessage = '';
$statusType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ($currentUser['name'] ?? ''));
    $email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
    $category = in_array($_POST['category'] ?? 'other', ['fare', 'route', 'service', 'app', 'safety', 'other'], true)
        ? $_POST['category']
        : 'other';
    $severity = in_array($_POST['severity'] ?? 'medium', ['low', 'medium', 'high', 'critical'], true)
        ? $_POST['severity']
        : 'medium';
    $subject = trim($_POST['subject'] ?? '');
    $routeName = trim($_POST['route_name'] ?? '');
    $details = trim($_POST['details'] ?? '');

    if ($subject === '' || $details === '' || $name === '' || $email === '') {
        $statusMessage = 'Please complete your name, email, subject, and report details before submitting.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO reports (user_id, name, email, category, severity, subject, route_name, details, status, created_at)
             VALUES (:user_id, :name, :email, :category, :severity, :subject, :route_name, :details, :status, NOW())'
        );

        $stmt->execute([
            'user_id' => (int) $_SESSION['user_id'],
            'name' => $name,
            'email' => $email,
            'category' => $category,
            'severity' => $severity,
            'subject' => $subject,
            'route_name' => $routeName !== '' ? $routeName : null,
            'details' => $details,
            'status' => 'pending',
        ]);

        $statusMessage = 'Your report has been submitted successfully. Admin will review it shortly.';
        $statusType = 'success';
        $_POST = [];
    }
}

$recentReports = $pdo->prepare(
    'SELECT category, subject, status, created_at FROM reports WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5'
);
$recentReports->execute(['user_id' => (int) $_SESSION['user_id']]);
$recentReports = $recentReports->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="report-page">
    <div class="page-header">
        <div>
            <p class="eyebrow">User support</p>
            <h1>Report a Transport Issue</h1>
        </div>
        <a href="<?= APP_URL ?>/user/dashboard.php" class="button secondary">Back to dashboard</a>
    </div>

    <?php if ($statusMessage !== ''): ?>
        <div class="alert <?= htmlspecialchars($statusType) ?>"><?= htmlspecialchars($statusMessage) ?></div>
    <?php endif; ?>

    <div class="report-grid">
        <form class="report-form" method="post" action="">
            <div class="form-row two-columns">
                <label>
                    Full name
                    <input type="text" name="name" value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" required>
                </label>
                <label>
                    Email address
                    <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required>
                </label>
            </div>

            <div class="form-row two-columns">
                <label>
                    Report subject
                    <input type="text" name="subject" placeholder="Example: Fare pricing issue on Downtown Express" required>
                </label>
                <label>
                    Category
                    <select name="category">
                        <option value="fare">Fare</option>
                        <option value="route">Route</option>
                        <option value="service">Service</option>
                        <option value="app">App</option>
                        <option value="safety">Safety</option>
                        <option value="other" selected>Other</option>
                    </select>
                </label>
            </div>

            <div class="form-row two-columns">
                <label>
                    Severity
                    <select name="severity">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </label>
                <label>
                    Route name (optional)
                    <input type="text" name="route_name" placeholder="Example: Downtown Express">
                </label>
            </div>

            <label>
                Describe the problem or suggestion
                <textarea name="details" rows="8" placeholder="Please explain the issue, what route or fare is affected, and any relevant details for a faster review." required></textarea>
            </label>

            <div class="form-actions">
                <button type="submit" class="button primary">Submit report</button>
                <button type="reset" class="button secondary light">Clear</button>
            </div>
        </form>

        <aside class="info-panel">
            <h3>Report guidelines</h3>
            <ul>
                <li>Share the specific route, location, or fare involved.</li>
                <li>Include an accurate description so administrators can act quickly.</li>
                <li>Report safety hazards, outdated fares, or app problems here.</li>
                <li>Suggestions for improving the Local Transport Fare Guide are welcome.</li>
            </ul>

            <div class="mini-history">
                <h4>Your recent reports</h4>
                <?php if (empty($recentReports)): ?>
                    <p class="muted">No reports submitted yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recentReports as $report): ?>
                            <li>
                                <span class="dot <?= htmlspecialchars($report['status']) ?>"></span>
                                <div>
                                    <strong><?= htmlspecialchars($report['subject']) ?></strong>
                                    <small><?= htmlspecialchars(ucfirst($report['category'])) ?> · <?= date('d M Y', strtotime($report['created_at'])) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<style>
    body {
        background: linear-gradient(180deg, #edf4fb 0%, #f5f8fc 100%);
        color: #1a2436;
    }

    .report-page {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 18px 50px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 22px;
    }

    .eyebrow {
        margin: 0 0 8px;
        color: #49658b;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 11px;
        font-weight: 700;
    }

    .page-header h1 {
        margin: 0;
        color: #10233f;
        font-size: clamp(2rem, 3vw, 2.8rem);
    }

    .button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        border-radius: 12px;
        padding: 12px 18px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }

    .button:hover {
        transform: translateY(-1px);
        opacity: 0.96;
    }

    .button.primary {
        background: linear-gradient(135deg, #f2b45e 0%, #e1902a 100%);
        color: #162844;
    }

    .button.secondary {
        background: #10233f;
        color: #fff;
    }

    .button.secondary.light {
        background: #edf4fb;
        color: #10233f;
        border: 1px solid #d9e2ef;
    }

    .alert {
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 22px;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .alert.success {
        background: #eafaf3;
        border-color: #cceee1;
        color: #116a47;
    }

    .alert.error {
        background: #fff2f2;
        border-color: #ffd6d6;
        color: #aa2f2f;
    }

    .report-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(260px, 0.95fr);
        gap: 22px;
        align-items: start;
    }

    .report-form,
    .info-panel {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #e5ebf4;
        border-radius: 20px;
        padding: 22px;
        box-shadow: 0 10px 30px rgba(16, 35, 63, 0.05);
    }

    .form-row {
        display: grid;
        gap: 18px;
        margin-bottom: 18px;
    }

    .two-columns {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    label {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 0.96rem;
        color: #31415d;
        font-weight: 600;
    }

    input, select, textarea {
        width: 100%;
        border: 1px solid #d5deeb;
        border-radius: 12px;
        background: #f9fbff;
        padding: 12px 14px;
        font: inherit;
        color: #1a2436;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input:focus, select:focus, textarea:focus {
        outline: none;
        border-color: #88acd8;
        box-shadow: 0 0 0 4px rgba(47, 128, 237, 0.08);
    }

    textarea {
        resize: vertical;
        min-height: 170px;
    }

    .form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }

    .info-panel h3,
    .mini-history h4 {
        margin: 0 0 16px;
        color: #10233f;
    }

    .info-panel ul {
        margin: 0;
        padding-left: 18px;
        color: #43577f;
        line-height: 1.8;
    }

    .mini-history {
        margin-top: 24px;
        padding-top: 22px;
        border-top: 1px solid #ecf0f6;
    }

    .mini-history ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 12px;
    }

    .mini-history li {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        background: #f7faff;
        border: 1px solid #ebf1f8;
        border-radius: 12px;
        padding: 12px;
    }

    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-top: 6px;
        flex-shrink: 0;
        background: #c6d3e7;
    }

    .dot.pending { background: #f7b731; }
    .dot.reviewing { background: #2f80ed; }
    .dot.resolved { background: #2ea66f; }
    .dot.rejected { background: #d9534f; }

    .mini-history strong {
        display: block;
        color: #1f2d45;
        margin-bottom: 4px;
    }

    .mini-history small {
        color: #5d6f8d;
    }

    .muted {
        color: #627391;
    }

    @media (max-width: 840px) {
        .report-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 620px) {
        .page-header,
        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .two-columns {
            grid-template-columns: 1fr;
        }
    }
</style>
