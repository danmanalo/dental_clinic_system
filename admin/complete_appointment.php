<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: appointments.php");
    exit();
}

$appointment_id = (int)$_GET['id'];

// Check if appointment exists and is confirmed
$stmt = $conn->prepare("SELECT status FROM appointments WHERE id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: appointments.php");
    exit();
}

$appointment = $result->fetch_assoc();
if (strtolower($appointment['status']) !== 'confirmed') {
    $_SESSION['error'] = "Only confirmed appointments can be marked as completed.";
    header("Location: appointments.php");
    exit();
}

// Update status to completed
$update_stmt = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
$update_stmt->bind_param("i", $appointment_id);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Appointment marked as completed.";
} else {
    $_SESSION['error'] = "Failed to update appointment status.";
}

header("Location: appointments.php");
exit();
