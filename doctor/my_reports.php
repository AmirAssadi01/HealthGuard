<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a doctor may view their medical reports.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in doctor.
$conn = OpenCon();


$doctor_user_id = $_SESSION['user_id'];


$sqlDoctor = "SELECT doctor_id
              FROM doctors
              WHERE user_id='$doctor_user_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);
$doctor_id = $doctor['doctor_id'];

// Load reports with their appointment and patient information.
$sql = "SELECT
            medical_reports.*,
            appointments.appointment_date,
            appointments.appointment_time,
            users.name AS patient_name,
            users.email AS patient_email
        FROM medical_reports
        JOIN appointments
            ON medical_reports.appointment_id=appointments.appointment_id
        JOIN users
            ON medical_reports.patient_id=users.user_id
        WHERE medical_reports.doctor_id='$doctor_id'
        ORDER BY medical_reports.created_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הדוחות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">הדוחות שלי</h1>
    <p class="section-subtitle">דוחות רפואיים שנוצרו לאחר תורים</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מטופל</th>
                <th>אימייל</th>
                <th>תאריך התור</th>
                <th>שעת התור</th>
                <th>אבחנה</th>
                <th>נוצר בתאריך</th>
                <th>פעולה</th>
            </tr>

            <?php // Print one table row for every report. ?>
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['patient_name']; ?></td>
                    <td><?php echo $row['patient_email']; ?></td>
                    <td><?php echo $row['appointment_date']; ?></td>
                    <td><?php echo $row['appointment_time']; ?></td>
                    <td><?php echo $row['diagnosis']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="/HealthGuard/doctor/manage_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>" class="primary-btn">פתיחה</a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="7">עדיין אין דוחות.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the report table has been printed.
CloseCon($conn);
?>
