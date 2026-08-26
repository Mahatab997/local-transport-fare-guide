<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}

$pdo = db_connect();

// Ensure 'updated_at' column exists
try {
    $pdo->exec("ALTER TABLE reports ADD COLUMN updated_at DATETIME DEFAULT NULL");
} catch (PDOException $e) {
    // Column likely exists; ignore
}

// ----- AJAX status update -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    $reportId = (int) ($_POST['update_report_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $validStatuses = ['pending', 'reviewing', 'resolved', 'rejected'];

    if ($reportId > 0 && in_array($newStatus, $validStatuses)) {
        try {
            $stmt = $pdo->prepare('UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $reportId]);
            echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }
    exit;
}

// ----- Regular POST (non‑AJAX) -----
$updateMessage = '';
$updateType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report_id'], $_POST['new_status'])) {
    $reportId = (int) $_POST['update_report_id'];
    $newStatus = $_POST['new_status'];
    $validStatuses = ['pending', 'reviewing', 'resolved', 'rejected'];

    if (in_array($newStatus, $validStatuses)) {
        try {
            $stmt = $pdo->prepare('UPDATE reports SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $reportId]);
            $updateMessage = 'Report status updated successfully.';
            $updateType = 'success';
        } catch (PDOException $e) {
            $updateMessage = 'Database error: ' . $e->getMessage();
            $updateType = 'error';
        }
    } else {
        $updateMessage = 'Invalid status selected.';
        $updateType = 'error';
    }
}

// ----- Filters & summary -----
$statusFilter = $_GET['status'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';

$summarySql = 'SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = "reviewing" THEN 1 ELSE 0 END) AS reviewing,
    SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS rejected
FROM reports';
$summary = $pdo->query($summarySql)->fetch(PDO::FETCH_ASSOC);

$sql = 'SELECT r.*, u.name AS user_name, u.email AS user_email
        FROM reports r
        INNER JOIN users u ON u.id = r.user_id
        WHERE 1 = 1';
$params = [];

if ($statusFilter !== 'all') {
    $sql .= ' AND r.status = :status';
    $params['status'] = $statusFilter;
}
if ($categoryFilter !== 'all') {
    $sql .= ' AND r.category = :category';
    $params['category'] = $categoryFilter;
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statuses = ['all' => 'All statuses', 'pending' => 'Pending', 'reviewing' => 'Reviewing', 'resolved' => 'Resolved', 'rejected' => 'Rejected'];
$categories = ['all' => 'All categories', 'fare' => 'Fare', 'route' => 'Route', 'service' => 'Service', 'app' => 'App', 'safety' => 'Safety', 'other' => 'Other'];

$badgeClass = [
    'pending' => 'warning',
    'reviewing' => 'info',
    'resolved' => 'success',
    'rejected' => 'danger',
];
?>
<style>
    /* ==============================================
       Hide Sidebar on Reports Page
    ============================================== */
    .sidebar {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 30px 40px !important;
        width: 100% !important;
    }
    .page-shell {
        max-width: 100% !important;
        padding: 0 !important;
    }

    /* ---------- Professional Admin Reports UI ---------- */
    :root {
        --primary: #1a4b7c;          /* dark sky blue */
        --primary-light: #e0f2fe;    /* light sky blue */
        --secondary: #f59e0b;
        --success: #22c55e;
        --danger: #ef4444;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --shadow-md: 0 4px 16px rgba(0,0,0,0.06);
        --shadow-lg: 0 10px 40px rgba(0,0,0,0.08);
        --radius: 12px;
    }

    body {
        background: var(--gray-50);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: var(--gray-800);
    }

    .report-admin-wrap {
        max-width: 1440px;
        margin: 24px auto;
        padding: 0 20px 50px;
    }

    /* ---- Header with dark sky blue background ---- */
    .page-heading {
        background: var(--primary);
        border-radius: var(--radius);
        padding: 24px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        color: #fff;
        box-shadow: 0 4px 20px rgba(26,75,124,0.25);
        margin-bottom: 24px;
    }
    .page-heading .eyebrow {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255,255,255,0.7);
        font-weight: 600;
        margin: 0 0 4px;
    }
    .page-heading h2 {
        margin: 0;
        font-size: 1.6rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.01em;
    }

    /* ---- White Back button ---- */
    .page-heading .btn {
        background: #ffffff;
        color: var(--primary);
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
    }
    .page-heading .btn:hover {
        background: #f0f4f8;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }

    /* ---- Alerts ---- */
    .alert {
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }
    .alert.success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }
    .alert.error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* ---- Summary Cards (light pastel) ---- */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    .summary-card {
        border-radius: 12px;
        padding: 16px 18px;
        background: #fff;
        box-shadow: var(--shadow-sm);
        text-align: center;
        border: 1px solid var(--gray-200);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }
    .summary-card span {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--gray-600);
        font-weight: 600;
        margin-bottom: 4px;
    }
    .summary-card strong {
        display: block;
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .summary-card.total { background: #e0f2fe; border-left: 4px solid #38bdf8; color: #0c4a6e; }
    .summary-card.pending { background: #fef3c7; border-left: 4px solid #fbbf24; color: #78350f; }
    .summary-card.reviewing { background: #e0f2fe; border-left: 4px solid #60a5fa; color: #1e3a5f; }
    .summary-card.resolved { background: #dcfce7; border-left: 4px solid #4ade80; color: #14532d; }
    .summary-card.rejected { background: #fef2f2; border-left: 4px solid #f87171; color: #991b1b; }

    /* ---- Toolbar ---- */
    .toolbar {
        background: #fff;
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 14px;
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: end;
        flex: 1;
    }
    .filter-form label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-600);
    }
    .filter-form select {
        min-width: 150px;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid var(--gray-200);
        background: var(--gray-50);
        font-size: 13px;
        color: var(--gray-800);
        transition: border-color 0.15s;
        cursor: pointer;
    }
    .filter-form select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(26,75,124,0.1);
    }
    .clear-btn {
        background: var(--gray-100);
        color: var(--gray-700);
        padding: 6px 14px;
        border-radius: 8px;
        border: 1px solid var(--gray-200);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s;
        align-self: flex-end;
    }
    .clear-btn:hover {
        background: var(--gray-200);
    }

    /* ---- Reports Grid – exactly 2 columns ---- */
    .reports-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    /* ---- Report Card ---- */
    .report-card {
        background: #fff;
        border-radius: var(--radius);
        border: 1px solid var(--gray-200);
        padding: 16px 18px 14px;
        box-shadow: var(--shadow-sm);
        transition: box-shadow 0.2s, transform 0.15s;
        display: flex;
        flex-direction: column;
    }
    .report-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-3px);
    }

    .report-top-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }
    .report-tag {
        background: #ebf3ff;
        color: #2f80ed;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 2px 10px;
        border-radius: 999px;
    }
    .report-severity {
        background: var(--gray-100);
        color: var(--gray-600);
        font-size: 11px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 999px;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 2px 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        border: none;
        cursor: pointer;
        min-width: 56px;
        height: 24px;
        transition: background 0.15s, transform 0.1s;
        margin-left: auto;
    }
    .status-badge:hover {
        transform: scale(1.05);
    }
    .status-badge.warning { background: #fef3c7; color: #92400e; }
    .status-badge.info { background: #dbeafe; color: #1e40af; }
    .status-badge.success { background: #dcfce7; color: #166534; }
    .status-badge.danger { background: #fef2f2; color: #991b1b; }

    .report-subject {
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin: 4px 0 6px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .report-reporter {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 14px;
        font-size: 0.8rem;
        color: var(--gray-600);
        margin-bottom: 4px;
    }
    .report-reporter strong {
        color: var(--gray-800);
        font-weight: 600;
    }

    .report-route-date {
        display: flex;
        flex-wrap: wrap;
        gap: 4px 14px;
        font-size: 0.78rem;
        color: var(--gray-600);
        margin-bottom: 8px;
    }
    .report-route-date strong {
        color: var(--gray-800);
        font-weight: 600;
    }

    .report-message {
        margin: 0 0 10px;
        color: var(--gray-700);
        line-height: 1.5;
        font-size: 0.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }

    .report-actions {
        border-top: 1px solid var(--gray-200);
        padding-top: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .status-update-form {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        flex-wrap: wrap;
    }
    .status-update-form label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-600);
    }
    .status-update-form select {
        padding: 4px 10px;
        border-radius: 6px;
        border: 1px solid var(--gray-200);
        background: var(--gray-50);
        font-size: 0.8rem;
        color: var(--gray-800);
        cursor: pointer;
    }
    .update-btn {
        background: var(--primary);
        color: #fff;
        padding: 4px 14px;
        border: 0;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    .update-btn:hover {
        background: #1e3a5f;
    }
    .view-details-btn {
        background: var(--gray-100);
        color: var(--gray-700);
        padding: 4px 14px;
        border: 1px solid var(--gray-200);
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
    }
    .view-details-btn:hover {
        background: var(--gray-200);
    }

    /* ---- Empty State ---- */
    .empty-state {
        grid-column: 1 / -1;
        background: #fff;
        border-radius: var(--radius);
        padding: 40px 20px;
        text-align: center;
        border: 1px solid var(--gray-200);
    }
    .empty-state h3 {
        color: var(--gray-800);
        margin-bottom: 8px;
        font-weight: 600;
    }
    .empty-state p {
        color: var(--gray-600);
        font-size: 0.9rem;
    }

    /* ---- Toast ---- */
    .toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: var(--gray-800);
        color: #fff;
        padding: 12px 22px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        font-weight: 600;
        font-size: 0.9rem;
        z-index: 9999;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s, transform 0.3s;
        pointer-events: none;
    }
    .toast.show {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .toast.success { background: #16a34a; }
    .toast.error { background: #dc2626; }

    /* ---- Responsive: stack to 1 column on smaller screens ---- */
    @media (max-width: 820px) {
        .reports-list {
            grid-template-columns: 1fr;
        }
        .page-heading {
            flex-direction: column;
            align-items: flex-start;
        }
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-form select {
            width: 100%;
        }
        .report-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .status-update-form {
            flex-wrap: wrap;
            justify-content: center;
        }
        .summary-grid {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        }
    }

    @media (max-width: 480px) {
        .summary-grid {
            grid-template-columns: 1fr 1fr;
        }
        .report-card {
            padding: 14px;
        }
        .report-top-row {
            gap: 4px;
        }
        .report-subject {
            font-size: 0.9rem;
        }
    }
</style>

<section class="report-admin-wrap">
    <!-- Header with dark sky blue background -->
    <div class="page-heading">
        <div>
            <p class="eyebrow">Operations Dashboard</p>
            <h2>Community Reports</h2>
        </div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="btn">← Back to Dashboard</a>
    </div>

    <!-- Alerts -->
    <?php if ($updateMessage): ?>
        <div class="alert <?= htmlspecialchars($updateType) ?>"><?= htmlspecialchars($updateMessage) ?></div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card total"><span>Total</span><strong><?= (int) ($summary['total'] ?? 0) ?></strong></div>
        <div class="summary-card pending"><span>Pending</span><strong><?= (int) ($summary['pending'] ?? 0) ?></strong></div>
        <div class="summary-card reviewing"><span>Reviewing</span><strong><?= (int) ($summary['reviewing'] ?? 0) ?></strong></div>
        <div class="summary-card resolved"><span>Resolved</span><strong><?= (int) ($summary['resolved'] ?? 0) ?></strong></div>
        <div class="summary-card rejected"><span>Rejected</span><strong><?= (int) ($summary['rejected'] ?? 0) ?></strong></div>
    </div>

    <!-- Filters -->
    <div class="toolbar">
        <form method="get" class="filter-form" id="filterForm">
            <label>
                Status
                <select name="status" onchange="this.form.submit()">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Category
                <select name="category" onchange="this.form.submit()">
                    <?php foreach ($categories as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $categoryFilter === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($statusFilter !== 'all' || $categoryFilter !== 'all'): ?>
                <a href="<?= APP_URL ?>/admin/reports/index.php" class="clear-btn">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Reports Grid (2 columns) -->
    <div class="reports-list" id="reportsContainer">
        <?php if (empty($reports)): ?>
            <div class="empty-state">
                <h3>No Reports Found</h3>
                <p>No community reports match the selected filters yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <article class="report-card" data-report-id="<?= (int) $report['id'] ?>">
                    <div class="report-top-row">
                        <span class="report-tag"><?= htmlspecialchars(ucfirst($report['category'])) ?></span>
                        <span class="report-severity"><?= htmlspecialchars(ucfirst($report['severity'])) ?></span>
                        <button class="status-badge <?= htmlspecialchars($badgeClass[$report['status']] ?? 'info') ?>" data-status="<?= htmlspecialchars($report['status']) ?>" title="Click to cycle status">
                            <?= htmlspecialchars(ucfirst($report['status'])) ?>
                        </button>
                    </div>

                    <div class="report-subject"><?= htmlspecialchars($report['subject']) ?></div>

                    <div class="report-reporter">
                        <span><strong>Reporter:</strong> <?= htmlspecialchars($report['user_name']) ?></span>
                        <span><strong>Email:</strong> <?= htmlspecialchars($report['user_email']) ?></span>
                    </div>

                    <div class="report-route-date">
                        <span><strong>Route:</strong> <?= htmlspecialchars($report['route_name'] ?: 'Not specified') ?></span>
                        <span><strong>Submitted:</strong> <?= date('d M Y, h:i A', strtotime($report['created_at'])) ?></span>
                    </div>

                    <p class="report-message"><?= nl2br(htmlspecialchars($report['details'])) ?></p>

                    <div class="report-actions">
                        <form method="post" class="status-update-form" data-ajax="1">
                            <input type="hidden" name="update_report_id" value="<?= (int) $report['id'] ?>">
                            <label>
                                <span>Update Status</span>
                                <select name="new_status" class="status-select">
                                    <option value="pending" <?= $report['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="reviewing" <?= $report['status'] === 'reviewing' ? 'selected' : '' ?>>Reviewing</option>
                                    <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                    <option value="rejected" <?= $report['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                </select>
                            </label>
                            <button type="submit" class="update-btn">Update</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ---- AJAX status update from dropdown form ----
        const forms = document.querySelectorAll('.status-update-form[data-ajax="1"]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('ajax', '1');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        const card = this.closest('.report-card');
                        const badge = card.querySelector('.status-badge');
                        const newStatus = this.querySelector('select[name="new_status"]').value;
                        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        badge.className = 'status-badge ' + getBadgeClass(newStatus);
                        badge.dataset.status = newStatus;
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('An error occurred. Please try again.', 'error'));
            });
        });

        // ---- Click on status badge to cycle ----
        document.querySelectorAll('.status-badge').forEach(badge => {
            badge.addEventListener('click', function() {
                const statuses = ['pending', 'reviewing', 'resolved', 'rejected'];
                const current = this.dataset.status;
                const nextIndex = (statuses.indexOf(current) + 1) % statuses.length;
                const newStatus = statuses[nextIndex];

                const card = this.closest('.report-card');
                const reportId = card.dataset.reportId;
                const select = card.querySelector('.status-select');
                if (select) select.value = newStatus;

                const formData = new FormData();
                formData.append('ajax', '1');
                formData.append('update_report_id', reportId);
                formData.append('new_status', newStatus);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        this.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        this.className = 'status-badge ' + getBadgeClass(newStatus);
                        this.dataset.status = newStatus;
                        if (select) select.value = newStatus;
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(() => showToast('An error occurred. Please try again.', 'error'));
            });
        });

        function getBadgeClass(status) {
            const map = { 'pending': 'warning', 'reviewing': 'info', 'resolved': 'success', 'rejected': 'danger' };
            return map[status] || 'info';
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            void toast.offsetWidth;
            toast.classList.add('show');
            clearTimeout(toast._timeout);
            toast._timeout = setTimeout(() => toast.classList.remove('show'), 4000);
        }

    });
</script>