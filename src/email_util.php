<?php
// Email utility for SendGrid/Mailgun integration
function sendOTPEmail($to, $otp) {
    // === SENDGRID IMPLEMENTATION ===
    $apiKey = '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'; // User's real API key
    $subject = 'Your AgriSmart Hub OTP';
    $body = "Your OTP is: $otp";
    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ];
    $data = json_encode([
        'personalizations' => [[
            'to' => [['email' => $to]],
            'subject' => $subject
        ]],
        'from' => ['email' => 'oogeku@kabarak.ac.ke', 'name' => 'AgriSmart Hub'],
        'reply_to' => ['email' => 'ondusobonface9@gmail.com'],
        'content' => [['type' => 'text/plain', 'value' => $body]]
    ]);
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status === 202;
}
