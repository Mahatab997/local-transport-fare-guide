<?php
$pageTitle = 'Manage Routes';
require_once __DIR__ . '/../../includes/header.php';
require_login();
if (!is_admin()) {
    redirect('index.php');
}

$pdo = db_connect();
$errors = [];
$successMessage = '';
$editingRouteId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editingRoute = null;
$existingFareMap = [];
$vehicleTypes = ['Auto', 'Bus', 'CNG', 'Rikshaw', 'Town Services', 'Microbus'];
$vehicleIcons = [
    'Auto' => '🛺',
    'Bus' => '🚌',
    'CNG' => '🚕',
    'Rikshaw' => '🚲',
    'Town Services' => '🚐',
    'Microbus' => '🚙',
];

function ensureLocationRecord($pdo, $name) {
    $name = trim((string) $name);
    if ($name === '') {
        return null;
    }

    $columns = $pdo->query('SHOW COLUMNS FROM locations')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('name', $columns)) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM locations WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => $name]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        return (int) $existingId;
    }

    $regionColumnExists = in_array('region', $columns);
    if ($regionColumnExists) {
        $stmt = $pdo->prepare('INSERT INTO locations (name, region, created_at) VALUES (:name, NULL, NOW())');
        $stmt->execute(['name' => $name]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO locations (name, created_at) VALUES (:name, NOW())');
        $stmt->execute(['name' => $name]);
    }

    return (int) $pdo->lastInsertId();
}

if ($editingRouteId > 0) {
    $editingRouteStmt = $pdo->prepare('SELECT id, name, start_location, end_location, description FROM routes WHERE id = :id LIMIT 1');
    $editingRouteStmt->execute(['id' => $editingRouteId]);
    $editingRoute = $editingRouteStmt->fetch(PDO::FETCH_ASSOC);

    if ($editingRoute) {
        $fareMapStmt = $pdo->prepare('SELECT vehicle_type, fare, min_time FROM route_fares WHERE route_id = :route_id');
        $fareMapStmt->execute(['route_id' => $editingRoute['id']]);
        foreach ($fareMapStmt->fetchAll(PDO::FETCH_ASSOC) as $fareRow) {
            $existingFareMap[$fareRow['vehicle_type']] = $fareRow;
        }
    }
}

// ----- Auto‑setup: ensure routes table has start_location / end_location (text) -----
try {
    // Check if columns exist; if not, add them
    $cols = $pdo->query("SHOW COLUMNS FROM routes")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('start_location', $cols)) {
        $pdo->exec("ALTER TABLE routes ADD start_location VARCHAR(100) NOT NULL DEFAULT ''");
    }
    if (!in_array('end_location', $cols)) {
        $pdo->exec("ALTER TABLE routes ADD end_location VARCHAR(100) NOT NULL DEFAULT ''");
    }
    // Remove foreign keys if they exist (we won't use them)
    // Also ensure we have name, description, etc.
    if (!in_array('name', $cols)) {
        $pdo->exec("ALTER TABLE routes ADD name VARCHAR(100) NOT NULL DEFAULT ''");
    }
    if (!in_array('description', $cols)) {
        $pdo->exec("ALTER TABLE routes ADD description TEXT");
    }
    // If start_location_id or end_location_id exist, we could drop them, but we'll keep them for backward compatibility
    // We'll simply ignore them in our queries.

    // Ensure route_fares table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS route_fares (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        route_id INT UNSIGNED NOT NULL,
        vehicle_type VARCHAR(80) NOT NULL,
        fare DECIMAL(10,2) NOT NULL,
        min_time INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    $errors[] = 'Database setup error: ' . $e->getMessage();
}

