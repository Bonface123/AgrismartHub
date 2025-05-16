<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/email_util.php';

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Password Reset</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/login.html' class='text-blue-700 underline mt-4 inline-block'>Back to Login</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        http_response_code(400);
        showMessage('danger', 'Please enter your email address.');
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(404);
        showMessage('danger', 'No account found with that email address.');
    }
    // Generate reset token
    $token = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
    $stmt = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?');
    $stmt->execute([$token, $expires, $user['id']]);
    $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset-password.html?email=$email&token=$token";
    $sent = sendOTPEmail($email, "To reset your password, click: $resetLink");
    if ($sent) {
        showMessage('success', 'A password reset link has been sent to your email. Please check your inbox.');
    } else {
        showMessage('danger', 'Failed to send password reset email. Please try again later.');
    }
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
