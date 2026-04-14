<?php
// ============================================================
// customer_dashboard.php — Customer portal home
// ============================================================
require_once 'session.php';
require_once 'db.php';
require_login('customer');

$cid  = current_customer_id();
$conn = getConnection();

// Customer profile
$stmt = $conn->prepare("SELECT * FROM CUSTOMER WHERE customer_id = ?");
$stmt->bind_param('i', $cid);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Current/past rentals
$stmt = $conn->prepare(
    "SELECT r.rental_id, r.rental_date, r.rental_end_date, r.total_days,
            rec.total_price, rec.issue_date
     FROM RENTAL r
     LEFT JOIN RECEIPT rec ON r.receipt_id = rec.receipt_id
     WHERE r.customer_id = ?
     ORDER BY r.rental_date DESC"
);
$stmt->bind_param('i', $cid);
$stmt->execute();
$rentals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My Account — Violin Rental</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
h1 { margin-bottom: 5px; }
nav { margin-bottom: 25px; }
nav a { margin-right: 12px; text-decoration: none; color: #000; font-size: 0.9rem; }
nav a:hover { text-decoration: underline; }
.nav-sep { margin-right: 12px; color: #aaa; }
.profile { background: #fff; border: 1px solid #ccc; padding: 15px 20px; margin-bottom: 25px; }
.profile table { border: none; width: auto; }
.profile td { border: none; padding: 4px 12px 4px 0; font-size: 0.9rem; }
.profile td:first-child { font-weight: bold; color: #555; }
h2 { margin-bottom: 10px; }
table { border-collapse: collapse; width: 100%; background: #fff; margin-top: 10px; }
th, td { border: 1px solid #ccc; padding: 6px 10px; }
th { background: #333; color: #fff; text-align: left; }
.badge { padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
.badge-active { background: #d4edda; color: #155724; }
.badge-past   { background: #e2e2e2; color: #555; }
.empty { padding: 20px; color: #555; }
</style>
</head>
<body>

<h1>My Account — <?= htmlspecialchars($customer['name']) ?></h1>

<nav>
  <a href="customer_dashboard.php">My Profile</a><span class="nav-sep">|</span>
  <a href="products.php">Browse Products</a><span class="nav-sep">|</span>
  <a href="rental_insert.php">Rent a Violin</a><span class="nav-sep">|</span>
  <a href="logout.php">Sign Out</a>
</nav>

<h2>My Profile</h2>
<div class="profile">
  <table>
    <tr><td>Name</td><td><?= htmlspecialchars($customer['name']) ?></td></tr>
    <tr><td>Phone</td><td><?= htmlspecialchars($customer['phone_number']) ?></td></tr>
    <tr><td>Email</td><td><?= htmlspecialchars($customer['email'] ?? '—') ?></td></tr>
    <tr><td>City</td><td><?= htmlspecialchars($customer['city'] ?? '—') ?></td></tr>
    <tr><td>Province</td><td><?= htmlspecialchars($customer['province'] ?? '—') ?></td></tr>
    <tr><td>Postal Code</td><td><?= htmlspecialchars($customer['postal_code'] ?? '—') ?></td></tr>
  </table>
</div>

<h2>My Rentals</h2>
<?php if ($rentals): ?>
<table>
  <thead>
    <tr><th>Rental ID</th><th>Start Date</th><th>End Date</th><th>Days</th><th>Total Paid</th><th>Status</th></tr>
  </thead>
  <tbody>
    <?php foreach ($rentals as $r): ?>
    <?php $active = $r['rental_end_date'] >= date('Y-m-d'); ?>
    <tr>
      <td>#<?= $r['rental_id'] ?></td>
      <td><?= $r['rental_date'] ?></td>
      <td><?= $r['rental_end_date'] ?></td>
      <td><?= $r['total_days'] ?></td>
      <td><?= $r['total_price'] !== null ? '$' . number_format($r['total_price'], 2) : '—' ?></td>
      <td><span class="badge <?= $active ? 'badge-active' : 'badge-past' ?>"><?= $active ? 'Active' : 'Ended' ?></span></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
  <div class="empty">No rentals yet. <a href="rental_insert.php">Rent a violin</a></div>
<?php endif; ?>

</body>
</html>