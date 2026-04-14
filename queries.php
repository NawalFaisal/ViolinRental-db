<?php
require_once 'session.php';
require_once 'db.php';

require_login('admin');

$conn = getConnection();

$queries = [

    1 => [
        'title' => 'Total rentals per customer',
        'desc'  => 'Counts how many rentals each customer made',
        'sql'   => "SELECT c.name, COUNT(r.rental_id) AS total_rentals
                    FROM CUSTOMER c
                    JOIN RENTAL r ON c.customer_id = r.customer_id
                    GROUP BY c.customer_id, c.name
                    ORDER BY total_rentals DESC"
    ],

    2 => [
        'title' => 'Customers with more than 1 rental',
        'desc'  => 'Only shows customers with 2+ rentals',
        'sql'   => "SELECT c.name, COUNT(r.rental_id) AS num_rentals
                    FROM CUSTOMER c
                    JOIN RENTAL r ON c.customer_id = r.customer_id
                    GROUP BY c.customer_id, c.name
                    HAVING COUNT(r.rental_id) > 1
                    ORDER BY num_rentals DESC"
    ],

    3 => [
        'title' => 'Average / min / max price',
        'desc'  => 'Basic stats on product prices',
        'sql'   => "SELECT AVG(price) AS avg_price,
                           MIN(price) AS min_price,
                           MAX(price) AS max_price
                    FROM PRODUCT"
    ],

    4 => [
        'title' => 'Revenue by payment method',
        'desc'  => 'Total revenue grouped by payment type',
        'sql'   => "SELECT payment_method,
                           COUNT(*) AS total_payments,
                           SUM(amount) AS revenue
                    FROM PAYMENT
                    GROUP BY payment_method
                    ORDER BY revenue DESC"
    ],

    5 => [
        'title' => 'Rentals between Jan–Mar 2026',
        'desc'  => 'Date range filter example',
        'sql'   => "SELECT r.rental_id, c.name, r.rental_date, r.rental_end_date, r.total_days
                    FROM RENTAL r
                    JOIN CUSTOMER c ON r.customer_id = c.customer_id
                    WHERE r.rental_date BETWEEN '2026-01-01' AND '2026-03-31'
                    ORDER BY r.rental_date"
    ],

    6 => [
        'title' => 'Manufacturers with no email',
        'desc'  => 'Find rows where email is missing',
        'sql'   => "SELECT manufacturer_id, name, country, contact_phone
                    FROM MANUFACTURER_DISTRIBUTOR
                    WHERE contact_email IS NULL"
    ],

    7 => [
        'title' => "Customers from cities with 'bridge'",
        'desc'  => 'Simple LIKE example',
        'sql'   => "SELECT name, city, province, phone_number
                    FROM CUSTOMER
                    WHERE city LIKE '%bridge%'"
    ],

    8 => [
        'title' => 'Customers who never paid cash',
        'desc'  => 'Uses NOT IN with subquery',
        'sql'   => "SELECT name, email
                    FROM CUSTOMER
                    WHERE customer_id NOT IN (
                        SELECT customer_id
                        FROM PAYMENT
                        WHERE payment_method = 'cash'
                    )"
    ],

    9 => [
        'title' => 'Customers who rented full-size violin',
        'desc'  => 'IN with subquery across tables',
        'sql'   => "SELECT name, email, city
                    FROM CUSTOMER
                    WHERE customer_id IN (
                        SELECT DISTINCT r.customer_id
                        FROM RENTAL r
                        JOIN RENTAL_ITEM ri ON r.rental_id = ri.rental_id
                        JOIN PRODUCT p ON ri.product_id = p.product_id
                        WHERE p.type = 'Violin' AND p.size = '4/4'
                    )"
    ],

    10 => [
        'title' => 'Products above average price',
        'desc'  => 'Compare against overall average',
        'sql'   => "SELECT product_id, type, size, price
                    FROM PRODUCT
                    WHERE price > ALL (
                        SELECT AVG(price) FROM PRODUCT
                    )
                    ORDER BY price DESC"
    ],

    11 => [
        'title' => 'Products never rented',
        'desc'  => 'Uses NOT EXISTS',
        'sql'   => "SELECT p.product_id, p.type, p.size, p.price
                    FROM PRODUCT p
                    WHERE NOT EXISTS (
                        SELECT * FROM RENTAL_ITEM ri
                        WHERE ri.product_id = p.product_id
                    )"
    ],

    12 => [
        'title' => 'All names (UNION)',
        'desc'  => 'Combines customers + users',
        'sql'   => "SELECT name AS name_in_system, 'Customer' AS source
                    FROM CUSTOMER
                    UNION
                    SELECT username, 'User'
                    FROM USERS
                    ORDER BY source, name_in_system"
    ],

    13 => [
        'title' => 'Rental length category',
        'desc'  => 'CASE example',
        'sql'   => "SELECT r.rental_id, c.name, r.total_days,
                        CASE
                            WHEN r.total_days <= 30 THEN 'Short'
                            WHEN r.total_days <= 90 THEN 'Medium'
                            ELSE 'Long'
                        END AS category
                    FROM RENTAL r
                    JOIN CUSTOMER c ON r.customer_id = c.customer_id
                    ORDER BY r.total_days DESC"
    ],

    14 => [
        'title' => 'Maintenance cost per product',
        'desc'  => 'Group + sum maintenance cost',
        'sql'   => "SELECT p.type, p.size,
                           COUNT(ml.maintenance_date) AS times_serviced,
                           SUM(ml.cost) AS total_cost
                    FROM PRODUCT p
                    JOIN MAINTENANCE_LOG ml ON p.product_id = ml.product_id
                    GROUP BY p.product_id, p.type, p.size
                    ORDER BY total_cost DESC"
    ],

    15 => [
        'title' => 'Active rentals',
        'desc'  => 'Shows rentals not finished yet',
        'sql'   => "SELECT r.rental_id, c.name, r.rental_date,
                           r.rental_end_date, r.total_days,
                           COALESCE(rec.total_price, 0) AS amount_charged
                    FROM RENTAL r
                    JOIN CUSTOMER c ON r.customer_id = c.customer_id
                    LEFT JOIN RECEIPT rec ON r.receipt_id = rec.receipt_id
                    WHERE r.rental_end_date >= CURDATE()
                    ORDER BY r.rental_end_date"
    ],

    16 => [
        'title' => 'Create audit log table',
        'desc'  => 'DDL example',
        'sql'   => "CREATE TABLE IF NOT EXISTS AUDIT_LOG (
                        log_id INT AUTO_INCREMENT PRIMARY KEY,
                        action_desc VARCHAR(200),
                        performed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )"
    ],

    17 => [
        'title' => 'Insert high-value customers into log',
        'desc'  => 'Insert from select',
        'sql'   => "INSERT INTO AUDIT_LOG (action_desc)
                    SELECT CONCAT('High-value customer: ', c.name, ' paid $', p.amount)
                    FROM CUSTOMER c
                    JOIN PAYMENT p ON c.customer_id = p.customer_id
                    WHERE p.amount > 50"
    ],

];

