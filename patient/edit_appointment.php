<?php

// Start the session and load the database and notification functions.
session_start();
include("../db_connection.php");
include("../app_helpers.php");

// Only a patient may edit their appointment.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and read the appointment ID from the URL or form.
$conn = OpenCon();
$patient_id = (int)$_SESSION['user_id'];
$appointment_id = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
$message = "";


// Load the appointment only when it belongs to the signed-in patient.
$appointment_query = "SELECT
                             appointments.*,
                             users.name AS doctor_name,
                             doctors.user_id AS doctor_user_id
                      FROM appointments
                      JOIN doctors
                          ON appointments.doctor_id=doctors.doctor_id
                      JOIN users
                          ON doctors.user_id=users.user_id
                      WHERE appointments.appointment_id='$appointment_id'
                      AND appointments.patient_id='$patient_id'";
$appointment = mysqli_fetch_assoc(mysqli_query($conn, $appointment_query));

// Return to the list when the appointment was not found.
if (!$appointment) {
    CloseCon($conn);
    header("Location: /HealthGuard/patient/my_appointments.php");
    exit();
}

// Validate and save the new date and time after the form is submitted.
if (isset($_POST['save_appointment'])) {
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $time_database = $time . ":00";
    $day = date("l", strtotime($date));
    $doctor_id = (int)$appointment['doctor_id'];

    // Load the doctor's working hours for the selected weekday.
    $schedule_sql = "SELECT *
                     FROM doctor_schedules
                     WHERE doctor_id='$doctor_id'
                     AND day_of_week='$day'
                     LIMIT 1";
    $schedule_result = mysqli_query($conn, $schedule_sql);
    $schedule = mysqli_fetch_assoc($schedule_result);

    // Check whether another active appointment already uses this time.
    $taken_sql = "SELECT appointment_id
                  FROM appointments
                  WHERE doctor_id='$doctor_id'
                  AND appointment_date='$date'
                  AND appointment_time='$time_database'
                  AND appointment_id<>'$appointment_id'
                  AND status<>'cancelled'";
    $taken_result = mysqli_query($conn, $taken_sql);
    $time_is_taken = mysqli_num_rows($taken_result) > 0;

    if ($date < date("Y-m-d") || !$schedule) {
        $message = "נא לבחור תאריך עתידי שבו הרופא עובד.";
    } elseif (
        $time_database < $schedule['start_time']
        || $time_database >= $schedule['end_time']
    ) {
        $message = "נא לבחור שעה בתוך שעות העבודה של הרופא.";
    } elseif ($time_is_taken) {
        $message = "השעה הזו כבר תפוסה.";
    } else {
        // Save the new date and time.
        $update_sql = "UPDATE appointments
                       SET
                           appointment_date='$date',
                           appointment_time='$time_database'
                       WHERE appointment_id='$appointment_id'
                       AND patient_id='$patient_id'";
        mysqli_query($conn, $update_sql);

        // Tell the doctor that the patient changed the appointment.
        CreateNotification(
            $conn,
            $appointment['doctor_user_id'],
            "appointment",
            $_SESSION['name'] . " עדכן תור לתאריך $date בשעה $time.",
            "/HealthGuard/doctor/my_appointments.php",
            "appointment_update_" . $appointment_id . "_" . time()
        );

        // Update the patient's existing automatic appointment reminder.
        $reminder_sql = "UPDATE notifications
                         SET
                             message='תזכורת לתור: $date בשעה $time',
                             is_read=0
                         WHERE user_id='$patient_id'
                         AND reference_key='appointment_$appointment_id'";
        mysqli_query($conn, $reminder_sql);

        $message = "התור עודכן בהצלחה.";
        $appointment['appointment_date'] = $date;
        $appointment['appointment_time'] = $time_database;
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>עריכת תור</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">
<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>עדכון תור</h2>
        <p class="subtitle">
            Dr. <?php echo $appointment['doctor_name']; ?>
        </p>

        <?php if ($message != "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <input
            type="hidden"
            name="appointment_id"
            value="<?php echo $appointment_id; ?>"
        >

        <label>תאריך חדש</label>
        <input
            type="date"
            name="appointment_date"
            min="<?php echo date('Y-m-d'); ?>"
            value="<?php echo $appointment['appointment_date']; ?>"
            required
        >

        <label>שעה חדשה</label>
        <input
            type="time"
            name="appointment_time"
            value="<?php echo substr($appointment['appointment_time'], 0, 5); ?>"
            required
        >

        <button type="submit" name="save_appointment">שמירת שינויים</button>
        <a class="secondary-btn" href="/HealthGuard/patient/my_appointments.php">חזרה</a>
    </form>
</div>
</body>
</html>
<?php
// Close the database after the edit page has been printed.
CloseCon($conn);
?>
