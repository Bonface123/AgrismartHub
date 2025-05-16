<?php
require_once __DIR__ . '/config/db.php';
$id = intval($_GET['id'] ?? 0);
if (!$id) {
  echo json_encode(null);
  exit;
}
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
header('Content-Type: application/json');
echo json_encode($product);
