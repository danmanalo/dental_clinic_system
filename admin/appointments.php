<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$search = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare(
        "SELECT 
            a.id, 
            p.full_name, 
            s.name AS service_name,
            ts.slot_date,
            ts.start_time,
            ts.end_time,
            a.status,
            a.reschedule_reason
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN services s ON a.service_id = s.id
        JOIN timeslots ts ON a.time_slot_id = ts.id
        WHERE p.full_name LIKE ? OR s.name LIKE ?
        ORDER BY ts.slot_date, ts.start_time"
    );
    $param = "%$search%";
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT 
                a.id, 
                p.full_name, 
                s.name AS service_name,
                ts.slot_date,
                ts.start_time,
                ts.end_time,
                a.status,
                a.reschedule_reason
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN services s ON a.service_id = s.id
            JOIN timeslots ts ON a.time_slot_id = ts.id
            ORDER BY ts.slot_date, ts.start_time";
    $result = mysqli_query($conn, $query);
}

function statusBadge($status) {
    switch (strtolower($status)) {
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'confirmed':
            return '<span class="badge bg-primary">Confirmed</span>';
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Appointments | Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f9fc;
        }
        .sidebar {
            background-color: #e3f2fd;
            min-height: 100vh;
            padding: 30px 20px;
            color: #0d47a1;
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
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn-sm {
            margin-bottom: 4px;
        }
        .table th,
        .table td {
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid" />
            <h2>Tooth Talks</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="patients.php">Manage Patients</a>
            <a href="appointments.php" class="active">Appointments</a>
            <a href="timeslots.php">Time Slots</a>
            <a href="services.php">Services</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <main class="col px-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-3 mb-md-0">
                    <i class="bi bi-calendar-check-fill me-2" style="color: #0d47a1;"></i>Appointments
                </h1>
                <form method="GET" class="search-form">
                    <input class="form-control" type="search" name="search" placeholder="Search by patient/service..." value="<?= htmlspecialchars($search) ?>" />
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Search</button>
                        <?php if (!empty($search)) : ?>
                            <a href="appointments.php" class="btn btn-secondary">View All</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Reschedule Reason</th>
                            <th style="min-width: 230px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) : ?>
                            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                <?php $statusLower = strtolower($row['status']); ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                                    <td><?= htmlspecialchars(date("F j, Y", strtotime($row['slot_date']))) ?></td>
                                    <td><?= htmlspecialchars(date("g:i A", strtotime($row['start_time']))) ?> - <?= htmlspecialchars(date("g:i A", strtotime($row['end_time']))) ?></td>
                                    <td>
                                        <?= statusBadge($row['status']) ?>
                                        <?php if (!empty($row['reschedule_reason'])): ?>
                                            <i class="bi bi-arrow-repeat text-info ms-1" data-bs-toggle="tooltip" title="Rescheduled appointment"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star text-warning ms-1" data-bs-toggle="tooltip" title="New appointment"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['reschedule_reason'] ?? '-') ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php if ($statusLower === 'pending') : ?>
                                                <a href="confirm_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('Confirm this appointment?')">Confirm</a>
                                                <a href="reschedule_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">Reschedule</a>
                                                <a href="delete_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment? This action cannot be undone.')">Cancel</a>
                                            <?php elseif ($statusLower === 'confirmed') : ?>
                                                <a href="complete_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark this appointment as completed?')">Complete</a>
                                                <a href="reschedule_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-secondary">Reschedule</a>
                                                <a href="delete_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment? This action cannot be undone.')">Cancel</a>
                                            <?php else: ?>
                                                <a href="delete_appointment.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment? This action cannot be undone.')">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center">No appointments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
});
</script>
</body>
</html>
