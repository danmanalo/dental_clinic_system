<?php
session_start();
date_default_timezone_set('Asia/Manila');
require 'db.php';
$conn->query("SET time_zone = '+08:00'"); 


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $user, $hashed_pw, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_pw)) {
            // Login successful
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $role;

            if ($role === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: patient/dashboard.php");
            }
            exit();
        } else {
            // Wrong password
            $_SESSION['login_error'] = "Incorrect password.";
            header("Location: index.php");
            exit();
        }
    } else {
        // Username not found
        $_SESSION['login_error'] = "Account not found.";
        header("Location: index.php");
        exit();
    }
}
?>
