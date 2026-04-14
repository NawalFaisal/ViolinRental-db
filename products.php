<?php
require_once 'session.php';
require_once 'db.php';

require_login(); // both admin and customers can view; write actions checked below

$conn = getConnection();
$msg = '';

// delete product
if (isset($_GET['delete'])) {
    if (!is_admin()) { header('Location: unauthorized.php'); exit; }
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM PRODUCT WHERE product_id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $msg = "Product deleted";
    } else {
        $msg = "Error deleting product";
    }

    $stmt->close();
}

// add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
    if (!is_admin()) { header('Location: unauthorized.php'); exit; }

    $type  = $_POST['type'];
    $size  = $_POST['size'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $mid = $_POST['manufacturer_id'] ? (int)$_POST['manufacturer_id'] : null;

    $stmt = $conn->prepare("INSERT INTO PRODUCT (type, size, price, stock, manufacturer_id)
                             VALUES (?,?,?,?,?)");
    $stmt->bind_param("ssdii", $type, $size, $price, $stock, $mid);

    if ($stmt->execute()) {
        $msg = "Product added (ID: " . $conn->insert_id . ")";
    } else {
        $msg = "Error adding product";
    }

    $stmt->close();
}

// update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!is_admin()) { header('Location: unauthorized.php'); exit; }

    $id    = (int)$_POST['product_id'];
    $type  = $_POST['type'];
    $size  = $_POST['size'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $mid = $_POST['manufacturer_id'] ? (int)$_POST['manufacturer_id'] : null;

    $stmt = $conn->prepare("UPDATE PRODUCT SET type=?, size=?, price=?, stock=?, manufacturer_id=? WHERE product_id=?");
    $stmt->bind_param("ssdiii", $type, $size, $price, $stock, $mid, $id);

    if ($stmt->execute()) {
        $msg = "Product updated";
    } else {
        $msg = "Error updating product";
    }

    $stmt->close();
}

// get product for editing
$edit = null;

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];

    $res = $conn->query("SELECT * FROM PRODUCT WHERE product_id=$id");
    $edit = $res->fetch_assoc();
}

// get all products
$products = [];
$res = $conn->query("
    SELECT p.*, m.name AS mfr
    FROM PRODUCT p
    LEFT JOIN MANUFACTURER_DISTRIBUTOR m ON p.manufacturer_id = m.manufacturer_id
    ORDER BY p.type, p.size
");

while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

// manufacturers for dropdown
$mfrs = [];
$res2 = $conn->query("SELECT manufacturer_id, name FROM MANUFACTURER_DISTRIBUTOR ORDER BY name");

while ($row = $res2->fetch_assoc()) {
    $mfrs[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Products</title>

<style>
body {
    font-family: Arial;
    background: #f4f4f4;
    padding: 20px;
}

input, select {
    padding: 6px;
    margin: 5px 0;
}

table {
    border-collapse: collapse;
    margin-top: 15px;
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
    margin-bottom: 15px;
    padding: 10px;
    background: #eee;
}

button {
    padding: 6px 10px;
}

a {
    margin-right: 5px;
}
</style>
</head>

<body>

<h1>Products</h1>

<?php if ($msg): ?>
<div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if (is_admin()): ?>
<h2><?= $edit ? "Edit Product" : "Add Product" ?></h2>

<form method="POST">

<input type="hidden" name="action" value="<?= $edit ? 'update' : 'insert' ?>">

<?php if ($edit): ?>
<input type="hidden" name="product_id" value="<?= $edit['product_id'] ?>">
<?php endif; ?>

Type:<br>
<input type="text" name="type" value="<?= htmlspecialchars($edit['type'] ?? '') ?>" required>

<br>

Size:<br>
<input type="text" name="size" value="<?= htmlspecialchars($edit['size'] ?? '') ?>">

<br>

Price:<br>
<input type="number" step="0.01" name="price" value="<?= $edit['price'] ?? '' ?>" required>

<br>

Stock:<br>
<input type="number" name="stock" value="<?= $edit['stock'] ?? 0 ?>" required>

<br>

Manufacturer:<br>
<select name="manufacturer_id">
<option value="">None</option>
<?php foreach ($mfrs as $m): ?>
<option value="<?= $m['manufacturer_id'] ?>"
<?= ($edit['manufacturer_id'] ?? '') == $m['manufacturer_id'] ? 'selected' : '' ?>>
<?= htmlspecialchars($m['name']) ?>
</option>
<?php endforeach; ?>
</select>

<br><br>

<button type="submit">
<?= $edit ? 'Update' : 'Add' ?>
</button>

<?php if ($edit): ?>
<a href="products.php">Cancel</a>
<?php endif; ?>

</form>
<?php endif; // end admin-only form ?>

<h2>All Products</h2>

<table>
<tr>
<th>ID</th>
<th>Type</th>
<th>Size</th>
<th>Price</th>
<th>Stock</th>
<th>Manufacturer</th>
<th>Actions</th>
</tr>

<?php foreach ($products as $p): ?>
<tr>
<td><?= $p['product_id'] ?></td>
<td><?= htmlspecialchars($p['type']) ?></td>
<td><?= htmlspecialchars($p['size'] ?? '') ?></td>
<td>$<?= number_format($p['price'], 2) ?></td>
<td><?= $p['stock'] ?></td>
<td><?= htmlspecialchars($p['mfr'] ?? '') ?></td>
<td>
    <?php if (is_admin()): ?>
    <a href="products.php?edit=<?= $p['product_id'] ?>">Edit</a>
    <a href="products.php?delete=<?= $p['product_id'] ?>" onclick="return confirm('Delete this product?')">Delete</a>
    <?php else: ?>—<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>