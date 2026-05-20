<?php
/**
 * HEP Academy Chatbot – Anthropic API Proxy
 * Place this file on your Moodle server at:
 * /moodle/local/hepchatbot/api_proxy.php
 * 
 * Set environment variable: HEP_ANTHROPIC_KEY=sk-ant-your-key
 */

// Security: Only allow from your Moodle domain
$allowed_origin = 'https://your-moodle-domain.com'; // CHANGE THIS
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Verify Moodle session (optional but recommended)
// require_once('../../config.php');
// require_login();

$api_key = getenv('HEP_ANTHROPIC_KEY');
if (!$api_key) {
    http_response_code(500);
    echo json_encode(['error' => 'API key not configured']);
    exit();
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

// Forward to Anthropic
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $api_key,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$result   = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($http_code);
echo $result;
