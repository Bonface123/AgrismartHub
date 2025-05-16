<?php
session_start();
require_once __DIR__ . '/config/db.php';

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Order Placement</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/products.html' class='text-blue-700 underline mt-4 inline-block'>Back to Products</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        showMessage('danger', 'You must be logged in to place an order.');
    }
    require_once __DIR__ . '/cart.php';
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart || count($cart) === 0) {
        http_response_code(400);
        showMessage('danger', 'Your cart is empty. Please add items before ordering.');
    }
    // Calculate total
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT id, price, stock FROM products WHERE id IN ($ids)");
    $products = [];
    foreach ($stmt as $row) {
        $products[$row['id']] = $row;
    }
    $total = 0;
    foreach ($cart as $pid => $qty) {
        if (!isset($products[$pid]) || $products[$pid]['stock'] < $qty) {
            showMessage('danger', 'One or more items are out of stock or not available.');
        }
        $total += $products[$pid]['price'] * $qty;
    }
    // Insert order
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, status, total, payment_status, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$_SESSION['user_id'], 'pending', $total, 'pending']);
    $order_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    foreach ($cart as $pid => $qty) {
        $stmt->execute([$order_id, $pid, $qty, $products[$pid]['price']]);
    }
    // Optionally, clear cart
    unset($_SESSION['cart']);
    showMessage('success', 'Order placed successfully! You will receive updates via email.');
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
