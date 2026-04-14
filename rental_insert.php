<?php
require_once 'session.php';
require_once 'db.php';

require_login(); // both admin and customers can create rentals

$conn = getConnection();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cid   = (int)$_POST['customer_id'];
    $start = $_POST['rental_date'];
    $end   = $_POST['rental_end_date'];

    $method = $_POST['payment_method'];
    $card   = trim($_POST['client_card_paypal']);

    $product_ids = isset($_POST['product_id']) ? $_POST['product_id'] : [];
    $quantities  = isset($_POST['quantity']) ? $_POST['quantity'] : [];

    // basic checks
    if (strtotime($end) < strtotime($start)) {
        $msg = "End date has to be after start date";
    }
    else if (empty($product_ids)) {
        $msg = "Please select at least one product";
    }
    else {

        $conn->begin_transaction();

        try {

            // create receipt first
            $conn->query("INSERT INTO RECEIPT (customer_id, total_price, issue_date)
                          VALUES ($cid, 0, CURDATE())");

            $receipt_id = $conn->insert_id;

            // create rental
            $stmt = $conn->prepare("INSERT INTO RENTAL (customer_id, receipt_id, rental_date, rental_end_date)
                                    VALUES (?,?,?,?)");
            $stmt->bind_param("iiss", $cid, $receipt_id, $start, $end);
            $stmt->execute();

            $rental_id = $conn->insert_id;
            $stmt->close();

            $total = 0;

            // calculate days
            $days = (strtotime($end) - strtotime($start)) / 86400;

            foreach ($product_ids as $i => $pid) {

                $pid = (int)$pid;
                $qty = isset($quantities[$i]) ? (int)$quantities[$i] : 1;

                // get product price (simple way)
                $res = $conn->query("SELECT price FROM PRODUCT WHERE product_id=$pid");
                $row = $res->fetch_assoc();
                $price = $row['price'];

                // insert rental item
                $stmt2 = $conn->prepare("INSERT INTO RENTAL_ITEM (rental_id, product_id, quantity, rental_rate)
                                         VALUES (?,?,?,?)");
                $stmt2->bind_param("iiid", $rental_id, $pid, $qty, $price);
                $stmt2->execute();
                $stmt2->close();

                $total += $price * $qty * $days;
            }

            // update receipt total
            $stmt3 = $conn->prepare("UPDATE RECEIPT SET total_price=? WHERE receipt_id=?");
            $stmt3->bind_param("di", $total, $receipt_id);
            $stmt3->execute();
            $stmt3->close();

            // create payment
            $cardVal = $card ? $card : null;

            $stmt4 = $conn->prepare("INSERT INTO PAYMENT (customer_id, receipt_id, payment_method, client_card_paypal, amount)
                                     VALUES (?,?,?,?,?)");
            $stmt4->bind_param("iissd", $cid, $receipt_id, $method, $cardVal, $total);
            $stmt4->execute();
            $stmt4->close();

            $conn->commit();

            $msg = "Rental created! ID: $rental_id (Total: $" . number_format($total, 2) . ")";

        } catch (Exception $e) {

            $conn->rollback();
            $msg = "Something went wrong: " . $e->getMessage();
        }
    }
}

// get customers + products
$customers = [];
$res1 = $conn->query("SELECT customer_id, name FROM CUSTOMER ORDER BY name");
while ($row = $res1->fetch_assoc()) {
    $customers[] = $row;
}

$products = [];
$res2 = $conn->query("SELECT product_id, type, size, price, stock FROM PRODUCT WHERE stock > 0 ORDER BY type");
while ($row = $res2->fetch_assoc()) {
    $products[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>New Rental</title>

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

.item-row {
    margin-bottom: 10px;
}

button {
    padding: 6px 10px;
}

.msg {
    margin-bottom: 15px;
    padding: 10px;
    background: #eee;
}
</style>
</head>

<body>

<h1>Create Rental</h1>

<?php if ($msg): ?>
<div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<form method="POST">

<h3>Basic Info</h3>

Customer:
<?php if (is_admin()): ?>
<select name="customer_id" required>
<option value="">Select</option>
<?php foreach ($customers as $c): ?>
<option value="<?= $c['customer_id'] ?>">
    <?= htmlspecialchars($c['name']) ?>
</option>
<?php endforeach; ?>
</select>
<?php else: ?>
<input type="hidden" name="customer_id" value="<?= current_customer_id() ?>">
<strong><?php foreach ($customers as $c) { if ($c['customer_id'] == current_customer_id()) echo htmlspecialchars($c['name']); } ?></strong>
<?php endif; ?>

<br>

Start Date:
<input type="date" name="rental_date" value="<?= date('Y-m-d') ?>" required>

End Date:
<input type="date" name="rental_end_date" required>

<br><br>

Payment:
<select name="payment_method">
    <option value="cash">Cash</option>
    <option value="card">Card</option>
    <option value="paypal">PayPal</option>
</select>

<input type="text" name="client_card_paypal" placeholder="Card/PayPal (optional)">

<h3>Products</h3>

<div id="items">

<div class="item-row">
<select name="product_id[]" required>
<option value="">Select product</option>
<?php foreach ($products as $p): ?>
<option value="<?= $p['product_id'] ?>">
    <?= htmlspecialchars($p['type']) ?> 
    <?= $p['size'] ? '(' . $p['size'] . ')' : '' ?>
    - $<?= number_format($p['price'],2) ?>/day
</option>
<?php endforeach; ?>
</select>

Qty:
<input type="number" name="quantity[]" value="1" min="1">

<button type="button" onclick="removeItem(this)">Remove</button>
</div>

</div>

<br>

<button type="button" onclick="addItem()">+ Add Product</button>

<br><br>

<button type="submit">Create Rental</button>

</form>

<script>
function addItem() {
    const container = document.getElementById('items');

    const row = document.createElement('div');
    row.className = 'item-row';

    row.innerHTML = `
        <select name="product_id[]" required>
            <option value="">Select product</option>
            <?php foreach ($products as $p): ?>
            <option value="<?= $p['product_id'] ?>">
                <?= htmlspecialchars($p['type']) ?> <?= $p['size'] ? '(' . $p['size'] . ')' : '' ?>
                - $<?= number_format($p['price'],2) ?>/day
            </option>
            <?php endforeach; ?>
        </select>

        Qty:
        <input type="number" name="quantity[]" value="1" min="1">

        <button type="button" onclick="removeItem(this)">Remove</button>
    `;

    container.appendChild(row);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('#items .item-row');
    if (rows.length > 1) {
        btn.parentNode.remove();
    }
}
</script>

</body>
</html>