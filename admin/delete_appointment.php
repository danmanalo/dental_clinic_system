<?php
session_start();
require_once '../db.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Check if 'id' is present and is a positive integer
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: appointments.php");
    exit();
}

$appointment_id = (int)$_GET['id'];

// Check if the appointment exists before deleting
$stmt_check = $conn->prepare("SELECT id FROM appointments WHERE id = ?");
$stmt_check->bind_param("i", $appointment_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: appointments.php");
    exit();
}

// Delete the appointment
$stmt_delete = $conn->prepare("DELETE FROM appointments WHERE id = ?");
$stmt_delete->bind_param("i", $appointment_id);

if ($stmt_delete->execute()) {
    $_SESSION['success'] = "Appointment cancelled successfully.";
} else {
    $_SESSION['error'] = "Failed to cancel appointment. Please try again.";
}

$stmt_delete->close();
$stmt_check->close();
$conn->close();

// Redirect back to the appointments page
header("Location: appointments.php");
exit();
?>
