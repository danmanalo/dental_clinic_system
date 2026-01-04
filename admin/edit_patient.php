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
$success = "";
$error = "";

// Fetch current patient info and user ID
$stmt = $conn->prepare("SELECT p.full_name, p.email, p.phone, u.id AS user_id FROM patients p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if (!$patient) {
    echo "Patient not found.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = trim($_POST['new_password']);

    // Update patient details
    $stmt = $conn->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $patient_id);
    $stmt->execute();
    $stmt->close();

    // Update password if a new one is provided
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $patient['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: patients.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Patient</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h2>Edit Patient</h2>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($patient['full_name']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Email (optional)</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($patient['phone']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
        </div>
        <button type="submit" class="btn btn-success">Update</button>
        <a href="patients.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>
