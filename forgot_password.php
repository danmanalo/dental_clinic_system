<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <img src="logo.png" alt="Tooth Talks Dental Clinic" class="logo">
        <h2>Forgot Your Password?</h2>
        <p>Enter your email address and we’ll send instructions to reset your password.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
            <p style="color: green;">✅ Reset instructions have been sent to your email.</p>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
            <p style="color: red;">❌ No account found with that email.</p>
        <?php endif; ?>

        <form method="POST" action="reset_request.php">
            <input type="email" name="email" placeholder="Your Email" required>
            <input type="submit" name="submit" value="Send Reset Link">
        </form>

        <p class="login-links"><a href="index.php">Back to Login</a></p>
    </div>
</div>
</body>
</html>
