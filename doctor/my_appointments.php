<?php

// Start the session and load the database and notification functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a doctor may view and update these appointments.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in doctor.
$conn = OpenCon();


$user_id = $_SESSION['user_id'];


$sqlDoctor = "SELECT doctor_id
              FROM doctors
              WHERE user_id='$user_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);
$doctor_id = $doctor['doctor_id'];

// Accept or cancel one pending appointment after the decision form is submitted.
if (isset($_POST['appointment_id'], $_POST['new_status'])) {
    $appointment_id = (int)$_POST['appointment_id'];

    // Only the two supported decision values may be saved.
    if ($_POST['new_status'] == "accepted") {
        $new_status = "accepted";
    } else {
        $new_status = "cancelled";
    }

    // Make sure the appointment belongs to this doctor and is still pending.
    $appointment_sql = "SELECT patient_id
                        FROM appointments
                        WHERE appointment_id='$appointment_id'
                        AND doctor_id='$doctor_id'
                        AND status='pending'";
    $appointment_result = mysqli_query($conn, $appointment_sql);
    $rowAppointment = mysqli_fetch_assoc($appointment_result);

    if ($rowAppointment) {
        // Save the doctor's decision.
        $update_sql = "UPDATE appointments
                       SET status='$new_status'
                       WHERE appointment_id='$appointment_id'";
        mysqli_query($conn, $update_sql);

        // Tell the patient that the appointment status changed.
        CreateNotification(
            $conn,
            $rowAppointment['patient_id'],
            "appointment",
            "מצב התור עודכן על ידי הרופא: " . HebrewText($new_status),
            "/HealthGuard/patient/my_appointments.php",
            "doctor_status_" . $appointment_id . "_" . $new_status
        );
    }
}

// Load the doctor's appointments and any report connected to each appointment.
$sql = "SELECT
            appointments.*,
            users.name AS patient_name,
            users.email AS patient_email,
            medical_reports.report_id
        FROM appointments
        JOIN users
            ON appointments.patient_id=users.user_id
        LEFT JOIN medical_reports
            ON appointments.appointment_id=medical_reports.appointment_id
        WHERE appointments.doctor_id='$doctor_id'
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
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">התורים שלי</h1>
    <p class="section-subtitle">תורים שהמטופלים הזמינו</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>שם המטופל</th>
                <th>אימייל</th>
                <th>תאריך</th>
                <th>שעה</th>
                <th>מצב</th>
                <th>החלטה</th>
                <th>דוח</th>
            </tr>

            <?php
            // Print one table row for every appointment.
            while ($row = mysqli_fetch_assoc($result)) {
                $report_action = 'התחלת ביקור';

                if ($row['report_id']) {
                    $report_action = 'פתיחת דוח';
                }
            ?>
            <tr>
                <td><?php echo $row['patient_name']; ?></td>
                <td><?php echo $row['patient_email']; ?></td>
                <td><?php echo $row['appointment_date']; ?></td>
                <td><?php echo $row['appointment_time']; ?></td>
                <td><?php echo HebrewText($row['status']); ?></td>
                <td>
                    <?php if ($row['status'] == 'pending') { ?>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                        <button name="new_status" value="accepted" class="primary-btn">אישור</button>
                        <button name="new_status" value="cancelled" class="danger-btn">ביטול</button>
                    </form>
                    <?php } else { ?>
                        —
                    <?php } ?>
                </td>
                <td>
                    <?php if ($row['status'] != 'cancelled') { ?>
                    <a href="/HealthGuard/doctor/manage_appointment.php?appointment_id=<?php echo $row['appointment_id']; ?>" class="primary-btn">
                        <?php echo $report_action; ?>
                    </a>
                    <?php } else { ?>
                        בוטל
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
