<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/init.php';

function countTableRows($pdo, $table) {
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

$stats = [
    'routes' => 0,
    'locations' => 0,
    'transport_types' => 0,
    'users' => 0,
];

try {
    $pdo = db_connect();
    $stats['routes'] = countTableRows($pdo, 'routes');
    $stats['locations'] = countTableRows($pdo, 'locations');
    $stats['users'] = countTableRows($pdo, 'users');

    try {
        $stats['transport_types'] = (int) $pdo->query('SELECT COUNT(DISTINCT vehicle_type) FROM route_fares')->fetchColumn();
    } catch (Throwable $e) {
        $stats['transport_types'] = 0;
    }
} catch (Throwable $e) {
    error_log('Homepage stats load failed: ' . $e->getMessage());
}
//require_once __DIR__ . '/includes/header.php';
?>

<!-- These two links normally belong in includes/header.php — added here only so the page
     renders correctly on its own while header.php is commented out above. -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;800&family=IBM+Plex+Mono:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
:root{
    --navy:#132441;
    --navy-2:#1E355C;
    --amber:#E8A33D;
    --amber-dark:#C07C22;
    --teal:#2F8F6E;
    --paper:#EFF2F6;
    --card:#FFFFFF;
    --ink:#182236;
    --muted:#5C6779;
    --line:#DCE1E9;
    --radius:14px;
}

*{box-sizing:border-box;}

body{
    background:var(--paper);
    color:var(--ink);
    font-family:'Inter',system-ui,sans-serif;
}

.eyebrow{
    font-family:'IBM Plex Mono',monospace;
    font-size:12px;
    letter-spacing:.12em;
    text-transform:uppercase;
    font-weight:600;
}

h1,h2,h3{
    font-family:'Archivo',sans-serif;
    font-weight:800;
    letter-spacing:-.01em;
}

a:focus-visible,
button:focus-visible{
    outline:3px solid var(--amber);
    outline-offset:2px;
}

/* ---------- Hero ---------- */

.hero{
    position:relative;
    background:
        radial-gradient(circle at 85% -10%, rgba(232,163,61,.18), transparent 45%),
        linear-gradient(135deg,var(--navy),var(--navy-2));
    color:#fff;
    border-radius:20px;
    padding:56px 48px;
    margin-bottom:34px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:40px;
    flex-wrap:wrap;
    overflow:hidden;
    box-shadow:0 18px 40px rgba(19,36,65,.25);
}

.hero-content{
    max-width:560px;
    position:relative;
    z-index:1;
}

.hero .eyebrow{
    color:var(--amber);
}

.hero h1{
    font-size:38px;
    line-height:1.15;
    margin:14px 0 16px;
}

.hero p{
    font-size:16px;
    line-height:1.75;
    color:rgba(255,255,255,.82);
    max-width:480px;
}

/* origin -> destination route line, grounded in the subject rather than decorative */
.route-line{
    display:flex;
    align-items:center;
    gap:10px;
    margin:24px 0 4px;
    font-family:'IBM Plex Mono',monospace;
    font-size:12px;
    color:rgba(255,255,255,.75);
}

.route-line .dot{
    width:8px;height:8px;border-radius:50%;background:var(--amber);flex-shrink:0;
}

.route-line .dot.end{
    background:var(--teal);
}

.route-line .track{
    flex:1;
    height:1px;
    background:repeating-linear-gradient(90deg,rgba(255,255,255,.5) 0 6px,transparent 6px 12px);
    position:relative;
}

.route-line i{
    position:absolute;
    top:50%;
    left:38%;
    transform:translate(-50%,-50%);
    font-size:12px;
    color:var(--amber);
    background:var(--navy-2);
    padding:0 4px;
}

.hero-buttons{
    margin-top:26px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.hero-buttons a{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:13px 26px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    font-size:14.5px;
    transition:transform .15s ease, background .15s ease;
}

.btn-primary{
    background:var(--amber);
    color:var(--navy);
}

.btn-primary:hover{
    background:#f0b158;
    transform:translateY(-2px);
}

.btn-outline{
    border:1.5px solid rgba(255,255,255,.55);
    color:#fff;
}

.btn-outline:hover{
    background:rgba(255,255,255,.12);
    transform:translateY(-2px);
}

/* ---------- Ticket mockup (signature element) ---------- */

.ticket{
    position:relative;
    z-index:1;
    background:#fff;
    color:var(--ink);
    width:280px;
    border-radius:12px;
    padding:22px 22px 18px;
    transform:rotate(3deg);
    box-shadow:0 20px 45px rgba(0,0,0,.3);
    flex-shrink:0;
}

.ticket-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
}

.ticket-label{
    font-family:'IBM Plex Mono',monospace;
    font-size:10px;
    letter-spacing:.1em;
    color:var(--muted);
    text-transform:uppercase;
}

.ticket-route{
    font-family:'Archivo',sans-serif;
    font-weight:800;
    font-size:20px;
    margin:6px 0 2px;
}

.ticket-sub{
    font-size:12px;
    color:var(--muted);
}

.ticket-divider{
    position:relative;
    margin:16px -22px;
    border-top:2px dashed var(--line);
}

.ticket-divider::before,
.ticket-divider::after{
    content:"";
    position:absolute;
    top:-9px;
    width:18px;
    height:18px;
    border-radius:50%;
    background:var(--paper);
}

.ticket-divider::before{left:-9px;}
.ticket-divider::after{right:-9px;}

.ticket-bottom{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
}

.ticket-fare{
    font-family:'IBM Plex Mono',monospace;
    font-weight:600;
    font-size:26px;
    color:var(--teal);
}

.ticket-barcode{
    display:flex;
    gap:2px;
    align-items:flex-end;
    height:26px;
}

.ticket-barcode span{
    width:2px;
    background:var(--ink);
    opacity:.7;
}

/* ---------- Section title ---------- */

.section-title{
    text-align:center;
    margin:56px 0 30px;
    font-size:27px;
    color:var(--navy);
}

/* ---------- Feature cards (ticket-stub styling) ---------- */

.features{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:22px;
}

.feature-card{
    position:relative;
    background:var(--card);
    border-radius:var(--radius);
    padding:34px 26px 28px;
    text-align:center;
    box-shadow:0 6px 20px rgba(19,36,65,.07);
    border-top:2px dashed var(--line);
    transition:transform .2s ease, box-shadow .2s ease;
}

.feature-card::before,
.feature-card::after{
    content:"";
    position:absolute;
    top:-10px;
    width:20px;
    height:20px;
    border-radius:50%;
    background:var(--paper);
}

.feature-card::before{left:-10px;}
.feature-card::after{right:-10px;}

.feature-card:hover{
    transform:translateY(-6px);
    box-shadow:0 14px 30px rgba(19,36,65,.14);
}

.feature-card i{
    font-size:30px;
    color:var(--navy);
    background:rgba(232,163,61,.15);
    width:64px;
    height:64px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 18px;
}

.feature-card h3{
    font-size:17px;
    margin-bottom:10px;
    color:var(--navy);
}

.feature-card p{
    color:var(--muted);
    font-size:14px;
    line-height:1.65;
}

/* ---------- Departure-board stats ---------- */

.stats{
    margin:56px 0;
    background:var(--navy);
    border-radius:16px;
    padding:34px 20px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
    gap:10px;
}

.stat-card{
    text-align:center;
    padding:14px;
    border-right:1px dashed rgba(255,255,255,.15);
}

.stat-card:last-child{
    border-right:none;
}

.stat-card h2{
    font-family:'IBM Plex Mono',monospace;
    font-weight:600;
    font-size:32px;
    color:var(--amber);
    letter-spacing:.03em;
    margin-bottom:6px;
}

.stat-card p{
    font-family:'IBM Plex Mono',monospace;
    font-size:11px;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:rgba(255,255,255,.65);
}

/* ---------- Info section ---------- */

.info-section{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(320px,1fr));
    gap:22px;
    margin-bottom:44px;
}

