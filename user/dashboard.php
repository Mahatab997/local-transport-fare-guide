<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

// Users don't need admin rights — but if an admin lands here, send them to their own dashboard
if (is_admin()) {
    redirect('admin/dashboard.php');
}

$pageTitle = 'My Dashboard';
$pdo = db_connect();

// ----- Fetch current user's name from database (ensures it's always fresh) -----
$userStmt = $pdo->prepare('SELECT name FROM users WHERE id = :id');
$userStmt->execute(['id' => $_SESSION['user_id']]);
$userRecord = $userStmt->fetch(PDO::FETCH_ASSOC);
$userName = $userRecord ? $userRecord['name'] : ($_SESSION['user_name'] ?? 'User');
$_SESSION['user_name'] = $userName;

function safeCount($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$totalRoutes    = safeCount($pdo, 'routes');
$totalLocations = safeCount($pdo, 'locations');

$vehicleIcons = [
    'Auto' => '🛺',
    'Bus' => '🚌',
    'CNG' => '🚕',
    'Rikshaw' => '🚲',
    'Town Services' => '🚐',
    'Microbus' => '🚙',
];

// ----- Fetch all routes with their fares -----
$routeRows = $pdo->query('SELECT r.id, r.name, r.start_location, r.end_location, r.description FROM routes r ORDER BY r.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

$fareStmt = $pdo->prepare('SELECT vehicle_type, fare, min_time FROM route_fares WHERE route_id = :route_id ORDER BY id');
foreach ($routeRows as &$route) {
    $fareStmt->execute(['route_id' => $route['id']]);
    $route['fares'] = $fareStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($route);

// ----- Define a palette of light colors for route cards -----
$cardColors = [
    ['bg' => '#f0f4f8', 'border' => '#b0c4d8'], // gray
    ['bg' => '#e3f0ff', 'border' => '#91b8e0'], // sky blue
    ['bg' => '#fef7e0', 'border' => '#f5d68a'], // orange
    ['bg' => '#f0f8f0', 'border' => '#94c9a0'], // green
    ['bg' => '#f0edf8', 'border' => '#b8a9d8'], // purple
    ['bg' => '#fce4ec', 'border' => '#e8a0b0'], // pink
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

        /* ---------- Welcome Banner ---------- */
        .welcome-banner {
            background: #1a4b7c;
            border-radius: var(--radius);
            padding: 28px 32px;
            color: #fff;
            margin-bottom: 28px;
            box-shadow: 0 8px 30px rgba(26, 75, 124, 0.25);
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

        .welcome-banner h2 {
            margin: 0 0 6px 0;
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
        }

        .welcome-banner p {
            margin: 0;
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* ---------- Stats Grid ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(19, 36, 65, 0.12);
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
            gap: 14px;
        }
        .route-card {
            border-radius: var(--radius);
            padding: 18px 20px;
            border-left: 4px solid transparent;
            transition: box-shadow 0.2s ease, transform 0.15s ease, border-left-color 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .route-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
        .route-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .route-name {
            font-weight: 700;
            color: var(--navy);
            font-size: 15px;
        }
        .route-path {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--teal);
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
        }
        .route-desc {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .fare-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .fare-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.7);
            border: 1px solid rgba(0,0,0,0.04);
            color: var(--navy);
            font-size: 12.5px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 999px;
            backdrop-filter: blur(2px);
        }
        .fare-badge .fare-amount {
            color: var(--teal);
            font-weight: 700;
        }
        .fare-badge .fare-time {
            color: var(--muted);
            font-weight: 500;
        }
        .no-fares {
            font-size: 12.5px;
            color: var(--muted);
            font-style: italic;
        }
        .route-empty {
            padding: 30px 18px;
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
        }
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- ====== SIDEBAR ====== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo"><i class="fas fa-bus"></i> LocalFare</div>
            <div class="sub">User Panel</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="<?= APP_URL ?>/user/dashboard.php" class="active"><i class="fas fa-chart-pie"></i> Dashboard</a>

            <div class="nav-label" style="margin-top:16px;">System</div>
            <a href="<?= APP_URL ?>/user/profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="<?= APP_URL ?>/user/reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <div class="admin-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                <div class="admin-details">
                    <div class="name"><?= htmlspecialchars($userName) ?></div>
                    <div class="role">User</div>
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
            <h1>Welcome back, <?= htmlspecialchars($userName) ?></h1>
            <p>Browse available transport routes and compare fares across vehicle types before you travel.</p>
            <br>
            <div class="date-chip"><i class="fas fa-calendar-day"></i> <?= date('l, F j, Y') ?></div>
        </div>

        <!-- Route Directory -->
        <div class="card">
            <h2>Route Directory</h2>
            <div class="route-search-wrap">
                <input type="search" id="routeSearch" class="route-search" placeholder="Search routes by name or location..." aria-label="Search routes">
            </div>

            <div class="route-list" id="routeList">
                <?php if (empty($routeRows)): ?>
                    <div class="route-empty">No routes have been added yet. Please check back later.</div>
                <?php else: ?>
                    <?php 
                    $colorCount = count($cardColors);
                    $index = 0;
                    foreach ($routeRows as $route): 
                        $color = $cardColors[$index % $colorCount];
                        $index++;
                    ?>
                        <div class="route-card" 
                             style="background-color: <?= $color['bg'] ?>; border-left-color: <?= $color['border'] ?>;"
                             data-search="<?= strtolower(htmlspecialchars($route['name'] . ' ' . $route['start_location'] . ' ' . $route['end_location'])) ?>">
                            <div class="route-card-top">
                                <div>
                                    <div class="route-name"><?= htmlspecialchars($route['name'] ?: ($route['start_location'] . ' to ' . $route['end_location'])) ?></div>
                                    <div class="route-path"><i class="fas fa-location-arrow"></i> <?= htmlspecialchars($route['start_location']) ?> &rarr; <?= htmlspecialchars($route['end_location']) ?></div>
                                </div>
                            </div>

                            <?php if (!empty($route['description'])): ?>
                                <div class="route-desc"><?= htmlspecialchars($route['description']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($route['fares'])): ?>
                                <div class="fare-badges">
                                    <?php foreach ($route['fares'] as $fare): ?>
                                        <span class="fare-badge">
                                            <?= $vehicleIcons[$fare['vehicle_type']] ?? '🚘' ?>
                                            <?= htmlspecialchars($fare['vehicle_type']) ?>
                                            <span class="fare-amount">৳<?= number_format((float) $fare['fare'], 2) ?></span>
                                            <span class="fare-time">· <?= (int) $fare['min_time'] ?>m</span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-fares">No fare details added for this route yet.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
            const cards = Array.from(document.querySelectorAll('.route-card'));

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.trim().toLowerCase();
                    cards.forEach(function(card) {
                        const haystack = (card.dataset.search || '').toLowerCase();
                        const matches = !term || haystack.includes(term);
                        card.style.display = matches ? '' : 'none';
                    });
                });
            }
        });
    </script>

</body>
</html>