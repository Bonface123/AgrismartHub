<?php
require_once __DIR__ . '/config/db.php';

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>OTP Verification</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/login.html' class='text-blue-700 underline mt-4 inline-block'>Go to Login</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    if (!$email || !$otp) {
        http_response_code(400);
        showMessage('danger', 'Missing email or OTP. Please enter both fields.');
    }
    $stmt = $pdo->prepare('SELECT id, otp_code, otp_expires, is_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        showMessage('danger', 'User not found. Please check your email address.');
    }
    if ($user['is_verified']) {
        showMessage('warning', 'Account already verified. You can now log in.');
    }
    if ($user['otp_code'] !== $otp) {
        http_response_code(401);
        showMessage('danger', 'Invalid OTP. Please check the code sent to your email.');
    }
    if (strtotime($user['otp_expires']) < time()) {
        http_response_code(401);
        showMessage('danger', 'OTP has expired. Please request a new code by registering again.');
    }
    $stmt = $pdo->prepare('UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);
    showMessage('success', 'Email verified successfully! You can now log in.');
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
