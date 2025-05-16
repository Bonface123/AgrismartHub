<?php
session_start();
require_once __DIR__ . '/config/db.php';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'view') {
    // View cart
    $cart = $_SESSION['cart'];
    $items = [];
    $grand_total = 0;
    if ($cart) {
        $ids = implode(',', array_map('intval', array_keys($cart)));
        $stmt = $pdo->query("SELECT id, name, price FROM products WHERE id IN ($ids)");
        $products = [];
        foreach ($stmt as $row) {
            $products[$row['id']] = $row;
        }
        foreach ($cart as $pid => $qty) {
            if (isset($products[$pid])) {
                $total = $products[$pid]['price'] * $qty;
                $grand_total += $total;
                $items[] = [
                    'id' => $pid,
                    'name' => $products[$pid]['name'],
                    'price' => $products[$pid]['price'],
                    'quantity' => $qty,
                    'total' => $total
                ];
            }
        }
    }
    echo json_encode(['items'=>$items, 'grand_total'=>$grand_total]);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);
if ($action === 'add' && $product_id && $quantity > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    echo json_encode(['success'=>true, 'message'=>'Added to cart!']);
    exit;
}
if ($action === 'remove' && $product_id) {
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success'=>true, 'message'=>'Removed from cart.']);
    exit;
}
echo json_encode(['success'=>false, 'message'=>'Invalid request.']);
