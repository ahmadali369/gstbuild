<?php
// CRITICAL: No whitespace or output before this line!

// Turn off all output buffering and errors that might corrupt JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// CORS headers
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get and decode JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if JSON is valid
if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['service'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Sanitize inputs
$name = htmlspecialchars(trim($data['name']), ENT_QUOTES, 'UTF-8');
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$phone = htmlspecialchars(trim($data['phone']), ENT_QUOTES, 'UTF-8');
$company = isset($data['company']) ? htmlspecialchars(trim($data['company']), ENT_QUOTES, 'UTF-8') : '';
$service = htmlspecialchars(trim($data['service']), ENT_QUOTES, 'UTF-8');
$message = isset($data['message']) ? htmlspecialchars(trim($data['message']), ENT_QUOTES, 'UTF-8') : '';

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Email configuration - ONLY TO sales@gstsaudi.com
$to = 'sales@gstsaudi.com';
$subject = 'New Consultation Request - ' . $service;

// Build email body
$emailBody = "New Consultation Request\n\n";
$emailBody .= "Name: $name\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Phone: $phone\n";
$emailBody .= "Company: " . ($company ?: 'Not provided') . "\n";
$emailBody .= "Service Interest: $service\n";
$emailBody .= "Message:\n" . ($message ?: 'No additional message provided') . "\n\n";
$emailBody .= "---\n";
$emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

// Email headers - FROM sales@gstsaudi.com (not noreply)
$headers = "From: sales@gstsaudi.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Send main email
$mailSent = @mail($to, $subject, $emailBody, $headers);

// Clear any output buffer before sending JSON
ob_end_clean();

if ($mailSent) {
    // Send auto-reply to customer FROM sales@gstsaudi.com
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
    
    // Send auto-reply (don't fail if this fails)
    @mail($email, $autoReplySubject, $autoReplyBody, $autoReplyHeaders);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
}
?>


<!-- <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || empty($data['service'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
$company = filter_var($data['company'] ?? '', FILTER_SANITIZE_STRING);
$service = filter_var($data['service'], FILTER_SANITIZE_STRING);
$message = filter_var($data['message'] ?? '', FILTER_SANITIZE_STRING);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$to = 'sales@gstsaudi.com';
$subject = 'Consultation Request - ' . $service;

$emailBody = "New Consultation Request\n\n";
$emailBody .= "Name: $name\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Phone: $phone\n";
$emailBody .= "Company: " . ($company ?: 'Not provided') . "\n";
$emailBody .= "Service Interest: $service\n";
$emailBody .= "Message:\n$message\n\n";
$emailBody .= "---\n";
$emailBody .= "Submitted: " . date('Y-m-d H:i:s') . "\n";

$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$mailSent = mail($to, $subject, $emailBody, $headers);

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
    
    mail($email, $autoReplySubject, $autoReplyBody, $autoReplyHeaders);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?> -->