<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get patient ID from user_id
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Patient record not found.";
    header("Location: my_appointments.php");
    exit();
}

$patient = $result->fetch_assoc();
$patient_id = $patient['id'];

// Check if appointment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: my_appointments.php");
    exit();
}

$appointment_id = $_GET['id'];

// Verify appointment belongs to this patient and is cancellable
$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND patient_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $appointment_id, $patient_id);
$stmt->execute();
$appt_result = $stmt->get_result();

if ($appt_result->num_rows === 0) {
    $_SESSION['error'] = "Appointment not found or cannot be cancelled.";
    header("Location: my_appointments.php");
    exit();
}

// Cancel the appointment and record who cancelled it
$stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled', cancelled_by = 'patient' WHERE id = ?");
$stmt->bind_param("i", $appointment_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Appointment successfully cancelled.";
} else {
    $_SESSION['error'] = "Failed to cancel appointment. Please try again.";
}

header("Location: my_appointments.php");
exit();
?>
