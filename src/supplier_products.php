<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/session_check.php';

// Only allow suppliers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all products for this supplier
    $stmt = $pdo->prepare('SELECT * FROM products WHERE supplier_id = ?');
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $category = trim($data['category'] ?? '');
    $description = trim($data['description'] ?? '');
    $brand = trim($data['brand'] ?? '');
    if (!$name || !$price || !$category) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO products (name, price, category, description, brand, supplier_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $price, $category, $description, $brand, $user_id, 'pending']);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $price = floatval($data['price'] ?? 0);
    $category = trim($data['category'] ?? '');
    $description = trim($data['description'] ?? '');
    $brand = trim($data['brand'] ?? '');
    if (!$id || !$name || !$price || !$category) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE products SET name=?, price=?, category=?, description=?, brand=?, status=? WHERE id=? AND supplier_id=?');
    $stmt->execute([$name, $price, $category, $description, $brand, 'pending', $id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing product id']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM products WHERE id=? AND supplier_id=?');
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
