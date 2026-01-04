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
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}
$patient = $result->fetch_assoc();
$patient_id = $patient['id'];

// Fetch available services
$services = $conn->query("SELECT id, name FROM services ORDER BY name ASC");

// Fetch available time slots (exclude pending or confirmed)
$timeSlots = $conn->query("
    SELECT t.id, t.slot_date, t.start_time
    FROM timeslots t
    WHERE NOT EXISTS (
        SELECT 1 FROM appointments a
        WHERE a.time_slot_id = t.id AND a.status IN ('pending', 'confirmed')
    )
    ORDER BY t.slot_date ASC, t.start_time ASC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'];
    $time_slot_id = $_POST['time_slot_id'];

    if (empty($service_id) || empty($time_slot_id)) {
        $_SESSION['error'] = "Please select both service and time slot.";
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM appointments
            WHERE time_slot_id = ? AND status IN ('pending', 'confirmed')
        ");
        $stmt->bind_param("i", $time_slot_id);
        $stmt->execute();
        $check = $stmt->get_result()->fetch_assoc();

        if ($check['count'] > 0) {
            $_SESSION['error'] = "This time slot has just been booked. Please choose another.";
        } else {
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, service_id, time_slot_id, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("iii", $patient_id, $service_id, $time_slot_id);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Appointment booked successfully!";
                header("Location: my_appointments.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to book appointment.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment | Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            transition: 0.2s;
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
            font-size: 28px;
            font-weight: 600;
            color: #0d47a1;
        }
        .form-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            max-width: 700px;
            margin: auto;
        }
        label {
            font-weight: 500;
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
            <a href="book_appointment.php" class="active">Book Appointment</a>
            <a href="my_appointments.php">My Appointments</a>
            <a href="services.php">Services</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <main class="col px-4 py-4">
            <h1 class="mb-4 text-center"><i class="bi bi-calendar-plus me-2" style="color: #0d47a1;"></i>Book an Appointment</h1>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" class="row g-4">
                    <div class="col-md-12">
                        <label for="service_id" class="form-label">Select Service</label>
                        <select name="service_id" id="service_id" class="form-select" required>
                            <option value="">-- Choose a Service --</option>
                            <?php while ($service = $services->fetch_assoc()): ?>
                                <option value="<?= $service['id'] ?>"><?= htmlspecialchars($service['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="time_slot_id" class="form-label">Select Time Slot</label>
                        <select name="time_slot_id" id="time_slot_id" class="form-select" required>
                            <option value="">-- Choose a Time Slot --</option>
                            <?php while ($slot = $timeSlots->fetch_assoc()): ?>
                                <option value="<?= $slot['id'] ?>">
                                    <?= date("F j, Y", strtotime($slot['slot_date'])) ?> — <?= date("g:i A", strtotime($slot['start_time'])) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2">Book Appointment</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
