<?php
session_start();
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Server error: ' . $e->getMessage()]);
    exit;
});
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Database connection failed.']);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'farmer') {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
    exit;
}
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (($input['action'] ?? '') === 'cancel' && isset($input['order_id'])) {
        $order_id = intval($input['order_id']);
        // Only allow cancelling if order belongs to user and is pending
        $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();
        if ($order && $order['status'] === 'pending') {
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute(['cancelled', $order_id]);
            echo json_encode(['success'=>true, 'message'=>'Order cancelled.']);
        } else {
            echo json_encode(['success'=>false, 'message'=>'Cannot cancel this order.']);
        }
        exit;
    }
    echo json_encode(['success'=>false, 'message'=>'Invalid request.']);
    exit;
}
try {
    $sql = "SELECT o.id, o.status, o.total, o.payment_status, o.delivery_option, o.created_at,
        (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id) as items
        FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    echo json_encode(['success'=>true, 'orders'=>$orders]);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'delivery_option') !== false) {
        echo json_encode(['success'=>false, 'message'=>'Database schema is missing the delivery_option column in orders. Please update your schema.']);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error: ' . $e->getMessage()]);
    }
    exit;
}
