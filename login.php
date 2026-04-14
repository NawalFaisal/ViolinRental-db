<?php
// ============================================================
// login.php — Login page for admin and customers
// ============================================================
require_once 'session.php';
require_once 'db.php';

// Already logged in → redirect to appropriate dashboard
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? 'admin_dashboard.php' : 'customer_dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare(
            "SELECT u.user_id, u.username, u.password_hash, u.role, u.customer_id
             FROM USERS u
             WHERE u.username = ?"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']     = $user['user_id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['customer_id'] = $user['customer_id'];

            header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'customer_dashboard.php'));
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Violin Rental — Sign In</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 40px; }
h1 { margin-bottom: 20px; }
input { padding: 6px; margin: 5px 0; width: 250px; display: block; }
button { padding: 6px 14px; margin-top: 10px; }
.error { background: #f8d7da; padding: 8px; margin-bottom: 15px; }
.hint { margin-top: 20px; font-size: 0.9rem; color: #555; }
</style>
</head>
<body>
<h1>Violin Rental — Sign In</h1>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" autocomplete="off">
  <label>Username</label>
  <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" autofocus required>
  <label>Password</label>
  <input type="password" name="password" required>
  <button type="submit">Sign In</button>
</form>

<p class="hint">
  Admin: <strong>admin</strong> / <strong>admin123</strong><br>
  Customer: <strong>alice</strong> / <strong>password123</strong>
</p>
</body>
</html>