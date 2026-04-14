<?php
require_once 'session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Unauthorized</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=DM+Mono&display=swap" rel="stylesheet">
<style>
  body { background:#f5f0e8; display:grid; place-items:center; min-height:100vh; font-family:'DM Mono',monospace; text-align:center; }
  h1 { font-family:'Cormorant Garamond',serif; font-size:3rem; color:#1a1208; }
  p  { color:#7a6a52; margin-top:.8rem; }
  a  { color:#b8621a; }
</style>
</head>
<body>
  <div>
    <h1>403 — Unauthorized</h1>
    <p>You don't have permission to view this page.</p>
    <p><a href="<?= is_admin() ? 'admin_dashboard.php' : (is_logged_in() ? 'customer_dashboard.php' : 'login.php') ?>">← Go back</a></p>
  </div>
</body>
</html>