// ----- Handle form submission -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $routeId = (int) ($_POST['route_id'] ?? 0);
    $startLocation = trim($_POST['start_location'] ?? '');
    $endLocation = trim($_POST['end_location'] ?? '');
    $routeName = trim($_POST['route_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($startLocation === '' || $endLocation === '') {
        $errors[] = 'Please enter both start and end locations.';
    }

    if ($startLocation !== '' && $endLocation !== '' && strcasecmp($startLocation, $endLocation) === 0) {
        $errors[] = 'Start and end locations cannot be the same.';
    }

    $fareEntries = [];
    foreach ($vehicleTypes as $vehicleType) {
        $fareValue = trim($_POST['vehicle_fare'][$vehicleType] ?? '');
        $timeValue = trim($_POST['vehicle_time'][$vehicleType] ?? '');

        if ($fareValue === '' || $timeValue === '') {
            $errors[] = 'Please provide both fare and minimum time for ' . $vehicleType . '.';
            continue;
        }

        if (!is_numeric($fareValue) || (float) $fareValue < 0) {
            $errors[] = 'Fare for ' . $vehicleType . ' must be a valid number.';
            continue;
        }

        if (!ctype_digit($timeValue) || (int) $timeValue <= 0) {
            $errors[] = 'Minimum travel time for ' . $vehicleType . ' must be a positive number.';
            continue;
        }

        $fareEntries[] = [
            'vehicle_type' => $vehicleType,
            'fare' => (float) $fareValue,
            'min_time' => (int) $timeValue,
        ];
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            if ($routeName === '') {
                $routeName = 'Route from ' . $startLocation . ' to ' . $endLocation;
            }

            $routeColumns = $pdo->query('SHOW COLUMNS FROM routes')->fetchAll(PDO::FETCH_COLUMN);
            $legacyLocationIds = in_array('start_location_id', $routeColumns) && in_array('end_location_id', $routeColumns);
            $startLocationId = null;
            $endLocationId = null;

            if ($legacyLocationIds) {
                $startLocationId = ensureLocationRecord($pdo, $startLocation);
                $endLocationId = ensureLocationRecord($pdo, $endLocation);
                if ($startLocationId === null || $endLocationId === null) {
                    throw new Exception('Unable to create valid location records for the route.');
                }
            }

            if ($routeId > 0) {
                $params = [
                    'id' => $routeId,
                    'name' => $routeName,
                    'description' => $description,
                ];
                $sql = 'UPDATE routes SET name = :name, description = :description';

                if (in_array('start_location', $routeColumns)) {
                    $sql .= ', start_location = :start';
                    $params['start'] = $startLocation;
                }
                if (in_array('end_location', $routeColumns)) {
                    $sql .= ', end_location = :end';
                    $params['end'] = $endLocation;
                }
                if ($legacyLocationIds) {
                    $sql .= ', start_location_id = :start_location_id, end_location_id = :end_location_id';
                    $params['start_location_id'] = $startLocationId;
                    $params['end_location_id'] = $endLocationId;
                }
                $sql .= ', updated_at = NOW() WHERE id = :id';

                $routeStmt = $pdo->prepare($sql);
                $routeStmt->execute($params);
                $pdo->prepare('DELETE FROM route_fares WHERE route_id = :route_id')->execute(['route_id' => $routeId]);
                $editingRouteId = $routeId;
                $successMessage = 'Route updated successfully.';
            } else {
                $columns = ['name', 'description', 'created_at'];
                $placeholders = [':name', ':description', 'NOW()'];
                $params = [
                    'name' => $routeName,
                    'description' => $description,
                ];

                if (in_array('start_location', $routeColumns)) {
                    $columns[] = 'start_location';
                    $placeholders[] = ':start';
                    $params['start'] = $startLocation;
                }
                if (in_array('end_location', $routeColumns)) {
                    $columns[] = 'end_location';
                    $placeholders[] = ':end';
                    $params['end'] = $endLocation;
                }
                if ($legacyLocationIds) {
                    $columns[] = 'start_location_id';
                    $placeholders[] = ':start_location_id';
                    $params['start_location_id'] = $startLocationId;

                    $columns[] = 'end_location_id';
                    $placeholders[] = ':end_location_id';
                    $params['end_location_id'] = $endLocationId;
                }

                $routeStmt = $pdo->prepare('INSERT INTO routes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
                $routeStmt->execute($params);
                $routeId = (int) $pdo->lastInsertId();
                $editingRouteId = $routeId;
                $successMessage = 'Route created successfully.';
            }

            $fareStmt = $pdo->prepare('INSERT INTO route_fares (route_id, vehicle_type, fare, min_time, created_at) VALUES (:route_id, :vehicle_type, :fare, :min_time, NOW())');
            foreach ($fareEntries as $entry) {
                $fareStmt->execute([
                    'route_id' => $routeId,
                    'vehicle_type' => $entry['vehicle_type'],
                    'fare' => $entry['fare'],
                    'min_time' => $entry['min_time'],
                ]);
            }

            $pdo->commit();
            $editingRouteStmt = $pdo->prepare('SELECT id, name, start_location, end_location, description FROM routes WHERE id = :id LIMIT 1');
            $editingRouteStmt->execute(['id' => $editingRouteId]);
            $editingRoute = $editingRouteStmt->fetch(PDO::FETCH_ASSOC);
            if ($editingRoute) {
                $fareMapStmt = $pdo->prepare('SELECT vehicle_type, fare, min_time FROM route_fares WHERE route_id = :route_id');
                $fareMapStmt->execute(['route_id' => $editingRoute['id']]);
                foreach ($fareMapStmt->fetchAll(PDO::FETCH_ASSOC) as $fareRow) {
                    $existingFareMap[$fareRow['vehicle_type']] = $fareRow;
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Unable to save the route: ' . $e->getMessage();
        }
    }
}

// ----- Fetch routes (no join with locations, use start_location / end_location) -----
$routes = $pdo->query('SELECT r.id, r.name, r.start_location, r.end_location, r.description, r.created_at FROM routes r ORDER BY r.created_at DESC')->fetchAll();
$fareStmt = $pdo->prepare('SELECT vehicle_type, fare, min_time FROM route_fares WHERE route_id = :route_id ORDER BY id');
foreach ($routes as &$route) {
    $fareStmt->execute(['route_id' => $route['id']]);
    $route['fares'] = $fareStmt->fetchAll();
}
unset($route);

if ($editingRouteId > 0 && $editingRoute === null) {
    $editingRouteStmt = $pdo->prepare('SELECT id, name, start_location, end_location, description FROM routes WHERE id = :id LIMIT 1');
    $editingRouteStmt->execute(['id' => $editingRouteId]);
    $editingRoute = $editingRouteStmt->fetch(PDO::FETCH_ASSOC);
}

if ($editingRoute && empty($existingFareMap)) {
    $fareMapStmt = $pdo->prepare('SELECT vehicle_type, fare, min_time FROM route_fares WHERE route_id = :route_id');
    $fareMapStmt->execute(['route_id' => $editingRoute['id']]);
    foreach ($fareMapStmt->fetchAll(PDO::FETCH_ASSOC) as $fareRow) {
        $existingFareMap[$fareRow['vehicle_type']] = $fareRow;
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

        /* ---------- Top Bar (mobile) ---------- */
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

        /* ---------- Page Header ---------- */
        .page-header {
            background: #1a4b7c; /* dark sky blue */
            border-radius: var(--radius);
            padding: 28px 32px;
            margin-bottom: 28px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 8px 30px rgba(26, 75, 124, 0.25);
            position: relative;
        }

        .page-header .title-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .page-header .eyebrow {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
        }

        .page-header p {
            margin: 4px 0 0;
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* Back button - left side, light & dark contrast */
        .back-btn-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            color: #1a4b7c;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: 700;
            transition: background 0.15s, transform 0.1s;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .back-btn:hover {
            background: #e6f0ff;
            transform: translateY(-2px);
        }

        .back-btn i {
            font-size: 1rem;
        }

        .header-stats {
            display: flex;
            gap: 18px;
        }

        .header-stats > div {
            background: rgba(255,255,255,0.12);
            border-radius: 10px;
            padding: 10px 18px;
            text-align: center;
            min-width: 80px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .header-stats strong {
            display: block;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .header-stats span {
            font-size: 0.7rem;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ---------- Form Layout ---------- */
        .route-form-grid {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(340px, 1.4fr);
            gap: 22px;
            align-items: start;
        }

        .step-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 24px 28px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .step-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a4b7c, #4a8db7);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .step-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #e6f0ff;
            color: #1a4b7c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .step-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--ink);
        }

        .step-header p {
            margin: 2px 0 0;
            font-size: 0.82rem;
            color: var(--muted);
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 18px;
        }

        .field:last-child { margin-bottom: 0; }

        .field label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--ink);
        }

        .field .optional {
            font-weight: 400;
            color: var(--muted);
        }

        .field input[type="text"],
        .field textarea {
            padding: 10px 14px;
            border: 1px solid var(--line);
            border-radius: 9px;
            font-size: 0.94rem;
            font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #fff;
        }

        .field input:focus,
        .field textarea:focus {
            outline: none;
            border-color: #1a4b7c;
            box-shadow: 0 0 0 3px rgba(26, 75, 124, 0.12);
        }

        .route-preview {
            background: #e6f0ff;
            border: 1px dashed #8ab4d6;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.85rem;
            color: #1a4b7c;
            margin-top: 4px;
        }

        .route-preview strong { color: var(--ink); }

        /* Fare grid – exactly 2 columns */
        .fare-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .fare-item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 14px 16px;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #fafcff;
        }

        .fare-item:hover {
            border-color: #8ab4d6;
            box-shadow: 0 4px 14px rgba(26, 75, 124, 0.08);
        }

        .fare-item__title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.92rem;
            margin-bottom: 10px;
            color: var(--ink);
        }

        .fare-item__title .icon {
            font-size: 1.2rem;
        }

        .fare-item__inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .fare-item__inputs label {
            display: grid;
            gap: 4px;
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 600;
        }

        .fare-item__inputs input {
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 7px;
            font-size: 0.88rem;
            width: 100%;
            box-sizing: border-box;
            background: #fff;
        }

        .fare-item__inputs input:focus {
            outline: none;
            border-color: #1a4b7c;
            box-shadow: 0 0 0 3px rgba(26, 75, 124, 0.10);
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
        }

        .save-btn {
            background: linear-gradient(135deg, #1a4b7c, #0e3559);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 32px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 8px 22px rgba(26, 75, 124, 0.35);
            transition: transform 0.1s, box-shadow 0.2s;
        }

        .save-btn:hover {
            box-shadow: 0 10px 28px rgba(26, 75, 124, 0.45);
            transform: translateY(-1px);
        }

        .save-btn:active { transform: scale(0.97); }

        .alert {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.9rem;
            grid-column: 1 / -1;
        }

        .alert.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert.error ul {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .alert.success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
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
            .route-form-grid {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px 20px;
            }
            .back-btn-wrap {
                width: 100%;
                justify-content: space-between;
            }
            .header-stats {
                flex-wrap: wrap;
            }
            .fare-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .fare-grid {
                grid-template-columns: 1fr;
            }
            .header-stats > div {
                min-width: 60px;
                padding: 8px 12px;
            }
            .back-btn-wrap {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar is now included globally from includes/admin_sidebar.php -->

    <!-- ====== MAIN CONTENT ====== -->
    <div class="main-content">

        <!-- Top bar (mobile) -->
        <div class="top-bar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <span class="page-title"><?= $pageTitle ?></span>
            <span></span>
        </div>

        <!-- ===== PAGE HEADER ===== -->
        <div class="page-header">
            <div class="title-group">
                <div><?= $editingRoute ? 'Edit Route' : 'Add New Route' ?></div>
                <h1><?= $editingRoute ? 'Update route details' : 'Create a new transport route' ?></h1>
                <p><?= $editingRoute ? 'Modify the route information and vehicle fares below.' : 'Fill in the route details and set fares for each vehicle type.' ?></p>
            </div>
            <div class="back-btn-wrap">
                
                <a href="<?= APP_URL ?>/admin/dashboard.php" class="back-btn"><i class="fas fa-arrow-left-top"></i> Back</a>
            </div>
        </div>

        <!-- ===== FORM ===== -->
        <form method="post" class="route-form-grid">

            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <strong>Please fix the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= sanitize($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($successMessage !== ''): ?>
                <div class="alert success"><?= sanitize($successMessage) ?></div>
            <?php endif; ?>

            <input type="hidden" name="route_id" value="<?= (int) ($editingRouteId ?: ($_POST['route_id'] ?? 0)) ?>">

            <!-- Card 1: Route details -->
            <div class="step-card">
                <div class="step-header">
                    <div class="step-badge">1</div>
                    <div>
                        <h3><?= $editingRoute ? 'Edit Route' : 'Route Details' ?></h3>
                        <p>Where does this route start and end?</p>
                    </div>
                </div>

                <div class="field">
                    <label>Route name <span class="optional">(optional)</span></label>
                    <input type="text" id="route_name" name="route_name" value="<?= sanitize($_POST['route_name'] ?? ($editingRoute['name'] ?? '')) ?>" placeholder="Auto-generated if left blank">
                </div>
                <div class="field">
                    <label>Start point</label>
                    <input type="text" id="start_location" name="start_location" value="<?= sanitize($_POST['start_location'] ?? ($editingRoute['start_location'] ?? '')) ?>" placeholder="e.g. Dhaka" required>
                </div>
                <div class="field">
                    <label>End point</label>
                    <input type="text" id="end_location" name="end_location" value="<?= sanitize($_POST['end_location'] ?? ($editingRoute['end_location'] ?? '')) ?>" placeholder="e.g. Chittagong" required>
                </div>
                <div class="field">
                    <label>Description <span class="optional">(optional)</span></label>
                    <textarea name="description" rows="3" placeholder="Add a short note about the route"><?= sanitize($_POST['description'] ?? ($editingRoute['description'] ?? '')) ?></textarea>
                </div>

                
            </div>

            <!-- Card 2: Vehicle fares -->
            <div class="step-card">
                <div class="step-header">
                    <div class="step-badge">2</div>
                    <div>
                        <h3>Vehicle Fares &amp; Travel Time</h3>
                        <p>Set the fare and minimum travel time for every vehicle.</p>
                    </div>
                </div>

                <div class="fare-grid">
                    <?php foreach ($vehicleTypes as $vehicleType): ?>
                        <div class="fare-item">
                            <div class="fare-item__title">
                                <span class="icon"><?= $vehicleIcons[$vehicleType] ?? '🚘' ?></span>
                                <?= sanitize($vehicleType) ?>
                            </div>
                            <div class="fare-item__inputs">
                                <label>
                                    Fare (৳)
                                    <input type="number" step="0.01" min="0" name="vehicle_fare[<?= sanitize($vehicleType) ?>]" value="<?= sanitize($_POST['vehicle_fare'][$vehicleType] ?? ($existingFareMap[$vehicleType]['fare'] ?? '')) ?>" placeholder="0.00" required>
                                </label>
                                <label>
                                    Min. time (min)
                                    <input type="number" min="1" name="vehicle_time[<?= sanitize($vehicleType) ?>]" value="<?= sanitize($_POST['vehicle_time'][$vehicleType] ?? ($existingFareMap[$vehicleType]['min_time'] ?? '')) ?>" placeholder="15" required>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="save-btn"><?= $editingRoute ? '💾 Update Route' : '💾 Save Route' ?></button>
            </div>
        </form>

    </div>

    <!-- ====== JavaScript ====== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
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

            // Route preview
            const startInput = document.getElementById('start_location');
            const endInput = document.getElementById('end_location');
            const nameInput = document.getElementById('route_name');
            const preview = document.getElementById('route-preview-text');

            function updatePreview() {
                var name = nameInput.value.trim();
                var start = startInput.value.trim();
                var end = endInput.value.trim();
                if (name) {
                    preview.textContent = name;
                } else if (start && end) {
                    preview.textContent = 'Route from ' + start + ' to ' + end;
                } else {
                    preview.textContent = '—';
                }
            }

            if (startInput && endInput && nameInput && preview) {
                [startInput, endInput, nameInput].forEach(function(el) {
                    el.addEventListener('input', updatePreview);
                });
                updatePreview();
            }
        });
    </script>

</body>
</html>