<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get patient ID
$stmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();
if ($patient_result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}
$patient = $patient_result->fetch_assoc();
$patient_id = $patient['id'];

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $new_slot_id = $_POST['new_slot'];
    $reason = $_POST['reason'] ?? null;

    // Update the appointment's time slot and set status to 'pending'
    $update_stmt = $conn->prepare("UPDATE appointments SET time_slot_id = ?, status = 'pending', reschedule_reason = ? WHERE id = ? AND patient_id = ?");
    $update_stmt->bind_param("ssii", $new_slot_id, $reason, $appointment_id, $patient_id);
    if ($update_stmt->execute()) {
        header("Location: my_appointments.php?reschedule=success");
        exit();
    } else {
        $error = "Failed to reschedule appointment.";
    }
}

// Get appointment ID from query
if (!isset($_GET['id'])) {
    echo "No appointment ID provided.";
    exit();
}
$appointment_id = $_GET['id'];

// Get current appointment info
$appt_stmt = $conn->prepare("
    SELECT a.id, a.time_slot_id, s.name AS service_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.id = ? AND a.patient_id = ?
");
$appt_stmt->bind_param("ii", $appointment_id, $patient_id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
if ($appt_result->num_rows === 0) {
    echo "Appointment not found.";
    exit();
}
$appointment = $appt_result->fetch_assoc();

// Get available time slots that are not already booked
$slots_stmt = $conn->prepare("
    SELECT t.id, t.slot_date, t.start_time
    FROM timeslots t
    WHERE t.id NOT IN (
        SELECT time_slot_id FROM appointments WHERE status IN ('pending', 'confirmed') AND id != ?
    ) AND t.slot_date >= CURDATE()
    ORDER BY t.slot_date, t.start_time
");
$slots_stmt->bind_param("i", $appointment_id);
$slots_stmt->execute();
$slots_result = $slots_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reschedule Appointment | Tooth Talks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f9fc;
        }
        .sidebar {
            background-color: #e3f2fd;
            min-height: 100vh;
            padding: 30px 20px;
            text-align: center;
            border-right: 1px solid #cfd8dc;
        }
        .sidebar img {
            max-width: 100px;
            margin-bottom: 15px;
        }
        .sidebar h2 {
            font-size: 22px;
            font-weight: 700;
            color: #0d47a1;
            margin-bottom: 30px;
        }
        .sidebar a {
            color: #0d47a1;
            display: block;
            margin: 14px 0;
            font-weight: 500;
            text-decoration: none;
        }
        .sidebar a.active,
        .sidebar a:hover {
            color: #1976d2;
            font-weight: 600;
        }
        .logout-btn {
            color: #d32f2f;
        }
        h1 {
            color: #0d47a1;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Tooth Talks Logo">
            <h2>Tooth Talks</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="book_appointment.php">Book Appointment</a>
            <a href="my_appointments.php" class="active">My Appointments</a>
            <a href="services.php">Services</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <main class="col px-4 py-4">
            <h1 class="mb-4"><i class="bi bi-arrow-repeat me-2"></i>Reschedule Appointment</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Service</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($appointment['service_name']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="new_slot" class="form-label">Select New Time Slot</label>
                    <select name="new_slot" id="new_slot" class="form-select" required>
                        <option value="" disabled selected>Choose a time slot</option>
                        <?php while ($slot = $slots_result->fetch_assoc()): ?>
                            <?php
                            $datetime = date("F j, Y", strtotime($slot['slot_date'])) . " — " . date("g:i A", strtotime($slot['start_time']));
                            ?>
                            <option value="<?= $slot['id'] ?>"><?= $datetime ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for Rescheduling (optional)</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Submit Reschedule Request</button>
                <a href="my_appointments.php" class="btn btn-secondary">Cancel</a>
            </form>
        </main>
    </div>
</div>
</body>
</html>