$selected = isset($_GET['q']) ? (int)$_GET['q'] : 1;

if (!isset($queries[$selected])) {
    $selected = 1;
}

$current = $queries[$selected];

$data = [];
$cols = [];
$error = '';
$message = '';

$result = $conn->query($current['sql']);

if ($result === false) {
    $error = $conn->error;
} elseif ($result === true) {
    $message = "Query executed (no results returned).";
} else {
    while ($row = $result->fetch_assoc()) {
        if (empty($cols)) {
            $cols = array_keys($row);
        }
        $data[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Queries</title>

<style>
body {
    font-family: Arial;
    background: #f4f4f4;
    padding: 20px;
}

.query-list a {
    display: inline-block;
    margin: 5px;
    padding: 6px 10px;
    background: #ddd;
    text-decoration: none;
    color: #000;
}

.query-list a.active {
    background: #333;
    color: #fff;
}

pre {
    background: #eee;
    padding: 10px;
}

table {
    border-collapse: collapse;
    margin-top: 10px;
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
    margin-top: 10px;
    padding: 8px;
    background: #e7f5e7;
}

.error {
    margin-top: 10px;
    padding: 8px;
    background: #f8d7da;
}
</style>
</head>

<body>

<h1>Advanced Queries</h1>

<div class="query-list">
<?php foreach ($queries as $i => $q): ?>
    <a href="?q=<?= $i ?>" class="<?= $selected === $i ? 'active' : '' ?>">
        Query <?= $i ?>
    </a>
<?php endforeach; ?>
</div>

<h2><?= htmlspecialchars($current['title']) ?></h2>
<p><?= htmlspecialchars($current['desc']) ?></p>

<pre><?= htmlspecialchars($current['sql']) ?></pre>

<?php if ($error): ?>
    <div class="error">Error: <?= htmlspecialchars($error) ?></div>
<?php elseif ($message): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
<?php elseif (empty($data)): ?>
    <div class="msg">No results found.</div>
<?php else: ?>

<table>
<tr>
<?php foreach ($cols as $c): ?>
    <th><?= htmlspecialchars($c) ?></th>
<?php endforeach; ?>
</tr>

<?php foreach ($data as $row): ?>
<tr>
<?php foreach ($row as $val): ?>
    <td><?= htmlspecialchars($val ?? 'NULL') ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>

</table>

<?php endif; ?>

</body>
</html>