<?php
require_once 'session.php';
require_once 'db.php';

require_login('admin');

$conn = getConnection();
$msg = '';

// delete rental
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM RENTAL WHERE rental_id=?");
    $stmt->bind_param("i", $id);
    $msg = $stmt->execute() ? "Rental #$id deleted." : "Error deleting rental.";
    $stmt->close();
}

// update rental dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $stmt = $conn->prepare("UPDATE RENTAL SET rental_date=?, rental_end_date=? WHERE rental_id=?");
    $stmt->bind_param("ssi", $_POST['rental_date'], $_POST['rental_end_date'], $_POST['rental_id']);
    $msg = $stmt->execute() ? "Rental updated." : "Error updating rental.";
    $stmt->close();
}

$edit = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM RENTAL WHERE rental_id=$id");
    $edit = $res->fetch_assoc();
}

$rentals = [];
$res = $conn->query("
    SELECT r.rental_id, c.name AS customer, r.rental_date, r.rental_end_date, r.total_days,
           rec.total_price,
           CASE WHEN r.rental_end_date >= CURDATE() THEN 'Active' ELSE 'Ended' END AS status
    FROM RENTAL r
    JOIN CUSTOMER c ON r.customer_id = c.customer_id
    LEFT JOIN RECEIPT rec ON r.receipt_id = rec.receipt_id
    ORDER BY r.rental_date DESC
");

while ($row = $res->fetch_assoc()) {
    $rentals[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rentals</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    padding: 20px;
}

input, select {
    padding: 6px;
    margin: 5px 0;
}

table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
    background: #fff;
}

th, td {
    border: 1px solid #ccc;
    padding: 6px 10px;
}

th {
    background: #333;
    color: #fff;
}

.msg {
    background: #f9f9f9;
    padding: 10px;
    margin-bottom: 15px;
}

button {
    padding: 6px 10px;
    background: #333;
    color: white;
    border: none;
    cursor: pointer;
}

button:hover { background: #555; }

a {
    margin-right: 10px;
    color: #000;
    text-decoration: none;
}

a:hover { text-decoration: underline; }

.badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.ba { background: #d4edda; color: #155724; }
.bp { background: #e8dcc8; color: #7a6a52; }
</style>
</head>

<body>

<h1>All Rentals</h1>

<?php if ($msg): ?>
<div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($edit): ?>
<div class="card">
    <h2>Edit Rental #<?= $edit['rental_id'] ?></h2>
    <form method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="rental_id" value="<?= $edit['rental_id'] ?>">
        <div>
            <label>Start Date</label>
            <input type="date" name="rental_date" value="<?= $edit['rental_date'] ?>" required>
        </div>
        <div>
            <label>End Date</label>
            <input type="date" name="rental_end_date" value="<?= $edit['rental_end_date'] ?>" required>
        </div>
        <button type="submit">Save Changes</button>
        <a href="rentals.php">Cancel</a>
    </form>
</div>
<?php endif; ?>

<h2>Rental List</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Start</th>
            <th>End</th>
            <th>Days</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rentals as $r): ?>
        <tr>
            <td>#<?= $r['rental_id'] ?></td>
            <td><?= htmlspecialchars($r['customer']) ?></td>
            <td><?= $r['rental_date'] ?></td>
            <td><?= $r['rental_end_date'] ?></td>
            <td><?= $r['total_days'] ?></td>
            <td><?= $r['total_price'] !== null ? '$'.number_format($r['total_price'], 2) : '—' ?></td>
            <td><span class="badge <?= $r['status'] == 'Active' ? 'ba' : 'bp' ?>"><?= $r['status'] ?></span></td>
            <td>
                <a href="rentals.php?edit=<?= $r['rental_id'] ?>">Edit</a>
                <a href="rentals.php?delete=<?= $r['rental_id'] ?>" onclick="return confirm('Delete this rental?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<a href="rental_insert.php">+ New Rental</a>

</body>
</html>