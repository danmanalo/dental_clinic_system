<?php
session_start();
date_default_timezone_set('Asia/Manila');
require 'db.php';
$conn->query("SET time_zone = '+08:00'"); 

/* Redirect if already logged in */
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin'
        ? 'admin/dashboard.php'
        : 'patient/dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login']);
    $password = $_POST['password'];

    /* ----- 1️⃣  Look up by USERNAME first (works for admins & patients) ----- */
    $stmt = $conn->prepare(
        "SELECT id, username, password, role
         FROM users
         WHERE username = ?"
    );
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();

    /* If no row, 2️⃣  look up by patient EMAIL */
    if ($stmt->num_rows !== 1) {
        $stmt->close();
        $stmt = $conn->prepare(
            "SELECT u.id, u.username, u.password, u.role
             FROM users u
             JOIN patients p ON u.id = p.user_id
             WHERE p.email = ?"
        );
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $stmt->store_result();
    }

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $username, $hashed_pw, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_pw)) {
            /* Success: set session & redirect */
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            header("Location: " . ($role === 'admin'
                ? 'admin/dashboard.php'
                : 'patient/dashboard.php'));
            exit();
        }
    }

    /* Anything else is a failure */
    $_SESSION['login_error'] = "Invalid username/email or password.";
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tooth Talks | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <img src="logo.png" alt="Tooth Talks Dental Clinic" class="logo">
        <h2>Welcome to Tooth Talks</h2>
        <p>Please log in to your account</p>

        <?php if (isset($_SESSION['login_error'])): ?>
            <p class="error-msg" style="color:red;">
                <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text"     name="login"    placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password"          required>
            <input type="submit"   value="Login"   class="btn-login">
        </form>

        <div class="login-links">
            <p>Don’t have an account? <a href="register.php">Register here</a></p>
            <p><a href="forgot_password.php">Forgot your password?</a></p>
        </div>
    </div>
</div>
</body>
</html>
