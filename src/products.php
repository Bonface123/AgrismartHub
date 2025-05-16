<?php
require_once __DIR__ . '/config/db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// GET: List or get single product
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['mine']) && $role === 'supplier') {
        // Supplier's own products
        $stmt = $pdo->prepare('SELECT * FROM products WHERE supplier_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id]);
        echo json_encode(['success'=>true, 'products'=>$stmt->fetchAll()]);
        exit;
    }
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$_GET['id']]);
        $product = $stmt->fetch();
        echo json_encode(['success'=>true, 'product'=>$product]);
        exit;
    }
    // List all (admin or public)
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $sql = 'SELECT * FROM products WHERE 1';
    $params = [];
    if ($search) {
      $sql .= ' AND (name LIKE ? OR brand LIKE ? OR description LIKE ?)';
      $params[] = "%$search%";
      $params[] = "%$search%";
      $params[] = "%$search%";
    }
    if ($category) {
      $sql .= ' AND category = ?';
      $params[] = $category;
    }
    $sql .= ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    echo json_encode(['success'=>true, 'products'=>$products]);
    exit;
}
// POST: Add, update, delete product (supplier only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user_id || $role !== 'supplier') {
        http_response_code(403);
        echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        if (!$name || !$category || !$price || !$stock) {
            echo json_encode(['success'=>false,'message'=>'Please fill all required fields.']);
            exit;
        }
        $stmt = $pdo->prepare('INSERT INTO products (supplier_id, name, category, brand, description, price, stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$user_id, $name, $category, $brand, $description, $price, $stock]);
        echo json_encode(['success'=>true, 'message'=>'Product added successfully.']);
        exit;
    }
    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        if (!$id || !$name || !$category || !$price || !$stock) {
            echo json_encode(['success'=>false,'message'=>'Please fill all required fields.']);
            exit;
        }
        // Only allow supplier to update their own product
        $stmt = $pdo->prepare('UPDATE products SET name=?, category=?, brand=?, description=?, price=?, stock=? WHERE id=? AND supplier_id=?');
        $stmt->execute([$name, $category, $brand, $description, $price, $stock, $id, $user_id]);
        echo json_encode(['success'=>true, 'message'=>'Product updated successfully.']);
        exit;
    }
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success'=>false,'message'=>'Invalid product id.']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=? AND supplier_id=?');
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success'=>true, 'message'=>'Product deleted.']);
        exit;
    }
    echo json_encode(['success'=>false, 'message'=>'Unknown action.']);
    exit;
}
echo json_encode(['success'=>false, 'message'=>'Invalid request.']);
