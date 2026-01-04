<?php
date_default_timezone_set('Asia/Manila');
require 'db.php';
$conn->query("SET time_zone = '+08:00'"); 
session_start();

// ✅ Set timezone to avoid mismatches with DB expiration comparison
date_default_timezone_set('Asia/Manila');

$token = $_GET['token'] ?? '';
$valid = false;
$user_id = null;

// Check if token is valid (for displaying form)
if ($token) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $valid = true;
        $stmt->bind_result($user_id);
        $stmt->fetch();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && $token) {
    $new_pw = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Re-verify token and get user ID again
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $new_pw, $user_id);
        if ($stmt->execute()) {
            echo "✅ Password reset successful! <a href='index.php'>Login here</a>";
            exit;
        } else {
            echo "❌ Error resetting password.";
        }
    } else {
        echo "❌ Invalid or expired token. Please request a new password reset.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <h2>Reset Password</h2>
        <?php if ($valid): ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter new password" required>
            <input type="submit" value="Reset Password">
        </form>
        <?php else: ?>
        <p>⚠️ Invalid or expired token.</p>
        <?php endif; ?>
    </div>
</div>
</body>