.info-card{
    background:var(--card);
    border-radius:var(--radius);
    padding:32px 30px;
    box-shadow:0 6px 20px rgba(19,36,65,.07);
}

.info-card h3{
    color:var(--navy);
    font-size:17px;
    margin-bottom:18px;
    display:flex;
    align-items:center;
    gap:10px;
}

.info-card h3 i{
    color:var(--amber);
    font-size:15px;
}

.info-card ul{
    list-style:none;
    padding:0;
    margin:0;
    color:var(--ink);
}

.info-card li{
    display:flex;
    align-items:center;
    gap:12px;
    padding:10px 0;
    border-bottom:1px solid var(--paper);
    font-size:14.5px;
}

.info-card li:last-child{
    border-bottom:none;
}

.info-card li i{
    color:var(--teal);
    font-size:12px;
}

/* ---------- CTA ---------- */

.cta{
    position:relative;
    background:linear-gradient(135deg,var(--navy),var(--navy-2));
    color:#fff;
    text-align:center;
    padding:56px 30px;
    border-radius:20px;
    margin-top:44px;
    overflow:hidden;
}

.cta h2{
    margin-bottom:12px;
    font-size:26px;
}

.cta p{
    color:rgba(255,255,255,.8);
    max-width:480px;
    margin:0 auto;
    line-height:1.7;
    font-size:15px;
}

