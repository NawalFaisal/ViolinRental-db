<?php
include 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    $stmt = $conn->prepare(
        "INSERT INTO CUSTOMER (name, phone_number, street, city, postal_code, province) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssss',
        $_POST['name'], $_POST['phone_number'],
        $_POST['street'], $_POST['city'],
        $_POST['postal_code'], $_POST['province']
    );
    $message = $stmt->execute() ? "Customer added successfully! ID: {$conn->insert_id}" : "Error: " . $stmt->error;
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head><title>Insert Customer</title></head>
<body>
<h1>Insert Customer</h1>
<?php if ($message) echo "<p>$message</p>"; ?>
<form method="POST">
    Name: <input type="text" name="name" required><br><br>
    Phone: <input type="text" name="phone_number" required><br><br>
    Street: <input type="text" name="street"><br><br>
    City: <input type="text" name="city"><br><br>
    Postal Code: <input type="text" name="postal_code"><br><br>
    Province: <input type="text" name="province"><br><br>
    <button type="submit">Insert</button>
</form>
<a href="query.php">View all customers</a>
</body>
</html>
