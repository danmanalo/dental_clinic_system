<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Add time slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $slot_date = $_POST['slot_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($slot_date && $start_time && $end_time) {
        $stmt = $conn->prepare("INSERT INTO timeslots (slot_date, start_time, end_time) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $slot_date, $start_time, $end_time);
        $stmt->execute();
    }
}

// Delete slot if it's available
if (isset($_GET['delete'])) {
    $slot_id = $_GET['delete'];

    // Check if slot is booked
    $check_stmt = $conn->prepare("SELECT status FROM timeslots WHERE id = ?");
    $check_stmt->bind_param("i", $slot_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $slot = $check_result->fetch_assoc();

    if ($slot && $slot['status'] === 'available') {
        $del_stmt = $conn->prepare("DELETE FROM timeslots WHERE id = ?");
        $del_stmt->bind_param("i", $slot_id);
        $del_stmt->execute();
    }
}

// Fetch all time slots
$result = $conn->query("SELECT * FROM timeslots ORDER BY slot_date, start_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Time Slots | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap + Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f9fc;
        }

        .sidebar {
            background-color: #e3f2fd;
            min-height: 100vh;
            padding: 30px 20px;
            border-right: 1px solid #cfd8dc;
            text-align: center;
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
            font-size: 26px;
            font-weight: 600;
            color: #0d47a1;
        }

        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .table th {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .btn-delete {
            color: red;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .sidebar {
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Logo">
            <h2>Tooth Talks</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="patients.php">Manage Patients</a>
            <a href="appointments.php">Appointments</a>
            <a href="timeslots.php" class="active">Time Slots</a>
            <a href="services.php">Services</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Content -->
        <main class="col px-4 py-4">
            <h1 class="mb-4">Manage Time Slots</h1>

            <!-- Add Slot Form -->
            <div class="card p-4 mb-4">
                <h5>Add New Time Slot</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label for="slot_date" class="form-label">Date</label>
                        <input type="date" class="form-control" name="slot_date" required>
                    </div>
                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="add_slot" class="btn btn-primary w-100">Add Slot</button>
                    </div>
                </form>
            </div>

            <!-- Time Slots Table -->
            <div class="card p-4">
                <h5 class="mb-3">All Time Slots</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['slot_date']) ?></td>
                                    <td><?= date("g:i A", strtotime($row['start_time'])) ?></td>
                                    <td><?= date("g:i A", strtotime($row['end_time'])) ?></td>
                                    <td>
                                        <?= $row['status'] === 'booked' ? '<span class="text-danger">Booked</span>' : '<span class="text-success">Available</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'available'): ?>
                                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this time slot?')" class="btn btn-sm btn-outline-danger">🗑️ Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No time slots available.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
