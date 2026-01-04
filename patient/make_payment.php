<?php
session_start();
require_once '../config.php'; // DB config + STRIPE_SECRET_KEY
require_once 'C:\xampp\htdocs\dental_clinic_system\vendor\stripe\init.php'; // Stripe autoload

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['appointment_id'])) {
    die("Missing appointment ID.");
}

$appointment_id = intval($_GET['appointment_id']);
$user_id = $_SESSION['user_id'];

// Get appointment with service name and price
$stmt = $conn->prepare("
    SELECT a.id, a.patient_id, s.name AS service_name, s.price
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.id = ? LIMIT 1
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid appointment.");
}

$appointment = $result->fetch_assoc();

// Verify appointment belongs to logged-in patient
$patient_stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$patient_stmt->bind_param("i", $user_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();
$patient = $patient_result->fetch_assoc();

if ($appointment['patient_id'] != $patient['id']) {
    die("Unauthorized access.");
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$amount_in_cents = $appointment['price'] * 100; // PHP → centavos
$service_name = $appointment['service_name'];

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'php',
                'unit_amount' => $amount_in_cents,
                'product_data' => [
                    'name' => $service_name,
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost/dental_clinic_system/patient/payment_success.php?appointment_id=' . $appointment_id,
        'cancel_url' => 'http://localhost/dental_clinic_system/patient/my_appointments.php',
        'metadata' => [
            'appointment_id' => $appointment_id,
            'patient_id' => $patient['id'],
        ],
    ]);

    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    echo "Stripe Checkout error: " . $e->getMessage();
}
?>
