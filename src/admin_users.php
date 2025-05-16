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
    // List all users
    $stmt = $pdo->query('SELECT id, name, email, role, is_blocked FROM users');
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $user_id = intval($data['user_id'] ?? 0);
    if ($action === 'block') {
        $stmt = $pdo->prepare('UPDATE users SET is_blocked = 1 WHERE id = ?');
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'unblock') {
        $stmt = $pdo->prepare('UPDATE users SET is_blocked = 0 WHERE id = ?');
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'promote') {
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute(['admin', $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'demote') {
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute(['farmer', $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
