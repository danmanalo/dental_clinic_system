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
    $stmt = $conn->prepare("
        SELECT patients.id, patients.full_name, users.username, patients.email, patients.phone 
        FROM patients 
        JOIN users ON patients.user_id = users.id
        WHERE 
            patients.full_name LIKE ? OR 
            users.username LIKE ? OR 
            patients.email LIKE ? OR 
            patients.phone LIKE ?
    ");
    $param = "%$search%";
    $stmt->bind_param("ssss", $param, $param, $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "
        SELECT patients.id, patients.full_name, users.username, patients.email, patients.phone 
        FROM patients 
        JOIN users ON patients.user_id = users.id
    ";
    $result = mysqli_query($conn, $query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Patients | Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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

        .btn-primary {
            background-color: #0d47a1;
            border-color: #0d47a1;
        }

        .btn-primary:hover {
            background-color: #1565c0;
            border-color: #1565c0;
        }

        .btn-danger {
            background-color: #d32f2f;
            border-color: #d32f2f;
        }

        .btn-danger:hover {
            background-color: #b71c1c;
            border-color: #b71c1c;
        }

        .table thead {
            background-color: #e3f2fd;
        }

        .table th {
            color: #0d47a1;
        }

        .table td, .table th {
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .sidebar {
                text-align: center;
            }

            .sidebar h2 {
                font-size: 20px;
            }

            .search-form {
                flex-direction: column;
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
            <a href="dashboard.php">Dashboard</a>
            <a href="patients.php" class="active">Manage Patients</a>
            <a href="appointments.php">Appointments</a>
            <a href="timeslots.php">Time Slots</a>
            <a href="services.php">Services</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Content -->
        <main class="col px-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h1 class="mb-0">
                <i class="bi bi-people-fill me-2" style="color: #0d47a1;"></i>Manage Patients
                </h1>
                <form method="GET" class="search-form">
                    <input class="form-control" type="search" name="search" placeholder="Search patients..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="patients.php" class="btn btn-secondary">View All</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>
                                        <a href="edit_patient.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="delete_patient.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this patient?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No patients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>
