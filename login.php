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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Violin Rental — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --cream:  #f5f0e8;
    --ink:    #1a1208;
    --amber:  #b8621a;
    --amber2: #d97c2a;
    --warm:   #e8dcc8;
    --muted:  #7a6a52;
    --error:  #8b2020;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    min-height: 100vh;
    background: var(--cream);
    display: grid;
    place-items: center;
    font-family: 'DM Mono', monospace;
    position: relative;
    overflow: hidden;
  }

  /* Decorative background lines */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      repeating-linear-gradient(
        0deg,
        transparent,
        transparent 59px,
        rgba(184,98,26,.08) 59px,
        rgba(184,98,26,.08) 60px
      );
    pointer-events: none;
  }

  /* Large decorative text */
  body::after {
    content: 'VIOLIN';
    position: fixed;
    bottom: -0.15em;
    right: -0.05em;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(120px, 20vw, 260px);
    font-weight: 600;
    color: rgba(184,98,26,.06);
    line-height: 1;
    pointer-events: none;
    user-select: none;
  }

  .card {
    background: #fff;
    border: 1px solid rgba(184,98,26,.25);
    border-radius: 2px;
    padding: 3rem 3.5rem;
    width: min(420px, 92vw);
    position: relative;
    box-shadow:
      4px 4px 0 rgba(184,98,26,.12),
      0 20px 60px rgba(26,18,8,.08);
    animation: rise .5s ease both;
  }

  @keyframes rise {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Corner ornament */
  .card::before {
    content: '♩';
    position: absolute;
    top: 1rem; right: 1.25rem;
    font-size: 1.4rem;
    color: rgba(184,98,26,.3);
  }

  .brand {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.05rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .3rem;
  }

  h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.4rem;
    font-weight: 600;
    color: var(--ink);
    line-height: 1.1;
    margin-bottom: 2.2rem;
  }

  .divider {
    width: 2.5rem;
    height: 2px;
    background: var(--amber);
    margin-bottom: 2.2rem;
    margin-top: -.8rem;
  }

  .field {
    margin-bottom: 1.4rem;
  }

  label {
    display: block;
    font-size: .68rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: .45rem;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: .75rem 1rem;
    background: var(--cream);
    border: 1px solid rgba(184,98,26,.25);
    border-radius: 2px;
    font-family: 'DM Mono', monospace;
    font-size: .9rem;
    color: var(--ink);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
  }

  input:focus {
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(184,98,26,.1);
  }

  .error-msg {
    background: #fdf0f0;
    border-left: 3px solid var(--error);
    padding: .7rem 1rem;
    font-size: .8rem;
    color: var(--error);
    margin-bottom: 1.4rem;
    border-radius: 0 2px 2px 0;
  }

  button[type="submit"] {
    width: 100%;
    padding: .85rem;
    background: var(--amber);
    color: #fff;
    border: none;
    border-radius: 2px;
    font-family: 'DM Mono', monospace;
    font-size: .8rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .2s, transform .1s;
    margin-top: .4rem;
  }

  button[type="submit"]:hover  { background: var(--amber2); }
  button[type="submit"]:active { transform: scale(.98); }

  .hint {
    margin-top: 1.8rem;
    font-size: .72rem;
    color: var(--muted);
    text-align: center;
    line-height: 1.7;
  }

  .hint strong {
    color: var(--ink);
  }
</style>
</head>
<body>
<div class="card">
  <p class="brand">Violin Rental Agency</p>
  <h1>Sign In</h1>
  <div class="divider"></div>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autofocus required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Sign In →</button>
  </form>

  <p class="hint">
    Admin login: <strong>admin</strong> / <strong>admin123</strong><br>
    Customer login: <strong>alice</strong> / <strong>password123</strong>
  </p>
</div>
</body>
</html>