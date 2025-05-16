<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');
$product_id = intval($_GET['product_id'] ?? 0);
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing product_id.']);
    exit;
}
$stmt = $pdo->prepare('SELECT id, image_url FROM product_images WHERE product_id = ? ORDER BY created_at ASC');
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();
echo json_encode(['success' => true, 'images' => $images]);
