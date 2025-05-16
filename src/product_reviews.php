<?php
// Handles product review submission and retrieval
session_start();
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete review (admin/supplier)
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $review_id = intval($_POST['review_id'] ?? 0);
        if (!$review_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing review_id.']);
            exit;
        }
        // Check if user is admin or supplier of the product
        $stmt = $pdo->prepare('SELECT pr.product_id, p.supplier_id FROM product_reviews pr JOIN products p ON pr.product_id = p.id WHERE pr.id = ?');
        $stmt->execute([$review_id]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Review not found.']);
            exit;
        }
        $isAdmin = ($_SESSION['role'] === 'admin');
        $isSupplier = ($_SESSION['role'] === 'supplier' && $_SESSION['user_id'] == $row['supplier_id']);
        if (!$isAdmin && !$isSupplier) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this review.']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM product_reviews WHERE id = ?');
        $stmt->execute([$review_id]);
        echo json_encode(['success' => true, 'message' => 'Review deleted.']);
        exit;
    }
    // Add a new review
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review.']);
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');
    if (!$product_id || $rating < 1 || $rating > 5 || !$review) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }
    // Restrict: Only allow review if user purchased this product
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?');
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased.']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO product_reviews (product_id, user_id, rating, review) VALUES (?, ?, ?, ?)');
    $stmt->execute([$product_id, $user_id, $rating, $review]);
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch reviews for a product
    $product_id = intval($_GET['product_id'] ?? 0);
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing product_id.']);
        exit;
    }
    // Get average rating
    $stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM product_reviews WHERE product_id = ?');
    $stmt->execute([$product_id]);
    $summary = $stmt->fetch();
    // Get latest reviews
    $stmt = $pdo->prepare('SELECT pr.rating, pr.review, pr.created_at, u.name FROM product_reviews pr JOIN users u ON pr.user_id = u.id WHERE pr.product_id = ? ORDER BY pr.created_at DESC LIMIT 10');
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
    echo json_encode([
        'success' => true,
        'avg_rating' => round($summary['avg_rating'], 2),
        'total_reviews' => $summary['total_reviews'],
        'reviews' => $reviews
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
