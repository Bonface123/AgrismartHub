<?php
require_once __DIR__ . '/config/db.php';

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Password Reset</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/login.html' class='text-blue-700 underline mt-4 inline-block'>Back to Login</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (!$email || !$token || !$password || !$password2) {
        http_response_code(400);
        showMessage('danger', 'Missing required fields.');
    }
    if ($password !== $password2) {
        http_response_code(400);
        showMessage('danger', 'Passwords do not match.');
    }
    $stmt = $pdo->prepare('SELECT id, otp_code, otp_expires FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || $user['otp_code'] !== $token || strtotime($user['otp_expires']) < time()) {
        http_response_code(401);
        showMessage('danger', 'Invalid or expired reset link. Please request a new password reset.');
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL WHERE id = ?');
    $stmt->execute([$hashed, $user['id']]);
    showMessage('success', 'Your password has been reset successfully! You can now log in.');
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
