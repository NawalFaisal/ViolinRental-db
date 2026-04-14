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
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Violin Rental</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
h1 { margin-bottom: 5px; }
p.welcome { margin-bottom: 20px; color: #555; font-size: 0.9rem; }
nav { margin-bottom: 25px; }
nav a { margin-right: 12px; text-decoration: none; color: #000; font-size: 0.9rem; }
nav a:hover { text-decoration: underline; }
.nav-sep { margin-right: 12px; color: #aaa; }
.stats { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
.stat-card { background: #fff; border: 1px solid #ccc; padding: 15px 20px; min-width: 140px; }
.stat-label { font-size: 0.8rem; color: #555; margin-bottom: 5px; }
.stat-value { font-size: 1.8rem; font-weight: bold; }
table { border-collapse: collapse; width: 100%; background: #fff; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 6px 10px; }
th { background: #333; color: #fff; text-align: left; }
.badge { padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
.badge-active { background: #d4edda; color: #155724; }
.badge-past   { background: #e2e2e2; color: #555; }
.footer { margin-top: 20px; font-size: 0.85rem; color: #555; }
</style>
</head>
<body>

<h1>Admin Dashboard — Violin Rental</h1>
<p class="welcome">Logged in as <strong><?= htmlspecialchars(current_username()) ?></strong></p>

<nav>
  <a href="admin_dashboard.php">Dashboard</a><span class="nav-sep">|</span>
  <a href="query.php">View Customers</a><span class="nav-sep">|</span>
  <a href="insert.php">Add Customer</a><span class="nav-sep">|</span>
  <a href="products.php">Products</a><span class="nav-sep">|</span>
  <a href="rentals.php">All Rentals</a><span class="nav-sep">|</span>
  <a href="rental_insert.php">New Rental</a><span class="nav-sep">|</span>
  <a href="queries.php">Advanced Queries</a><span class="nav-sep">|</span>
  <a href="logout.php">Sign Out</a>
</nav>

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
    <div class="stat-value">$<?= number_format($stats['revenue'], 2) ?></div>
  </div>
</div>

<h2>Recent Rentals</h2>
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

</body>
</html>
