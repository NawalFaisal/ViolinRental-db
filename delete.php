<?php
include 'db.php';
$message = '';
$row = null;

$conn = getConnection();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $r = $conn->query("SELECT * FROM CUSTOMER WHERE customer_id=$id");
    $row = $r ? $r->fetch_assoc() : null;
    if (!$row) $message = "No customer found with ID $id.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['customer_id'];
    $stmt = $conn->prepare("DELETE FROM CUSTOMER WHERE customer_id=?");
    $stmt->bind_param('i', $id);
    $message = $stmt->execute() && $conn->affected_rows > 0
        ? "Customer ID $id deleted successfully."
        : "Error: " . $stmt->error;
    $stmt->close();
    $row = null;
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Delete Customer</title></head>
<body>
<h1>Delete Customer</h1>
<?php if ($message) echo "<p>$message</p>"; ?>

<form method="GET">
    Enter Customer ID: <input type="number" name="id" required>
    <button type="submit">Look Up</button>
</form>

<?php if ($row): ?>
<br>
<p><strong>Found:</strong> <?= htmlspecialchars($row['name']) ?> — <?= htmlspecialchars($row['city'] ?? '') ?></p>
<form method="POST" onsubmit="return confirm('Are you sure you want to delete this customer?')">
    <input type="hidden" name="customer_id" value="<?= $row['customer_id'] ?>">
    <button type="submit">Confirm Delete</button>
</form>
<?php endif; ?>
<a href="query.php">View all customers</a>
</body>
</html>
