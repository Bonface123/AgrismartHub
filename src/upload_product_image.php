<?php
// Supplier uploads image for a product
session_start();
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only suppliers can upload images.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
if (!$product_id || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing product or image.']);
    exit;
}
// Check if supplier owns this product
$stmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND supplier_id = ?');
$stmt->execute([$product_id, $_SESSION['user_id']]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not own this product.']);
    exit;
}
// Save file
$targetDir = __DIR__ . '/../uploads/products/';
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit;
}
$filename = uniqid('img_', true) . '.' . $ext;
$targetFile = $targetDir . $filename;
if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
    exit;
}
// Save path in DB (relative path for web access)
$image_url = 'uploads/products/' . $filename;
$stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_url) VALUES (?, ?)');
$stmt->execute([$product_id, $image_url]);
echo json_encode(['success' => true, 'message' => 'Image uploaded.', 'image_url' => $image_url]);
