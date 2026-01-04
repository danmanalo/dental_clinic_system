<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get services
$query = "SELECT id, name, description, price, image FROM services ORDER BY name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services | Tooth Talks Patient</title>
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-4px);
        }
        .card-title {
            font-weight: 600;
            color: #1565c0;
        }
        .card img {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            object-fit: cover;
            max-height: 200px;
            width: 100%;
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
        <!-- Sidebar (same as dashboard.php) -->
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid">
            <h2>Tooth Talks</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="book_appointment.php">Book Appointment</a>
            <a href="my_appointments.php">My Appointments</a>
            <a href="services.php" class="active">Services</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Content -->
        <main class="col px-4 py-4">
            <h1 class="mb-4"><i class="bi bi-list-ul me-2" style="color: #0d47a1;"></i>Our Services</h1>

            <div class="row g-4">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <?php 
                                    $image_path = '../' . $row['image'];
                                    if (!empty($row['image']) && file_exists($image_path)): 
                                ?>
                                    <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="card-img-top" loading="lazy">
                                <?php else: ?>
                                    <img src="../uploads/services/placeholder.png" alt="Service Image" class="card-img-top" loading="lazy">
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                                    <p class="card-text flex-grow-1"><?= htmlspecialchars($row['description']) ?></p>
                                    <p class="text-muted">Price: ₱<?= number_format($row['price'], 2) ?></p>
                                    <a href="book_appointment.php?service_id=<?= $row['id'] ?>" class="btn btn-outline-primary mt-auto">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-center">No services available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
