<?php
require_once '../config.php';

if (isset($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);

    $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'paid' WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    echo "<h2>✅ Payment successful!</h2>";
    echo "<p><a href='my_appointments.php'>Back to Appointments</a></p>";
} else {
    echo "Invalid payment confirmation.";
}
?>
