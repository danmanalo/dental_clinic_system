<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tooth_talks'); // ⚠️ Replace with your actual DB name

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stripe Test API Keys — Replace with your Stripe test keys from https://dashboard.stripe.com/test/apikeys
define('STRIPE_SECRET_KEY', 'sk_test_51RZsav06zY3sfvAjNKgfMURFuM0Wjo7CHQcPGik263CaLWmFLbZcuLcEeIpyLKWzwmMbOgEngyOAjjrpfLnbA12P00ls5NtQTX');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51RZsav06zY3sfvAjcjTHwdhltS8lavA2yZpnVdV7RbJiARqOtBsQu7082qw1dXr3uYZL4DpsEZ6A9kUEyh5vw6fW003Lh1LfqC');
?>

