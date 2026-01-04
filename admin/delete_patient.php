<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: patients.php");
    exit();
}

$patient_id = $_GET['id'];

// First, get the user_id linked to this patient
$stmt = $conn->prepare("SELECT user_id FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: patients.php");
    exit();
}

$user_id = $result->fetch_assoc()['user_id'];

// Delete from both patients and users table
$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt1->bind_param("i", $patient_id);
    $stmt1->execute();

    $stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    echo "Failed to delete patient.";
    exit();
}

header("Location: patients.php");
exit();
