<?php
// Remove ANY whitespace before this line - check with hex editor if needed
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['service'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
    exit();
}

$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8');
$company = isset($data['company']) ? htmlspecialchars(trim($data['company']), ENT_QUOTES, 'UTF-8') : '';
$service = htmlspecialchars(trim($data['service']), ENT_QUOTES, 'UTF-8');
$message = isset($data['message']) ? htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8') : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format'], JSON_UNESCAPED_UNICODE);
    exit();
}

$to = 'sales@gstsaudi.com';
$subject = 'New Consultation Request - ' . $service;

$emailBody = "New Consultation Request\n\n";
$emailBody .= "Name: $name\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Phone: $phone\n";
$emailBody .= "Company: " . ($company ?: 'Not provided') . "\n";
$emailBody .= "Service Interest: $service\n";
$emailBody .= "Message:\n" . ($message ?: 'No additional message provided') . "\n\n";
$emailBody .= "---\n";
$emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

$headers = "From: sales@gstsaudi.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mailSent = @mail($to, $subject, $emailBody, $headers);

ob_end_clean();

if ($mailSent) {
    $autoReplySubject = 'Thank you for contacting GST International';
    $autoReplyBody = "Dear $name,\n\n";
    $autoReplyBody .= "Thank you for your consultation request. We have received your inquiry regarding $service.\n\n";
    $autoReplyBody .= "Our team will review your request and get back to you within 24-48 hours.\n\n";
    $autoReplyBody .= "Best regards,\n";
    $autoReplyBody .= "GST International Team\n";
    $autoReplyBody .= "sales@gstsaudi.com\n";
    $autoReplyBody .= "+966 11 488 3087";
    
    $autoReplyHeaders = "From: sales@gstsaudi.com\r\n";
    $autoReplyHeaders .= "Reply-To: sales@gstsaudi.com\r\n";
    $autoReplyHeaders .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $autoReplyHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail($email, $autoReplySubject, $autoReplyBody, $autoReplyHeaders);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Email sent successfully'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email'], JSON_UNESCAPED_UNICODE);
}
exit();