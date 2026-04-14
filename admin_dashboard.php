<?php
// ============================================================
// admin_dashboard.php — Admin home page
// ============================================================
require_once 'session.php';
require_once 'db.php';
require_login('admin');

$conn = getConnection();

// Quick stats
$stats = [];
foreach ([
    'customers'     => "SELECT COUNT(*) FROM CUSTOMER",
    'products'      => "SELECT COUNT(*) FROM PRODUCT",
    'active_rentals'=> "SELECT COUNT(*) FROM RENTAL WHERE rental_end_date >= CURDATE()",
    'revenue'       => "SELECT COALESCE(SUM(amount),0) FROM PAYMENT",
] as $key => $sql) {
    $stats[$key] = $conn->query($sql)->fetch_row()[0];
}

// Recent rentals
$recent = $conn->query(
    "SELECT r.rental_id, c.name, r.rental_date, r.rental_end_date, r.total_days
     FROM RENTAL r JOIN CUSTOMER c ON r.customer_id = c.customer_id
     ORDER BY r.rental_date DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Violin Rental</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --cream: #f5f0e8; --ink: #1a1208; --amber: #b8621a;
    --amber2: #d97c2a; --warm: #e8dcc8; --muted: #7a6a52;
    --sidebar-w: 220px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { display: flex; min-height: 100vh; background: var(--cream); font-family: 'DM Mono', monospace; color: var(--ink); }

  /* Sidebar */
  .sidebar {
    width: var(--sidebar-w); background: var(--ink); color: var(--cream);
    display: flex; flex-direction: column; padding: 2rem 0; position: fixed;
    top: 0; left: 0; bottom: 0; z-index: 10;
  }
  .sidebar-brand {
    font-family: 'Cormorant Garamond', serif; font-size: 1.3rem;
    padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,.1);
    line-height: 1.3;
  }
  .sidebar-brand span { display: block; font-size: .65rem; letter-spacing: .15em; text-transform: uppercase; opacity: .5; margin-top: .2rem; }
  nav { flex: 1; padding: 1.5rem 0; }
  nav a {
    display: block; padding: .65rem 1.5rem; font-size: .75rem;
    letter-spacing: .08em; text-transform: uppercase; text-decoration: none;
    color: rgba(245,240,232,.6); transition: color .2s, background .2s;
  }
  nav a:hover, nav a.active { color: var(--cream); background: rgba(184,98,26,.25); }
  nav a.active { border-left: 3px solid var(--amber); }
  .nav-section { font-size: .6rem; letter-spacing: .2em; color: rgba(255,255,255,.25); padding: 1rem 1.5rem .4rem; text-transform: uppercase; }
  .sidebar-footer { padding: 1rem 1.5rem; font-size: .7rem; color: rgba(255,255,255,.35); }
  .sidebar-footer a { color: var(--amber); text-decoration: none; }

  /* Main */
  .main { margin-left: var(--sidebar-w); flex: 1; padding: 2.5rem 3rem; }
  .page-header { margin-bottom: 2.5rem; }
  .page-header h1 { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; }
  .page-header p { font-size: .75rem; color: var(--muted); margin-top: .3rem; }

  /* Stats grid */
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem; }
  .stat-card {
    background: #fff; border: 1px solid rgba(184,98,26,.18); border-radius: 2px;
    padding: 1.4rem 1.6rem; position: relative; overflow: hidden;
  }
  .stat-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: var(--amber); }
  .stat-label { font-size: .62rem; letter-spacing: .15em; text-transform: uppercase; color: var(--muted); margin-bottom: .6rem; }
  .stat-value { font-family: 'Cormorant Garamond', serif; font-size: 2.4rem; font-weight: 600; color: var(--ink); }
  .stat-value.money::before { content: '$'; font-size: 1.2rem; vertical-align: super; }

  /* Table */
  .section-title { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; margin-bottom: 1rem; }
  .table-wrap { background: #fff; border: 1px solid rgba(184,98,26,.18); border-radius: 2px; overflow: auto; }
  table { width: 100%; border-collapse: collapse; font-size: .8rem; }
  th { background: var(--ink); color: var(--cream); font-size: .62rem; letter-spacing: .12em; text-transform: uppercase; padding: .8rem 1.2rem; text-align: left; }
  td { padding: .8rem 1.2rem; border-bottom: 1px solid rgba(184,98,26,.1); }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--cream); }

  .badge {
    display: inline-block; padding: .2rem .6rem; border-radius: 20px;
    font-size: .65rem; letter-spacing: .08em; text-transform: uppercase;
  }
  .badge-active { background: #d4edda; color: #155724; }
  .badge-past   { background: var(--warm); color: var(--muted); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    Violin Rental<span>Admin Panel</span>
  </div>
  <nav>
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <div class="nav-section">Customers</div>
    <a href="query.php">View Customers</a>
    <a href="insert.php">Add Customer</a>
    <div class="nav-section">Inventory</div>
    <a href="products.php">Products</a>
    <div class="nav-section">Rentals</div>
    <a href="rentals.php">All Rentals</a>
    <a href="rental_insert.php">New Rental</a>
    <div class="nav-section">Reports</div>
    <a href="queries.php">Advanced Queries</a>
  </nav>
  <div class="sidebar-footer">
    Logged in as <strong><?= htmlspecialchars(current_username()) ?></strong><br>
    <a href="logout.php">Sign out</a>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars(current_username()) ?>. Here's the agency overview.</p>
  </div>

  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">Customers</div>
      <div class="stat-value"><?= $stats['customers'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Products</div>
      <div class="stat-value"><?= $stats['products'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Active Rentals</div>
      <div class="stat-value"><?= $stats['active_rentals'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value money"><?= number_format($stats['revenue'], 2) ?></div>
    </div>
  </div>

  <h2 class="section-title">Recent Rentals</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Rental ID</th><th>Customer</th><th>Start</th><th>End</th><th>Days</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <?php $active = $r['rental_end_date'] >= date('Y-m-d'); ?>
        <tr>
          <td>#<?= $r['rental_id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= $r['rental_date'] ?></td>
          <td><?= $r['rental_end_date'] ?></td>
          <td><?= $r['total_days'] ?></td>
          <td><span class="badge <?= $active ? 'badge-active' : 'badge-past' ?>"><?= $active ? 'Active' : 'Ended' ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>
