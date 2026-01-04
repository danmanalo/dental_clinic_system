<?php
// Include PHPMailer classes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');
require 'db.php';
$conn->query("SET time_zone = '+08:00'"); 
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in the patients table
    $stmt = $conn->prepare("SELECT users.id FROM users 
                            JOIN patients ON users.id = patients.user_id 
                            WHERE patients.email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();

        // Generate reset token and expiry
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Save token in database
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->bind_param("ssi", $token, $expires, $user_id);
        $update->execute();

        // Construct reset link
        $reset_link = "http://localhost/dental_clinic_system/reset_password.php?token=$token";

        // Send the reset email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'danwn09@gmail.com'; // 🔁 Replace with your Gmail
            $mail->Password = 'esah wdih syhw fswl';    // 🔁 Use Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email@gmail.com', 'Tooth Talks Dental Clinic');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "
                <p>Hello,</p>
                <p>We received a request to reset your password. Click the link below to choose a new one:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>This link will expire in 30 minutes.</p>
                <p>Tooth Talks Dental Clinic</p>
            ";

            $mail->send();
            echo "<p style='color: green;'>✅ A reset link has been sent to your email.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Email could not be sent. Error: {$mail->ErrorInfo}</p>";
        }

    } else {
        echo "<p style='color: red;'>❌ No account found with that email.</p>";
    }
}
?>
