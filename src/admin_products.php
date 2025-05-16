<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/session_check.php';

// Only admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all products (optionally filter by status)
    $status = $_GET['status'] ?? '';
    $sql = 'SELECT p.*, u.name as supplier_name FROM products p LEFT JOIN users u ON p.supplier_id = u.id';
    $params = [];
    if ($status) {
        $sql .= ' WHERE p.status = ?';
        $params[] = $status;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $product_id = intval($data['product_id'] ?? 0);
    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE products SET status = ? WHERE id = ?');
        $stmt->execute(['approved', $product_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'reject') {
        $stmt = $pdo->prepare('UPDATE products SET status = ? WHERE id = ?');
        $stmt->execute(['rejected', $product_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$product_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
