<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$uploadDir = '../uploads/services/';

// Handle service addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);

    // Image upload handling
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading image.";
        } elseif (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('service_', true) . '.' . $ext;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $dest = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $imagePath = 'uploads/services/' . $newFileName; // Store relative path for DB
            } else {
                $errors[] = "Failed to move uploaded image.";
            }
        }
    }

    if ($name === '') {
        $errors[] = "Service name is required.";
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = "Please enter a valid price.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO services (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $description, $price, $imagePath);
        if ($stmt->execute()) {
            header("Location: services.php?added=1");
            exit();
        } else {
            $errors[] = "Database error: Could not add service.";
        }
    }
}

// Handle service deletion (also delete image file)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Get current image path
    $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imageToDelete);
    $stmt->fetch();
    $stmt->close();

    if ($imageToDelete && file_exists('../' . $imageToDelete)) {
        unlink('../' . $imageToDelete);
    }

    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: services.php?deleted=1");
    exit();
}

// Fetch all services
$services_result = $conn->query("SELECT * FROM services ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Services | Tooth Talks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />

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

        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0d47a1;
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

        .service-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
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
            <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid" />
            <h2>Tooth Talks</h2>
            <a href="dashboard.php">Dashboard</a>
            <a href="patients.php">Manage Patients</a>
            <a href="appointments.php">Appointments</a>
            <a href="timeslots.php">Time Slots</a>
            <a href="services.php" class="active">Services</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Content -->
        <main class="col px-4 py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h1><i class="bi bi-card-list me-2" style="color: #0d47a1;"></i>Services</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add New Service</button>
            </div>

            <!-- Alerts -->
            <?php if (isset($_GET['added'])): ?>
                <div class="alert alert-success">Service added successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Service deleted successfully.</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Services Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th>Price (₱)</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($services_result && $services_result->num_rows > 0): ?>
                        <?php while ($service = $services_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($service['image'] && file_exists('../' . $service['image'])): ?>
                                        <img src="../<?= htmlspecialchars($service['image']) ?>" alt="Service Image" class="service-img" />
                                    <?php else: ?>
                                        <img src="../uploads/services/placeholder.png" alt="No Image" class="service-img" />
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($service['name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($service['description'])) ?></td>
                                <td>₱ <?= number_format($service['price'], 2) ?></td>
                                <td>
                                    <a href="edit_service.php?id=<?= $service['id'] ?>" class="btn btn-sm btn-primary me-1">Edit</a>
                                    <a href="services.php?delete=<?= $service['id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No services found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label for="serviceName" class="form-label">Service Name</label>
              <input type="text" class="form-control" id="serviceName" name="name" required>
          </div>
          <div class="mb-3">
              <label for="serviceDescription" class="form-label">Description</label>
              <textarea class="form-control" id="serviceDescription" name="description" rows="3"></textarea>
          </div>
          <div class="mb-3">
              <label for="servicePrice" class="form-label">Price (₱)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="servicePrice" name="price" required>
          </div>
          <div class="mb-3">
              <label for="serviceImage" class="form-label">Service Image</label>
              <input type="file" class="form-control" id="serviceImage" name="image" accept="image/*">
              <small class="form-text text-muted">Optional. JPG, PNG, or GIF only.</small>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

</body>
</html>
