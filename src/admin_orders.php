<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/session_check.php';

// Only admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// List all orders with product and user info
$sql = 'SELECT o.*, p.name as product_name, u.name as buyer_name FROM orders o JOIN products p ON o.product_id = p.id JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC';
$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll());
