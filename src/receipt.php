<?php
// Generate a simple receipt for an order (for download or display)
require_once __DIR__ . '/config/db.php';
session_start();
header('Content-Type: text/html');
$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id || !isset($_SESSION['user_id'])) {
    echo 'Invalid request.';
    exit;
}
$stmt = $pdo->prepare('SELECT o.*, u.name as customer_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?');
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) {
    echo 'Order not found.';
    exit;
}
$stmt = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $order_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="max-w-lg mx-auto bg-white shadow rounded p-8 mt-10">
        <h2 class="text-2xl font-bold mb-2">Order Receipt #<?= $order_id ?></h2>
        <div class="mb-2"><span class="font-semibold">Customer:</span> <?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['email']) ?>)</div>
        <div class="mb-2"><span class="font-semibold">Date:</span> <?= htmlspecialchars($order['created_at']) ?></div>
        <div class="mb-2"><span class="font-semibold">Delivery/Pickup:</span> <?= $order['delivery_option'] === 'delivery' ? 'Home Delivery' : 'Pickup from collection point' ?></div>
        <div class="mb-4"><span class="font-semibold">Status:</span> <?= htmlspecialchars($order['status']) ?></div>
        <table class="w-full mb-4 border">
            <thead><tr class="bg-gray-100"><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>KSh <?= number_format($item['price'],2) ?></td>
                <td>KSh <?= number_format($item['price']*$item['quantity'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-right font-bold mb-4">Grand Total: KSh <?= number_format($order['total'],2) ?></div>
        <a href="#" onclick="window.print();return false;" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Print/Download</a>
        <a href="../public/farmer-dashboard.html" class="ml-4 text-green-700 underline">Back to Dashboard</a>
    </div>
</body>
</html>
