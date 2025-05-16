<?php
// Handles registration for both farmers and suppliers
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/email_util.php';

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

function showMessage($type, $message, $extra = '') {
    $color = $type === 'success' ? 'green' : ($type === 'warning' ? 'yellow' : 'red');
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>Registration</title><link href='https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css' rel='stylesheet'></head><body class='bg-gray-50 flex items-center justify-center min-h-screen'><div class='bg-white p-8 rounded shadow-md w-full max-w-md text-center'><div class='mb-4 text-$color-700 font-bold text-lg'>$message</div>$extra<a href='../public/login.html' class='text-blue-700 underline mt-4 inline-block'>Go to Login</a></div></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $farming_details = $role === 'farmer' ? trim($_POST['farming_details'] ?? '') : null;
    $business_name = $role === 'supplier' ? trim($_POST['business_name'] ?? '') : null;

    // Basic validation
    if (!$role || !$name || !$email || !$password || !$location || ($role === 'farmer' && !$farming_details) || ($role === 'supplier' && !$business_name)) {
        http_response_code(400);
        showMessage('danger', 'Missing required fields. Please fill out all fields.');
    }
    // Check if user exists
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            showMessage('danger', 'Email already registered. Please use a different email or log in.');
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $otp = generateOTP();
        $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $stmt = $pdo->prepare('INSERT INTO users (role, name, email, password, location, farming_details, business_name, otp_code, otp_expires, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([
            $role, $name, $email, $hashedPassword, $location, $farming_details, $business_name, $otp, $otpExpires
        ]);
        $sent = sendOTPEmail($email, $otp);
        if ($sent) {
            showMessage('success', 'Registration successful! Please check your email for the OTP code to verify your account.', "<a href='../public/verify-otp.html?email=" . urlencode($email) . "' class='text-green-700 underline block mt-4'>Verify OTP</a>");
        } else {
            showMessage('danger', 'Registration succeeded, but failed to send OTP email. Please contact support or try again.');
        }
    } catch (Exception $e) {
        http_response_code(500);
        showMessage('danger', 'An error occurred during registration. Please try again later.');
    }
}
http_response_code(405);
showMessage('danger', 'Method not allowed.');
