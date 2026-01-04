<?php
require_once '../config.php';
require_once '../db.php';
require_once '../vendor/autoload.php';

use Paymongo\PaymongoClient;

$client = new PaymongoClient(PAYMONGO_SECRET_KEY);
$payload = json_decode(file_get_contents('php://input'), true);

// Verify event & update DB
if ($payload['data']['type'] === 'checkout_session') {
  $session = $payload['data']['attributes'];
  if ($session['status'] === 'paid') {
    $stmt = $conn->prepare("UPDATE appointments SET payment_status='paid' WHERE paymongo_session_id = ?");
    $stmt->bind_param("s", $session['id']);
    $stmt->execute();
  }
}

http_response_code(200);
