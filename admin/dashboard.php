<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f9fc;
            margin: 0;
            padding: 0;
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
            color: #0d47a1;
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

        .dashboard-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0d47a1;
        }

        .dashboard-header p {
            font-size: 15px;
            color: #666;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
            background-color: #ffffff;
        }

        .card h5 {
            color: #1565c0;
        }

        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #0d47a1;
        }

        .table thead {
            background-color: #e3f2fd;
        }

        .table th {
            color: #0d47a1;
        }

        @media (max-width: 768px) {
            .sidebar {
                text-align: center;
            }

            .sidebar h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row flex-nowrap">
        <!-- Sidebar -->
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid">
            <h2>Tooth Talks</h2>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="patients.php">Manage Patients</a>
            <a href="appointments.php">Appointments</a>
            <a href="timeslots.php">Time Slots</a>
            <a href="services.php">Services</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Content -->
        <main class="col px-4 py-4">
            <div class="dashboard-header mb-4 d-flex justify-content-between flex-wrap align-items-center">
                <h1>Welcome, Admin</h1>
                <p><?= date('l, F j, Y'); ?></p>
            </div>

            <!-- Stat Cards -->
            <div class="row mb-4">
                <div class="col-sm-6 col-lg-4 mb-3">
                    <div class="card text-center p-4">
                        <div class="stat-icon">🧑</div>
                        <h5>Total Patients</h5>
                        <div class="stat-value">120</div> <!-- Replace with PHP -->
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-3">
                    <div class="card text-center p-4">
                        <div class="stat-icon">📅</div>
                        <h5>Appointments Today</h5>
                        <div class="stat-value">8</div> <!-- Replace with PHP -->
                    </div>
                </div>
                <div class="col-sm-12 col-lg-4 mb-3">
                    <div class="card text-center p-4">
                        <div class="stat-icon">🕒</div>
                        <h5>Available Slots</h5>
                        <div class="stat-value">5</div> <!-- Replace with PHP -->
                    </div>
                </div>
            </div>

            <!-- Upcoming Appointments Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">📅 Upcoming Appointments</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Time</th>
                                    <th>Service</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Jane Doe</td><td>10:00 AM</td><td>Cleaning</td></tr>
                                <tr><td>John Smith</td><td>11:00 AM</td><td>Extraction</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>
</body>
</html>
