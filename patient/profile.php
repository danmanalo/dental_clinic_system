<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT u.username, p.full_name, p.email, p.phone FROM users u JOIN patients p ON u.id = p.user_id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

$success = "";
$error = "";
$password_success = "";
$password_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($full_name === "" || $phone === "") {
        $error = "Full name and phone are required.";
    } else {
        $update = $conn->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $update->bind_param("sssi", $full_name, $email, $phone, $user_id);
        if ($update->execute()) {
            $success = "Profile updated successfully.";
            $patient['full_name'] = $full_name;
            $patient['email'] = $email;
            $patient['phone'] = $phone;
        } else {
            $error = "Failed to update profile.";
        }
        $update->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New password and confirmation do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($current_password, $hashed_password)) {
            $password_error = "Current password is incorrect.";
        } else {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_hashed, $user_id);
            if ($update->execute()) {
                $password_success = "Password changed successfully.";
            } else {
                $password_error = "Failed to change password.";
            }
            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | Tooth Talks</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background-color: #f4f9fc; }
    .sidebar { background-color: #e3f2fd; min-height: 100vh; padding: 30px 20px; text-align: center; border-right: 1px solid #cfd8dc; }
    .sidebar img { max-width: 100px; margin-bottom: 15px; }
    .sidebar h2 { font-size: 22px; font-weight: 700; color: #0d47a1; margin-bottom: 30px; }
    .sidebar a { color: #0d47a1; display: block; margin: 14px 0; font-weight: 500; text-decoration: none; transition: 0.2s; }
    .sidebar a.active, .sidebar a:hover { color: #1976d2; font-weight: 600; }
    .logout-btn { color: #d32f2f; }
    .form-section { background-color: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .form-control { border-radius: 10px; }
    .btn-primary { background-color: #1976d2; border-color: #1976d2; }
    h2 { color: #0d47a1; }
    @media (max-width:767px){
        .sidebar { border-right: none; border-bottom:1px solid #cfd8dc; padding-bottom:20px; }
    }
</style>
</head>
<body>
<div class="container-fluid">
<div class="row flex-nowrap">
    <nav class="col-12 col-md-3 col-lg-2 sidebar">
    <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid">
    <h2>Tooth Talks</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="book_appointment.php">Book Appointment</a>
    <a href="my_appointments.php">My Appointments</a>
    <a href="services.php">Services</a>
    <a href="profile.php" class="active">Profile</a>
    <a href="logout.php" class="logout-btn">Logout</a>
    </nav>

    <main class="col px-4 py-4">
    <div class="form-section">
        <h2><i class="bi bi-person-circle me-2"></i>My Profile</h2>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>

        <form method="POST" class="row g-3">
            <input type="hidden" name="update_profile" value="1">
            <div class="col-md-6">
                <label class="form-label">Username (read-only)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($patient['username'], ENT_QUOTES) ?>" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($patient['full_name'], ENT_QUOTES) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email (optional)</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($patient['email'], ENT_QUOTES) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone'], ENT_QUOTES) ?>" required>
            </div>
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-primary px-4">Update Profile</button>
            </div>
        </form>
    </div>

    <div class="form-section mt-5">
        <h2><i class="bi bi-lock me-2"></i>Change Password</h2>
        <?php if ($password_success): ?><div class="alert alert-success"><?= htmlspecialchars($password_success, ENT_QUOTES) ?></div><?php endif; ?>
        <?php if ($password_error): ?><div class="alert alert-danger"><?= htmlspecialchars($password_error, ENT_QUOTES) ?></div><?php endif; ?>

        <form method="POST" class="row g-3">
            <input type="hidden" name="change_password" value="1">
            <div class="col-md-6">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="col-12 mt-3">
                <button type="submit" class="btn btn-warning px-4">Change Password</button>
            </div>
        </form>
    </div>

    </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
