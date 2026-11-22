<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a patient may view their appointments.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in patient.
$conn = OpenCon();


$patient_id = $_SESSION['user_id'];


// Load the patient's appointments and doctor details.
$sql = "SELECT
            appointments.*,
            users.name AS doctor_name,
            users.email AS doctor_email,
            doctors.specialization
        FROM appointments
        JOIN doctors
            ON appointments.doctor_id=doctors.doctor_id
        JOIN users
            ON doctors.user_id=users.user_id
        WHERE appointments.patient_id='$patient_id'
        ORDER BY
            appointment_date DESC,
            appointment_time DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התורים שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">התורים שלי</h1>
    <p class="section-subtitle">ניהול התורים שהזמנת</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        <a href="/HealthGuard/patient/find_doctor.php" class="primary-btn">הזמנת תור חדש</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>רופא</th>
                <th>אימייל</th>
                <th>התמחות</th>
                <th>תאריך</th>
                <th>שעה</th>
                <th>מצב</th>
                <th>פעולה</th>
            </tr>

            <?php // Print one table row for every appointment. ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td>Dr. <?php echo $row['doctor_name']; ?></td>
                <td><?php echo $row['doctor_email']; ?></td>
                <td><?php echo HebrewText($row['specialization']); ?></td>
                <td><?php echo $row['appointment_date']; ?></td>
                <td><?php echo $row['appointment_time']; ?></td>
                <td><?php echo HebrewText($row['status']); ?></td>
                <td>
                    <?php if ($row['status'] == 'pending') { ?>
                    <a href="/HealthGuard/patient/edit_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                       class="secondary-btn">עדכון</a>
                    <a href="/HealthGuard/patient/delete_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                       class="danger-btn"
                       onclick="return confirm('למחוק את התור?');">
                        הסרה
                    </a>
                    <?php } else { ?>
                        אין פעולות
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the appointment table has been printed.
CloseCon($conn);
?>
