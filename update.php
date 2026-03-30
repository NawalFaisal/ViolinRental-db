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
    $stmt = $conn->prepare(
        "UPDATE CUSTOMER SET name=?, phone_number=?, street=?, city=?, postal_code=?, province=? WHERE customer_id=?"
    );
    $stmt->bind_param('ssssssi',
        $_POST['name'], $_POST['phone_number'],
        $_POST['street'], $_POST['city'],
        $_POST['postal_code'], $_POST['province'],
        $_POST['customer_id']
    );
    $message = $stmt->execute() ? "Customer updated successfully." : "Error: " . $stmt->error;
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Update Customer</title></head>
<body>
<h1>Update Customer</h1>
<?php if ($message) echo "<p>$message</p>"; ?>

<form method="GET">
    Enter Customer ID: <input type="number" name="id" required>
    <button type="submit">Look Up</button>
</form>

<?php if ($row): ?>
<br>
<form method="POST">
    <input type="hidden" name="customer_id" value="<?= $row['customer_id'] ?>">
    Name: <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" required><br><br>
    Phone: <input type="text" name="phone_number" value="<?= htmlspecialchars($row['phone_number']) ?>" required><br><br>
    Street: <input type="text" name="street" value="<?= htmlspecialchars($row['street'] ?? '') ?>"><br><br>
    City: <input type="text" name="city" value="<?= htmlspecialchars($row['city'] ?? '') ?>"><br><br>
    Postal Code: <input type="text" name="postal_code" value="<?= htmlspecialchars($row['postal_code'] ?? '') ?>"><br><br>
    Province: <input type="text" name="province" value="<?= htmlspecialchars($row['province'] ?? '') ?>"><br><br>
    <button type="submit">Save Changes</button>
</form>
<?php endif; ?>
<a href="query.php">View all customers</a>
</body>
</html>
