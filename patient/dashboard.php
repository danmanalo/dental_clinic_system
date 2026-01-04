<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT p.id, p.full_name FROM patients p WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$patient_result = $stmt->get_result();

if ($patient_result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}

$patient = $patient_result->fetch_assoc();
$patient_id = $patient['id'];

$next_stmt = $conn->prepare("
    SELECT t.slot_date AS date, t.start_time AS time, s.name AS service_name
    FROM appointments a
    JOIN timeslots t ON a.time_slot_id = t.id
    JOIN services s ON a.service_id = s.id
    WHERE a.patient_id = ? AND t.slot_date >= CURDATE() AND a.status = 'confirmed'
    ORDER BY t.slot_date ASC, t.start_time ASC
    LIMIT 1
");
$next_stmt->bind_param("i", $patient_id);
$next_stmt->execute();
$next_result = $next_stmt->get_result();
$next_appointment = $next_result->fetch_assoc();

$past_stmt = $conn->prepare("
    SELECT t.slot_date AS date, t.start_time AS time, s.name AS service_name, a.status
    FROM appointments a
    JOIN timeslots t ON a.time_slot_id = t.id
    JOIN services s ON a.service_id = s.id
    WHERE a.patient_id = ? AND t.slot_date < CURDATE()
    ORDER BY t.slot_date DESC, t.start_time DESC
    LIMIT 2
");
$past_stmt->bind_param("i", $patient_id);
$past_stmt->execute();
$past_result = $past_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Tooth Talks Patient</title>
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
        }
        .card h5 {
            font-weight: 600;
            color: #1565c0;
        }
        .btn-primary {
            background-color: #1976d2;
            border-color: #1976d2;
        }
        .btn-outline-primary {
            color: #1976d2;
            border-color: #1976d2;
        }
        .btn-outline-primary:hover {
            background-color: #1976d2;
            color: #fff;
        }
        .btn-outline-dark:hover {
            background-color: #0d47a1;
            color: #fff;
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
        <nav class="col-12 col-md-3 col-lg-2 sidebar">
            <img src="../logo.png" alt="Tooth Talks Logo" class="img-fluid">
            <h2>Tooth Talks</h2>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="book_appointment.php">Book Appointment</a>
            <a href="my_appointments.php">My Appointments</a>
            <a href="services.php">Services</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <main class="col px-4 py-4">
            <h1 class="mb-4"><i class="bi bi-person-circle me-2" style="color: #0d47a1;"></i>Welcome, <?= htmlspecialchars($patient['full_name']) ?>!</h1>

            <div class="row g-4">
                <!-- Next Appointment Card -->
                <div class="col-md-6">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="bi bi-calendar-check me-2"></i>Next Appointment</h5>
                        <?php if ($next_appointment): ?>
                            <p><strong>Service:</strong> <?= htmlspecialchars($next_appointment['service_name']) ?></p>
                            <p><strong>Date:</strong> <?= htmlspecialchars(date("F j, Y", strtotime($next_appointment['date']))) ?></p>
                            <p><strong>Time:</strong> <?= htmlspecialchars(date("g:i A", strtotime($next_appointment['time']))) ?></p>
                            <a href="my_appointments.php" class="btn btn-outline-primary mt-2">View All</a>
                        <?php else: ?>
                            <p>You have no upcoming appointments.</p>
                            <a href="book_appointment.php" class="btn btn-primary mt-2">Book Now</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Past Appointments Card -->
                <div class="col-md-6">
                    <div class="card p-4">
                        <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Appointments</h5>
                        <?php if ($past_result->num_rows > 0): ?>
                            <?php while($row = $past_result->fetch_assoc()): ?>
                                <p>
                                    <strong><?= htmlspecialchars($row['service_name']) ?></strong> —
                                    <?= htmlspecialchars(date("F j, Y", strtotime($row['date']))) ?> at
                                    <?= htmlspecialchars(date("g:i A", strtotime($row['time']))) ?>
                                    (<?= ucfirst($row['status']) ?>)
                                </p>
                            <?php endwhile; ?>
                            <a href="my_appointments.php" class="btn btn-outline-dark mt-2">View History</a>
                        <?php else: ?>
                            <p>No past appointments found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chatbot Button and Box -->
<button id="chatbotToggle" class="btn btn-primary rounded-circle"
        style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
    💬
</button>

<div id="chatbotBox" class="card shadow" style="position: fixed; bottom: 80px; right: 20px; width: 320px; display: none; z-index: 1001;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <strong>Tooth Talks Chatbot</strong>
        <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="toggleChatbot()"></button>
    </div>
    <div class="card-body" style="max-height: 300px; overflow-y: auto;" id="chatContent">
        <div class="text-muted small mb-2">How can I help you today?</div>
    </div>
    <div class="card-footer">
        <div id="typingIndicator" class="text-muted small mb-2" style="display:none;">Typing...</div>
        <input type="text" class="form-control mb-2" placeholder="Type a message..." id="userInput" onkeypress="handleKey(event)">
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-sm btn-outline-primary" onclick="quickQuestion('What are your hours?')">Clinic Hours</button>
            <button class="btn btn-sm btn-outline-primary" onclick="quickQuestion('How do I book?')">How to Book</button>
            <button class="btn btn-sm btn-outline-primary" onclick="quickQuestion('Where are you located?')">Location</button>
        </div>
    </div>
</div>

<script>
    const chatbotToggle = document.getElementById('chatbotToggle');
    const chatbotBox = document.getElementById('chatbotBox');
    const chatContent = document.getElementById('chatContent');
    const userInput = document.getElementById('userInput');
    const typingIndicator = document.getElementById('typingIndicator');

    chatbotToggle.addEventListener('click', toggleChatbot);

    function toggleChatbot() {
        chatbotBox.style.display = chatbotBox.style.display === 'none' ? 'block' : 'none';
    }

    function handleKey(event) {
        if (event.key === 'Enter') {
            sendMessage();
        }
    }

    function quickQuestion(text) {
        userInput.value = text;
        sendMessage();
    }

    function sendMessage() {
        const msg = userInput.value.trim();
        if (!msg) return;

        appendMessage(msg, 'user');
        userInput.value = '';
        typingIndicator.style.display = 'block';

        setTimeout(() => {
            const response = getBotReply(msg);
            appendMessage(response, 'bot');
            typingIndicator.style.display = 'none';
        }, 800);
    }

    function appendMessage(message, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.classList.add('mb-2');

        if (sender === 'user') {
            msgDiv.innerHTML = `<div class="text-end"><span class="badge bg-primary">${message}</span></div>`;
        } else {
            msgDiv.innerHTML = `<div class="text-start"><span class="badge bg-secondary">${message}</span></div>`;
        }

        chatContent.appendChild(msgDiv);
        chatContent.scrollTop = chatContent.scrollHeight;
    }

    function getBotReply(msg) {
    const text = msg.toLowerCase();

    if (text.includes("hours") || text.includes("open")) {
        return "Our clinic is open Monday to Saturday, 9 AM to 5 PM.";
    } else if (text.includes("book") || text.includes("appointment")) {
        return "To book an appointment, click on 'Book Appointment' in the sidebar and follow the instructions.";
    } else if (text.includes("location") || text.includes("where")) {
        return "We are located at #123 Smile Street, Dental City, Philippines.";
    } else if (text.includes("services")) {
        return "We offer services like dental cleaning, tooth extraction, braces consultation, whitening, and more. Visit the 'Services' page for the full list.";
    } else if (text.includes("cleaning")) {
        return "Dental cleaning typically costs ₱800 to ₱1,200 depending on the case.";
    } else if (text.includes("walk-in")) {
        return "We prioritize appointments, but walk-ins are accepted if slots are available.";
    } else if (text.includes("insurance")) {
        return "Yes, we accept most major dental insurance providers. Please bring your insurance card on your visit.";
    } else if (text.includes("cancel") || text.includes("reschedule")) {
        return "You can cancel or reschedule your appointment from the 'My Appointments' section.";
    } else if (text.includes("contact")) {
        return "You can contact us at 0917-123-4567 or email tooth.talks@example.com.";
    } else {
        return "I'm not sure about that. You can ask about our hours, location, services, or how to book.";
    }
}

</script>
</body>
</html>
