<?php
// Supplier sales analytics API
session_start();
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Forbidden']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Total sales, revenue, bestsellers, sales by month
$sqlTotal = 'SELECT COUNT(DISTINCT o.id) AS total_orders, IFNULL(SUM(oi.price * oi.quantity),0) AS total_revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.supplier_id = ? AND o.status IN ("processing","delivered","ready_for_pickup","out_for_delivery")';
$stmt = $pdo->prepare($sqlTotal);
$stmt->execute([$user_id]);
$summary = $stmt->fetch();

// Bestsellers (top 5)
$sqlBest = 'SELECT p.id, p.name, SUM(oi.quantity) AS sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON o.id = oi.order_id
    WHERE p.supplier_id = ? AND o.status IN ("processing","delivered","ready_for_pickup","out_for_delivery")
    GROUP BY p.id, p.name
    ORDER BY sold DESC
    LIMIT 5';
$stmt = $pdo->prepare($sqlBest);
$stmt->execute([$user_id]);
$bestsellers = $stmt->fetchAll();

// Sales by month (last 6 months)
$sqlMonth = 'SELECT DATE_FORMAT(o.created_at,"%Y-%m") AS month, SUM(oi.price * oi.quantity) AS revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.supplier_id = ? AND o.status IN ("processing","delivered","ready_for_pickup","out_for_delivery")
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6';
$stmt = $pdo->prepare($sqlMonth);
$stmt->execute([$user_id]);
$monthly = $stmt->fetchAll();

// Output

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'bestsellers' => $bestsellers,
    'monthly' => array_reverse($monthly), // oldest first
]);
