<?php
session_start();
require_once __DIR__ . '/config/db.php';

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Login</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/login.html' class='text-blue-700 underline mt-4 inline-block'>Back to Login</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        http_response_code(400);
        showMessage('danger', 'Missing email or password. Please enter both fields.');
    }
    try {
        $stmt = $pdo->prepare('SELECT id, role, name, password, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            http_response_code(401);
            showMessage('danger', 'Invalid credentials. Please check your email and password.');
        }
        if (!$user['is_verified']) {
            http_response_code(403);
            showMessage('warning', 'Account not verified. Please verify your account first.', "<a href='../public/verify-otp.html?email=" . urlencode($email) . "' class='text-blue-700 underline block mt-4'>Verify OTP</a>");
        }
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            showMessage('danger', 'Invalid credentials. Please check your email and password.');
        }
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        // Redirect to dashboard based on role
        if ($user['role'] === 'farmer') {
            header('Location: ../public/farmer-dashboard.html');
        } elseif ($user['role'] === 'supplier') {
            header('Location: ../public/supplier-dashboard.html');
        } else {
            header('Location: ../public/admin-dashboard.html');
        }
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        showMessage('danger', 'An error occurred during login. Please try again later.');
    }
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
