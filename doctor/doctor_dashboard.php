<?php

// Start the session so the dashboard can read the signed-in doctor.
session_start();

// Only a doctor may open the doctor dashboard.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Use the saved name in the welcome message and avatar.
$doctor_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>לוח רופא - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="hg-patient-dashboard">

    <section class="hg-patient-header">
        <div class="hg-patient-header-text">
            <h1>ברוך הבא ד״ר <?php echo $doctor_name; ?></h1>
            <p>
                נהל בקלות את המטופלים, התורים והדוחות הרפואיים שלך
                מלוח העבודה האישי שלך.
            </p>
        </div>

        <div class="hg-patient-user-box">
            <div class="hg-patient-avatar">
                <?php echo strtoupper(substr($doctor_name, 0, 1)); ?>
            </div>
            <div class="hg-patient-user-info">
                <strong>לוח רופא</strong>
                <span><?php echo $doctor_name; ?></span>
            </div>
        </div>
    </section>

    <div class="hg-patient-layout">

        <main class="hg-patient-main">

            <section class="hg-panel">
                <div class="hg-panel-head">
                    <h2>שירותים רפואיים</h2>
                    <p>גישה מהירה לכלי העבודה שלך</p>
                </div>

                <div class="hg-service-grid">

                    <a href="/HealthGuard/doctor/my_profile.php" class="hg-service-card">
                        <div class="hg-service-icon">👤</div>
                        <span>הפרופיל שלי</span>
                    </a>

                    <a href="/HealthGuard/doctor/my_patients.php" class="hg-service-card">
                        <div class="hg-service-icon">🧑</div>
                        <span>המטופלים שלי</span>
                    </a>

                    <a href="/HealthGuard/doctor/my_appointments.php" class="hg-service-card">
                        <div class="hg-service-icon">📅</div>
                        <span>תורים</span>
                    </a>

                    <a href="/HealthGuard/doctor/my_reports.php" class="hg-service-card">
                        <div class="hg-service-icon">📄</div>
                        <span>דוחות</span>
                    </a>

                    <a href="/HealthGuard/doctor/my_messages.php" class="hg-service-card">
                        <div class="hg-service-icon">💬</div>
                        <span>הודעות</span>
                    </a>

                    <a href="/HealthGuard/doctor/my_schedule.php" class="hg-service-card">
                        <div class="hg-service-icon">🕐</div>
                        <span>לוח הזמנים שלי</span>
                    </a>

                    <a href="/HealthGuard/notifications.php" class="hg-service-card">
                        <div class="hg-service-icon">🔔</div>
                        <span>התראות</span>
                    </a>

                </div>
            </section>

        </main>

    </div>
</div>

</body>
</html>
