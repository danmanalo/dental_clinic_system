<?php
require 'db.php';
$conn->query("SET time_zone = '+08:00'");
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $phone = trim($_POST['phone']);

    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Username already taken.";
    } else {
        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'patient')");
        $stmt->bind_param("ss", $username, $password);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            // Insert into patients table
            $stmt2 = $conn->prepare("INSERT INTO patients (user_id, full_name, email, phone) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isss", $user_id, $full_name, $email, $phone);
            if ($stmt2->execute()) {
                $success = "Registration successful!";
            } else {
                $error = "Error saving patient details.";
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Tooth Talks</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <img src="logo.png" class="logo">
        <h2>Create a Patient Account</h2>

        <?php if (isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p style='color: green;'>$success</p>"; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="email" name="email" placeholder="Email Address (optional)">
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="submit" value="Register">
        </form>

        <p style="margin-top:10px;">Already have an account? <a href="index.php">Login here</a></p>
    </div>
</div>
</body>
</html>
