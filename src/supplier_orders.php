<?php
require_once __DIR__ . '/config/db.php';
session_start();

header('Content-Type: application/json');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Forbidden']);
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all orders that include any product from this supplier
    $sql = 'SELECT DISTINCT o.* FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.supplier_id = ?
            ORDER BY o.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    $result = [];
    foreach ($orders as $order) {
        // Get buyer info
        $buyerStmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $buyerStmt->execute([$order['user_id']]);
        $buyer = $buyerStmt->fetch();
        // Get order items for this supplier
        $itemStmt = $pdo->prepare('SELECT oi.*, p.name, p.brand FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.supplier_id = ?');
        $itemStmt->execute([$order['id'], $user_id]);
        $items = $itemStmt->fetchAll();
        // Always include pickup_date in the order object
        $order['pickup_date'] = $order['pickup_date'] ?? null;
        $result[] = [
            'order' => $order,
            'buyer' => $buyer,
            'items' => $items
        ];
    }
    echo json_encode(['success'=>true, 'orders'=>$result]);
    exit;
}
// POST: Update order status or pickup date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = intval($_POST['order_id'] ?? 0);
    if (!$order_id) {
        echo json_encode(['success'=>false, 'message'=>'Invalid order id.']);
        exit;
    }
    // Only allow status changes for orders with this supplier's products
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=? AND p.supplier_id=?');
    $checkStmt->execute([$order_id, $user_id]);
    if ($checkStmt->fetchColumn() == 0) {
        echo json_encode(['success'=>false, 'message'=>'Order not found for your products.']);
        exit;
    }
    if ($action === 'update_pickup_date') {
        $pickup_date = $_POST['pickup_date'] ?? '';
        if (!$pickup_date) {
            echo json_encode(['success'=>false, 'message'=>'Pickup date is required.']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE orders SET pickup_date=? WHERE id=?');
        $stmt->execute([$pickup_date, $order_id]);
        echo json_encode(['success'=>true, 'message'=>'Pickup date updated.']);
        exit;
    }
    // Accept/Decline/Update status
    $status = '';
    if ($action === 'accept') $status = 'processing';
    elseif ($action === 'decline') $status = 'cancelled';
    elseif ($action === 'ready') $status = 'ready_for_pickup';
    elseif ($action === 'out_for_delivery') $status = 'out_for_delivery';
    elseif ($action === 'delivered') $status = 'delivered';
    elseif ($action === 'cancel') $status = 'cancelled';
    else {
        echo json_encode(['success'=>false, 'message'=>'Unknown action.']);
        exit;
    }
    // Update status for this order (could be per supplier in future)
    $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
    $stmt->execute([$status, $order_id]);
    echo json_encode(['success'=>true, 'message'=>'Order status updated.']);
    exit;
}
echo json_encode(['success'=>false, 'message'=>'Invalid request.']);

// POST: Update order status (accept, decline, mark as delivered, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = intval($_POST['order_id'] ?? 0);
    if (!$order_id) {
        echo json_encode(['success'=>false, 'message'=>'Invalid order id.']);
        exit;
    }
    // Only allow status changes for orders with this supplier's products
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id=? AND p.supplier_id=?');
    $checkStmt->execute([$order_id, $user_id]);
    if ($checkStmt->fetchColumn() == 0) {
        echo json_encode(['success'=>false, 'message'=>'Order not found for your products.']);
        exit;
    }
    // Accept/Decline/Update status
    $status = '';
    if ($action === 'accept') $status = 'processing';
    elseif ($action === 'decline') $status = 'cancelled';
    elseif ($action === 'ready') $status = 'ready_for_pickup';
    elseif ($action === 'out_for_delivery') $status = 'out_for_delivery';
    elseif ($action === 'delivered') $status = 'delivered';
    elseif ($action === 'cancel') $status = 'cancelled';
    else {
        echo json_encode(['success'=>false, 'message'=>'Unknown action.']);
        exit;
    }
    // Update status for this order (could be per supplier in future)
    $stmt = $pdo->prepare('UPDATE orders SET status=? WHERE id=?');
    $stmt->execute([$status, $order_id]);
    echo json_encode(['success'=>true, 'message'=>'Order status updated.']);
    exit;
}
echo json_encode(['success'=>false, 'message'=>'Invalid request.']);