.cta a{
    margin-top:24px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--amber);
    color:var(--navy);
    text-decoration:none;
    font-weight:600;
    padding:14px 30px;
    border-radius:8px;
    transition:transform .15s ease;
}

.cta a:hover{
    transform:translateY(-2px);
    background:#f0b158;
}

@media(prefers-reduced-motion:reduce){
    .feature-card,.hero-buttons a,.cta a{transition:none;}
    .feature-card:hover,.hero-buttons a:hover,.cta a:hover{transform:none;}
}

@media(max-width:768px){

    .hero{
        text-align:center;
        padding:40px 26px;
        justify-content:center;
    }

    .hero-content{
        max-width:100%;
    }

    .route-line,.hero-buttons{
        justify-content:center;
    }

    .ticket{
        transform:rotate(0);
    }

    .hero h1{
        font-size:29px;
    }

    .stat-card{
        border-right:none;
        border-bottom:1px dashed rgba(255,255,255,.15);
    }

    .stat-card:last-child{
        border-bottom:none;
    }
}
</style>

<!-- Hero Section -->

<section class="hero">

    <div class="-content">

        <span class="eyebrow">
            <h2>Local Transport Fare Guide</h2>
        </span>

        <h1>Find the fastest route. Compare fares. Travel with confidence.</h1>

        <p>
            Search transport routes, compare fares across buses, trains,
            ferries, auto-rickshaws and ride-sharing services, and plan your
            journey with confidence — all in one place.
        </p>

        <div class="route-line">
            <span class="dot start"></span>
            <span class="track"><i class="fas fa-bus"></i></span>
            <span class="dot end"></span>
        </div>

        <div class="hero-buttons">

            <a href="auth/register.php" class="btn-primary">
                <i class="fas fa-user-plus"></i> Get Started
            </a>

            <a href="auth/login.php" class="btn-outline">
                <i class="fas fa-right-to-bracket"></i> Login
            </a>

        </div>

    </div>

    

</section>

<h2 class="section-title">Our Services</h2>

<div class="features">

    <div class="feature-card">
        <i class="fas fa-route"></i>
        <h3>Route Finder</h3>
        <p>
            Search transport routes between locations quickly and easily.
        </p>
    </div>

    <div class="feature-card">
        <i class="fas fa-money-bill-wave"></i>
        <h3>Fare Information</h3>
        <p>
            View updated transport fares for buses, trains, ferries,
            auto-rickshaws and ride sharing services.
        </p>
    </div>

    <div class="feature-card">
        <i class="fas fa-map-marker-alt"></i>
        <h3>Locations</h3>
        <p>
            Explore available stations, terminals and transport stops.
        </p>
    </div>

    <div class="feature-card">
        <i class="fas fa-heart"></i>
        <h3>Favorites</h3>
        <p>
            Save your preferred routes for faster access anytime.
        </p>
    </div>

</div>

<h2 class="section-title">System Overview</h2>

<div class="stats">

    <div class="stat-card">
        <h2><?= (int) $stats['routes'] ?></h2>
        <p>Routes</p>
    </div>

    <div class="stat-card">
        <h2><?= (int) $stats['locations'] ?></h2>
        <p>Locations</p>
    </div>

    <div class="stat-card">
        <h2><?= (int) $stats['transport_types'] ?></h2>
        <p>Transport Types</p>
    </div>

    <div class="stat-card">
        <h2><?= (int) $stats['users'] ?></h2>
        <p>Users</p>
    </div>

</div>

<div class="info-section">
    
    <div class="info-card">

        <h3><i class="fas fa-user-shield"></i> Administrator Features</h3>

        <ul>
            <li><i class="fas fa-check"></i> Manage Users</li>
            <li><i class="fas fa-check"></i> Manage Routes</li>
            <li><i class="fas fa-check"></i> Manage Fares</li>
            <li><i class="fas fa-check"></i> Manage Vehicles</li>
            <li><i class="fas fa-check"></i> Generate Reports</li>
        </ul>

    </div>

    <div class="info-card">

        <h3><i class="fas fa-user"></i> User Features</h3>

        <ul>
            <li><i class="fas fa-check"></i> Search Routes</li>
            <li><i class="fas fa-check"></i> Check Fare Details</li>
            <li><i class="fas fa-check"></i> Save Favourite Routes</li>
            <li><i class="fas fa-check"></i> Fare History</li>
            <li><i class="fas fa-check"></i> Submit Feedback</li>
        </ul>

    </div>

</div>

<section class="cta">

    <h2>Start Your Journey Today</h2>

    <p>
        Register now to access transport information, save favourite routes,
        and enjoy a smarter travel experience.
    </p>

    <a href="auth/register.php">
        <i class="fas fa-arrow-right"></i> Create Account
    </a>

</section>