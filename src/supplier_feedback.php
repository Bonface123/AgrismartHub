<?php
// Supplier feedback API: fetch reviews for all products by this supplier
session_start();
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supplier') {
    http_response_code(403);
    echo json_encode(['success'=>false, 'message'=>'Forbidden']);
    exit;
}
$user_id = $_SESSION['user_id'];
// Get all products for this supplier
$stmt = $pdo->prepare('SELECT id, name FROM products WHERE supplier_id = ?');
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();
$feedback = [];
foreach ($products as $product) {
    $reviewsStmt = $pdo->prepare('SELECT r.rating, r.review, r.created_at, u.name as reviewer FROM product_reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC LIMIT 5');
    $reviewsStmt->execute([$product['id']]);
    $reviews = $reviewsStmt->fetchAll();
    if ($reviews) {
        $avgStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ?');
        $avgStmt->execute([$product['id']]);
        $summary = $avgStmt->fetch();
        $feedback[] = [
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'avg_rating' => round($summary['avg_rating'],2),
            'total_reviews' => $summary['total_reviews'],
            'reviews' => $reviews
        ];
    }
}
echo json_encode(['success'=>true, 'feedback'=>$feedback]);
