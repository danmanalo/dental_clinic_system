<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: appointments.php");
    exit();
}

$appointment_id = (int)$_GET['id'];

// Fetch the appointment details
$stmt = $conn->prepare("
    SELECT a.id, a.status, ts.id as current_slot_id, ts.slot_date, ts.start_time, ts.end_time,
           p.full_name, s.name AS service_name
    FROM appointments a
    JOIN timeslots ts ON a.time_slot_id = ts.id
    JOIN patients p ON a.patient_id = p.id
    JOIN services s ON a.service_id = s.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Appointment not found.";
    header("Location: appointments.php");
    exit();
}

$appointment = $result->fetch_assoc();

// Fetch available timeslots (not booked)
$available_slots_query = "
    SELECT ts.id, ts.slot_date, ts.start_time, ts.end_time
    FROM timeslots ts
    LEFT JOIN appointments a ON ts.id = a.time_slot_id AND a.id != ?
    WHERE a.id IS NULL OR a.status = 'Cancelled'
    ORDER BY ts.slot_date, ts.start_time
";
$slots_stmt = $conn->prepare($available_slots_query);
$slots_stmt->bind_param("i", $appointment_id);
$slots_stmt->execute();
$slots_result = $slots_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_slot_id = $_POST['time_slot_id'] ?? null;
    if (!$new_slot_id || !is_numeric($new_slot_id)) {
        $error = "Please select a valid time slot.";
    } else {
        // Check if selected timeslot is available
        $check_stmt = $conn->prepare("
            SELECT a.id 
            FROM appointments a 
            WHERE a.time_slot_id = ? AND a.id != ? AND a.status != 'Cancelled'
        ");
        $check_stmt->bind_param("ii", $new_slot_id, $appointment_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Selected time slot is already booked.";
        } else {
            // Update appointment timeslot
            $update_stmt = $conn->prepare("UPDATE appointments SET time_slot_id = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $new_slot_id, $appointment_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Appointment rescheduled successfully.";
                header("Location: appointments.php");
                exit();
            } else {
                $error = "Failed to reschedule appointment.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reschedule Appointment | Tooth Talks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="p-4">
    <h2>Reschedule Appointment</h2>
    <p>
        Patient: <strong><?= htmlspecialchars($appointment['full_name']) ?></strong><br>
        Service: <strong><?= htmlspecialchars($appointment['service_name']) ?></strong><br>
        Current Schedule: <strong><?= date("F j, Y", strtotime($appointment['slot_date'])) ?></strong>
        from <strong><?= date("g:i A", strtotime($appointment['start_time'])) ?></strong> to
        <strong><?= date("g:i A", strtotime($appointment['end_time'])) ?></strong>
    </p>

    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="time_slot_id" class="form-label">Select New Time Slot</label>
            <select name="time_slot_id" id="time_slot_id" class="form-select" required>
                <option value="">-- Choose a Time Slot --</option>
                <?php while ($slot = $slots_result->fetch_assoc()) : ?>
                    <option value="<?= $slot['id'] ?>" <?= ($slot['id'] == $appointment['current_slot_id']) ? 'selected' : '' ?>>
                        <?= date("F j, Y", strtotime($slot['slot_date'])) ?> -
                        <?= date("g:i A", strtotime($slot['start_time'])) ?> to
                        <?= date("g:i A", strtotime($slot['end_time'])) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Reschedule</button>
        <a href="appointments.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>
