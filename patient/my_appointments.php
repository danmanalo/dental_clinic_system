<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get patient info
$stmt = $conn->prepare("SELECT id, full_name FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();

if ($patient_result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}

$patient = $patient_result->fetch_assoc();
$patient_id = $patient['id'];

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.id, s.name AS service_name, s.price, t.slot_date AS date, t.start_time AS time, a.status, a.cancelled_by, a.payment_status
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN timeslots t ON a.time_slot_id = t.id
    WHERE a.patient_id = ?
    ORDER BY t.slot_date DESC, t.start_time DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments | Tooth Talks</title>
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
        .table thead {
            background-color: #e3f2fd;
        }
        .badge {
            text-transform: capitalize;
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
            <a href="my_appointments.php" class="active">My Appointments</a>
            <a href="services.php">Services</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <main class="col px-4 py-4">
            <h1 class="mb-4"><i class="bi bi-calendar-week me-2" style="color: #0d47a1;"></i>My Appointments</h1>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php elseif (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if (count($appointments) > 0): ?>
                <!-- UPCOMING APPOINTMENTS -->
                <h4 class="mb-3 mt-2">Upcoming Appointments</h4>
                <div class="table-responsive mb-5">
                    <table class="table table-bordered align-middle">
                        <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <?php if (in_array($row['status'], ['pending', 'confirmed'])): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                                    <td><?= date("g:i A", strtotime($row['time'])) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= match($row['status']) {
                                                'pending' => 'bg-warning text-dark',
                                                'confirmed' => 'bg-primary',
                                            } ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $row['payment_status'] === 'paid' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($row['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <a href="cancel_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                        <?php elseif ($row['status'] === 'confirmed'): ?>
                                            <a href="reschedule_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Reschedule</a>
                                            <?php if ($row['payment_status'] === 'unpaid'): ?>
                                                <a href="make_payment.php?appointment_id=<?= $row['id'] ?>" class="btn btn-sm btn-success mt-1">Pay Now</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- APPOINTMENT HISTORY -->
                <h4 class="mb-3">Appointment History</h4>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                        <tr>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Details</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <?php if (in_array($row['status'], ['completed', 'cancelled', 'rejected'])): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                                    <td>₱<?= number_format($row['price'], 2) ?></td>
                                    <td><?= date("F j, Y", strtotime($row['date'])) ?></td>
                                    <td><?= date("g:i A", strtotime($row['time'])) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= match($row['status']) {
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-secondary',
                                                'rejected' => 'bg-danger'
                                            } ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $row['payment_status'] === 'paid' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= ucfirst($row['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'cancelled'): ?>
                                            <small class="text-muted">
                                                Cancelled by <?= $row['cancelled_by'] === 'admin' ? 'Admin' : 'You' ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No appointments found.</p>
            <?php endif; ?>
        </main>
    </div>
</div>
</body>
</html>
