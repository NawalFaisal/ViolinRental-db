<?php
require_once 'session.php';
require_once 'db.php';
require_login('admin');
$conn = getConnection();
$search = trim($_GET['search'] ?? '');
$results = [];

if ($search) {
    $stmt = $conn->prepare(
        "SELECT customer_id, name, phone_number, city, province
         FROM CUSTOMER WHERE name LIKE ? OR city LIKE ? ORDER BY name"
    );
    $like = "%$search%";
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $r = $conn->query("SELECT customer_id, name, phone_number, city, province FROM CUSTOMER ORDER BY name");
    $results = $r->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head><title>Query Customers</title></head>
<body>
<h1>Query Customers</h1>

<form method="GET">
    Search by name or city: <input type="text" name="search" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <a href="query.php">Clear</a>
</form>

<br>
<p><?= count($results) ?> result(s) found</p>

<?php if ($results): ?>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Phone</th>
        <th>City</th>
        <th>Province</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($results as $row): ?>
    <tr>
        <td><?= $row['customer_id'] ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['phone_number']) ?></td>
        <td><?= htmlspecialchars($row['city'] ?? '') ?></td>
        <td><?= htmlspecialchars($row['province'] ?? '') ?></td>
        <td>
            <a href="update.php?id=<?= $row['customer_id'] ?>">Edit</a> |
            <a href="delete.php?id=<?= $row['customer_id'] ?>">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>No results found.</p>
<?php endif; ?>

<br>
<a href="insert.php">Add new customer</a> | <a href="admin_dashboard.php">← Back to Dashboard</a>
</body>
</html>
