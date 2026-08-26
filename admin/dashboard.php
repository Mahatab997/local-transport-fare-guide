<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}

$pageTitle = 'Admin Dashboard';
$pdo = db_connect();

function safeCount($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$totalRoutes    = safeCount($pdo, 'routes');
$totalFares     = safeCount($pdo, 'fares');
$totalLocations = safeCount($pdo, 'locations');
$totalUsers     = safeCount($pdo, 'users');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_route_id'])) {
    $deleteRouteId = (int) $_POST['delete_route_id'];
    if ($deleteRouteId > 0) {
        $pdo->prepare('DELETE FROM route_fares WHERE route_id = :route_id')->execute(['route_id' => $deleteRouteId]);
        $pdo->prepare('DELETE FROM routes WHERE id = :id')->execute(['id' => $deleteRouteId]);
    }
    header('Location: ' . APP_URL . '/admin/dashboard.php');
    exit;
}

$routeRows = $pdo->query('SELECT r.id, r.name, r.start_location, r.end_location, r.description FROM routes r ORDER BY r.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$adminName = $_SESSION['user_name'] ?? 'Administrator';

// Determine time-based greeting
$hour = date('G');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

// Color palette for route rows
$routeColors = [
    'sky'    => ['bg' => '#e0f2fe', 'border' => '#7dd3fc'],
    'blue'   => ['bg' => '#dbeafe', 'border' => '#93c5fd'],
    'orange' => ['bg' => '#fef3c7', 'border' => '#fcd34d'],
    'green'  => ['bg' => '#dcfce7', 'border' => '#86efac'],
    'purple' => ['bg' => '#ede9fe', 'border' => '#c4b5fd'],
    'pink'   => ['bg' => '#fce7f3', 'border' => '#f9a8d4'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · Local Transport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz@14..32&family=Archivo:wght@800&display=swap" rel="stylesheet">
    <style>
        /* ---------- Root Variables ---------- */
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
            --shadow: 0 4px 16px rgba(19, 36, 65, 0.08);
            --radius: 12px;
            --sidebar-width: 240px;
        }

        /* ---------- Reset & Base ---------- */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--paper);
            color: var(--ink);
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--navy);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 20px;
        }
        .sidebar-brand .logo {
            font-family: 'Archivo', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: var(--amber);
            letter-spacing: -0.02em;
        }
        .sidebar-brand .logo i {
            margin-right: 8px;
        }
        .sidebar-brand .sub {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 2px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 0 12px;
        }
        .sidebar-nav .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.3);
            padding: 12px 12px 6px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
            margin-bottom: 2px;
        }
        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        .sidebar-nav a.active {
            background: var(--amber);
            color: var(--navy);
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 20px 20px 0;
            border-top: 1px solid rgba(255,255,255,0.08);
            margin-top: 20px;
        }
        .sidebar-footer .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
        }
        .sidebar-footer .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--amber);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            text-transform: uppercase;
        }
        .sidebar-footer .admin-details {
            flex: 1;
        }
        .sidebar-footer .admin-details .name {
            font-weight: 600;
            font-size: 14px;
            color: #fff;
        }
        .sidebar-footer .admin-details .role {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
        }
        .sidebar-footer .logout-btn {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
            margin-top: 12px;
        }
        .sidebar-footer .logout-btn:hover {
            background: rgba(255,255,255,0.16);
        }

        /* ---------- Main Content ---------- */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px 32px;
            min-height: 100vh;
        }

        .top-bar {
            display: none;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .top-bar .menu-toggle {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--ink);
            cursor: pointer;
        }
        .top-bar .page-title {
            font-weight: 600;
            font-size: 18px;
        }

        /* ---------- Welcome Banner (dark sky blue only) ---------- */
        .welcome-banner {
            background: #1a4b7c; /* solid dark sky blue */
            border-radius: var(--radius);
            padding: 32px 34px;
            color: #fff;
            margin-bottom: 28px;
            box-shadow: 0 8px 30px rgba(26, 75, 124, 0.3);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.06), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-banner .eyebrow {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.8);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .welcome-banner h1 {
            margin: 0 0 6px 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .welcome-banner p {
            margin: 0 0 16px 0;
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
            max-width: 600px;
            line-height: 1.6;
        }

        .date-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 6px 18px 6px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.9);
            backdrop-filter: blur(4px);
        }

        .date-chip i {
            color: var(--amber);
        }

        /* ---------- Stats Grid (light colors) ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            gap: 16px;
            border-left: 4px solid transparent;
            background: #f8faff;
        }
        .stat-card:hover {
            transform: translateY(-4px) translateX(2px);
            box-shadow: 0 8px 24px rgba(19, 36, 65, 0.12);
        }
        .stat-card:nth-child(1) {
            background: #e6f2ff;
            border-left-color: #2C7BE5;
        }
        .stat-card:nth-child(2) {
            background: #f0f0ff;
            border-left-color: #8B5CF6;
        }
        .stat-card:nth-child(3) {
            background: #e6faf5;
            border-left-color: #2F8F6E;
        }
        .stat-card:nth-child(4) {
            background: #fff6e6;
            border-left-color: #E8A33D;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #fff;
            flex-shrink: 0;
        }
        .stat-icon.blue { background: #2C7BE5; }
        .stat-icon.amber { background: var(--amber); }
        .stat-icon.teal { background: var(--teal); }
        .stat-icon.purple { background: #8B5CF6; }

        .stat-content .number {
            font-size: 24px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.2;
        }
        .stat-content .label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        /* ---------- Cards ---------- */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 24px 28px;
            box-shadow: var(--shadow);
            margin-bottom: 24px;
        }
        .card h2 {
            font-family: 'Archivo', sans-serif;
            font-weight: 700;
            font-size: 22px;
            margin-bottom: 8px;
        }
        .card h3 {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 12px;
            color: var(--navy);
        }
        .card p {
            color: var(--muted);
            line-height: 1.6;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 8px;
        }
        .quick-action {
            background: var(--paper);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            text-decoration: none;
            color: var(--ink);
            transition: background 0.2s, transform 0.15s;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .quick-action i {
            font-size: 24px;
            color: var(--navy);
        }
        .quick-action:hover {
            background: var(--amber);
            color: var(--navy);
            transform: translateY(-2px);
        }
        .quick-action:hover i {
            color: var(--navy);
        }

        .route-search-wrap {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            align-items: center;
            flex-wrap: wrap;
        }
        .route-search {
            flex: 1;
            min-width: 220px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 10px;
            font-size: 14px;
            color: var(--ink);
            background: #fff;
        }
        .route-search:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(19, 36, 65, 0.08);
        }
        .route-list {
            display: grid;
            gap: 12px;
        }

        /* ----- Colorful Route Rows ----- */
        .route-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-radius: var(--radius);
            padding: 14px 16px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }

        .route-row:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow);
        }

        .route-color-sky   { background: #e0f2fe; border-left-color: #7dd3fc; }
        .route-color-blue  { background: #dbeafe; border-left-color: #93c5fd; }
        .route-color-orange{ background: #fef3c7; border-left-color: #fcd34d; }
        .route-color-green { background: #dcfce7; border-left-color: #86efac; }
        .route-color-purple{ background: #ede9fe; border-left-color: #c4b5fd; }
        .route-color-pink  { background: #fce7f3; border-left-color: #f9a8d4; }

        .route-row:hover.route-color-sky   { background: #c7e9fb; }
        .route-row:hover.route-color-blue  { background: #c7d9f8; }
        .route-row:hover.route-color-orange{ background: #fde68a; }
        .route-row:hover.route-color-green { background: #bbf7d0; }
        .route-row:hover.route-color-purple{ background: #ddd6fe; }
        .route-row:hover.route-color-pink  { background: #fbcfe8; }

        .route-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .route-name {
            font-weight: 700;
            color: var(--navy);
        }
        .route-path {
            color: var(--muted);
            font-size: 13px;
        }
        .route-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .route-edit,
        .route-delete {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .route-edit {
            background: var(--navy);
            color: #fff;
        }
        .route-edit:hover {
            opacity: 0.9;
        }
        .route-delete {
            background: var(--danger);
            color: #fff;
        }
        .route-delete:hover {
            opacity: 0.9;
        }
        .route-empty {
            padding: 18px;
            border: 1px dashed var(--line);
            border-radius: 12px;
            color: var(--muted);
            background: #fff;
            text-align: center;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 260px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            .top-bar {
                display: flex;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            .welcome-banner h1 {
                font-size: 1.5rem;
            }
            .route-row {
                flex-wrap: wrap;
                justify-content: center;
            }
            .route-actions {
                width: 100%;
                justify-content: center;
                margin-top: 6px;
            }
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
            .welcome-banner {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- ====== SIDEBAR ====== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="fas fa-bus"></i> LocalFare</div>
            <div class="sub">Admin Panel</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="<?= APP_URL ?>/admin/dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>
            <a href="<?= APP_URL ?>/admin/routes/index.php"><i class="fas fa-road"></i> Add Routes</a>
            
            <div class="nav-label" style="margin-top:16px;">System</div>
            <a href="<?= APP_URL ?>/admin/profile/index.php" class="nav-link"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="<?= APP_URL ?>/admin/reports/index.php"><i class="fas fa-file-alt"></i> Reports</a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
                <div class="admin-details">
                    <div class="name"><?= htmlspecialchars($adminName) ?></div>
                    <div class="role">Administrator</div>
                </div>
            </div>
            <form method="post" action="<?= APP_URL ?>/auth/logout.php" style="margin:0;">
                <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </form>
        </div>
    </aside>

    <!-- ====== MAIN CONTENT ====== -->
    <div class="main-content">

        <!-- Top bar (mobile) -->
        <div class="top-bar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title">Dashboard</span>
            <span></span>
        </div>

        <!-- ===== WELCOME BANNER ===== -->
        <div class="welcome-banner">
            <div class="eyebrow">Admin Control Panel</div>
            <h1><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($adminName) ?></h1>
            <p>Manage routes, fares, and transport services, and keep an eye on platform activity — all from one place.</p>
            <div class="date-chip"><i class="fas fa-calendar-day"></i> <?= date('l, F j, Y') ?></div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-route"></i></div>
                <div class="stat-content">
                    <div class="number"><?= $totalRoutes ?></div>
                    <div class="label">Total Routes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-content">
                    <div class="number"><?= $totalUsers ?></div>
                    <div class="label">Active Users</div>
                </div>
            </div>
            <?php /* Additional stat cards if needed */ ?>
        </div>

        <!-- Route Directory -->
        <div class="card">
            <h3>Route Directory</h3>
            <div class="route-search-wrap">
                <input type="search" id="routeSearch" class="route-search" placeholder="Search routes by name or location..." aria-label="Search routes">
            </div>
            <div class="route-list" id="routeList">
                <?php if (empty($routeRows)): ?>
                    <div class="route-empty">No routes have been created yet.</div>
                <?php else: ?>
                    <?php 
                    $colorKeys = array_keys($routeColors);
                    $colorCount = count($colorKeys);
                    $index = 0;
                    foreach ($routeRows as $route): 
                        $colorKey = $colorKeys[$index % $colorCount];
                        $index++;
                    ?>
                        <div class="route-row route-color-<?= $colorKey ?>" data-search="<?= strtolower(htmlspecialchars($route['name'] . ' ' . $route['start_location'] . ' ' . $route['end_location'])) ?>">
                            <div class="route-meta">
                                <div class="route-name"><?= htmlspecialchars($route['name'] ?: ($route['start_location'] . ' to ' . $route['end_location'])) ?></div>
                                <div class="route-path"><?= htmlspecialchars($route['start_location']) ?> → <?= htmlspecialchars($route['end_location']) ?></div>
                            </div>
                            <div class="route-actions">
                                <a class="route-edit" href="<?= APP_URL ?>/admin/routes/index.php?edit=<?= (int) $route['id'] ?>">Edit</a>
                                <form method="post" action="<?= APP_URL ?>/admin/dashboard.php" onsubmit="return confirm('Delete this route?');" style="margin:0;">
                                    <input type="hidden" name="delete_route_id" value="<?= (int) $route['id'] ?>">
                                    <button type="submit" class="route-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        
    <!-- ====== JavaScript ====== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            if (toggle && sidebar) {
                toggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                            sidebar.classList.remove('open');
                        }
                    }
                });
            }

            const searchInput = document.getElementById('routeSearch');
            const rows = Array.from(document.querySelectorAll('.route-row'));

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.trim().toLowerCase();
                    rows.forEach(function(row) {
                        const haystack = (row.dataset.search || '').toLowerCase();
                        const matches = !term || haystack.includes(term);
                        row.style.display = matches ? '' : 'none';
                    });
                });
            }
        });
    </script>

</body>
</html>