<?php
// setup_passwords.php
require_once 'db.php';

$users = [
    'admin' => 'admin123',
    'alice' => 'password123',
    'bob'   => 'password123',
    'carol' => 'password123',
    'david' => 'password123',
    'eva'   => 'password123',
];

$conn = getConnection();
$stmt = $conn->prepare("UPDATE USERS SET password_hash = ? WHERE username = ?");

foreach ($users as $username => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt->bind_param('ss', $hash, $username);
    $stmt->execute();
    echo "Updated: <strong>$username</strong><br>";
}

$stmt->close();
$conn->close();
echo "<br><strong style='color:red'>DELETE THIS FILE NOW.</strong>";
?>