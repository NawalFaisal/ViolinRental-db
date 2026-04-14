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
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Account — Violin Rental</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --cream: #f5f0e8; --ink: #1a1208; --amber: #b8621a;
    --amber2: #d97c2a; --warm: #e8dcc8; --muted: #7a6a52;
    --sidebar-w: 210px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { display: flex; min-height: 100vh; background: var(--cream); font-family: 'DM Mono', monospace; color: var(--ink); }

  .sidebar {
    width: var(--sidebar-w); background: var(--ink); color: var(--cream);
    display: flex; flex-direction: column; padding: 2rem 0; position: fixed; top: 0; left: 0; bottom: 0;
  }
  .sidebar-brand { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; padding: 0 1.5rem 2rem; border-bottom: 1px solid rgba(255,255,255,.1); line-height: 1.3; }
  .sidebar-brand span { display: block; font-size: .62rem; letter-spacing: .15em; text-transform: uppercase; opacity: .5; margin-top: .2rem; }
  nav { flex: 1; padding: 1.5rem 0; }
  nav a { display: block; padding: .65rem 1.5rem; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; text-decoration: none; color: rgba(245,240,232,.6); transition: color .2s, background .2s; }
  nav a:hover, nav a.active { color: var(--cream); background: rgba(184,98,26,.25); }
  nav a.active { border-left: 3px solid var(--amber); }
  .sidebar-footer { padding: 1rem 1.5rem; font-size: .7rem; color: rgba(255,255,255,.35); }
  .sidebar-footer a { color: var(--amber); text-decoration: none; }

  .main { margin-left: var(--sidebar-w); flex: 1; padding: 2.5rem 3rem; }
  .page-header { margin-bottom: 2.5rem; }
  .page-header h1 { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; }
  .page-header p { font-size: .75rem; color: var(--muted); margin-top: .3rem; }

  /* Profile card */
  .profile-card {
    background: #fff; border: 1px solid rgba(184,98,26,.18); border-radius: 2px;
    padding: 1.6rem 2rem; margin-bottom: 2.5rem; display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.2rem;
  }
  .profile-field label { font-size: .62rem; letter-spacing: .15em; text-transform: uppercase; color: var(--muted); display: block; margin-bottom: .3rem; }
  .profile-field p { font-size: .85rem; color: var(--ink); }

  .section-title { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; margin-bottom: 1rem; }
  .table-wrap { background: #fff; border: 1px solid rgba(184,98,26,.18); border-radius: 2px; overflow: auto; }
  table { width: 100%; border-collapse: collapse; font-size: .8rem; }
  th { background: var(--ink); color: var(--cream); font-size: .62rem; letter-spacing: .12em; text-transform: uppercase; padding: .8rem 1.2rem; text-align: left; }
  td { padding: .8rem 1.2rem; border-bottom: 1px solid rgba(184,98,26,.1); }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--cream); }
  .badge { display: inline-block; padding: .2rem .6rem; border-radius: 20px; font-size: .65rem; letter-spacing: .08em; text-transform: uppercase; }
  .badge-active { background: #d4edda; color: #155724; }
  .badge-past   { background: var(--warm); color: var(--muted); }

  .empty { text-align: center; padding: 2.5rem; font-size: .8rem; color: var(--muted); }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">Violin Rental<span>My Account</span></div>
  <nav>
    <a href="customer_dashboard.php" class="active">My Profile</a>
    <a href="products.php">Browse Products</a>
    <a href="rental_insert.php">Rent a Violin</a>
  </nav>
  <div class="sidebar-footer">
    <?= htmlspecialchars(current_username()) ?><br>
    <a href="logout.php">Sign out</a>
  </div>
</aside>

<main class="main">
  <div class="page-header">
    <h1>Welcome, <?= htmlspecialchars($customer['name']) ?></h1>
    <p>Your rental profile and history.</p>
  </div>

  <div class="profile-card">
    <div class="profile-field"><label>Name</label><p><?= htmlspecialchars($customer['name']) ?></p></div>
    <div class="profile-field"><label>Phone</label><p><?= htmlspecialchars($customer['phone_number']) ?></p></div>
    <div class="profile-field"><label>Email</label><p><?= htmlspecialchars($customer['email'] ?? '—') ?></p></div>
    <div class="profile-field"><label>City</label><p><?= htmlspecialchars($customer['city'] ?? '—') ?></p></div>
    <div class="profile-field"><label>Province</label><p><?= htmlspecialchars($customer['province'] ?? '—') ?></p></div>
    <div class="profile-field"><label>Postal Code</label><p><?= htmlspecialchars($customer['postal_code'] ?? '—') ?></p></div>
  </div>

  <h2 class="section-title">My Rentals</h2>
  <div class="table-wrap">
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
      <div class="empty">No rentals yet. <a href="rental_insert.php">Rent a violin →</a></div>
    <?php endif; ?>
  </div>
</main>

</body>
</html>