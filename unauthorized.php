<?php
require_once 'session.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Unauthorized</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 40px; text-align: center; }
h1 { font-size: 2rem; margin-bottom: 10px; }
p { color: #555; margin-top: 8px; }
a { color: #000; }
</style>
</head>
<body>
  <h1>403 — Unauthorized</h1>
  <p>You don't have permission to view this page.</p>
  <p><a href="<?= is_admin() ? 'admin_dashboard.php' : (is_logged_in() ? 'customer_dashboard.php' : 'login.php') ?>">← Go back</a></p>
</body>
</html>