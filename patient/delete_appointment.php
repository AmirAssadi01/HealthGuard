<?php

// Start the session and load the database and notification functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a patient may cancel one of their appointments.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the appointment list when no appointment ID was provided.
if (!isset($_GET['appointment_id'])) {
    header("Location: /HealthGuard/patient/my_appointments.php");
    exit();
}


// Open the database and read the appointment and patient IDs.
$conn = OpenCon();


$appointment_id = $_GET['appointment_id'];
$patient_id = $_SESSION['user_id'];


// Load the appointment only when it belongs to this patient and is pending.
$sqlOwn = "SELECT
               appointments.appointment_id,
               doctors.user_id AS doctor_user_id
           FROM appointments
           JOIN doctors
               ON appointments.doctor_id=doctors.doctor_id
           WHERE appointments.appointment_id='$appointment_id'
           AND appointments.patient_id='$patient_id'
           AND appointments.status='pending'";
$appointment = mysqli_fetch_assoc(mysqli_query($conn, $sqlOwn));


// Cancel the appointment only when the ownership check succeeded.
if ($appointment) {
    // Unlink an unused family-doctor referral from this appointment.
    mysqli_query($conn, "UPDATE family_doctor_uploads
                         SET linked_appointment_id=NULL
                         WHERE linked_appointment_id='$appointment_id'
                         AND consumed_at IS NULL");

    // Keep the appointment history and mark the appointment as cancelled.
    $cancel_sql = "UPDATE appointments
                   SET status='cancelled'
                   WHERE appointment_id='$appointment_id'
                   AND patient_id='$patient_id'";
    mysqli_query($conn, $cancel_sql);

    // Tell the doctor that the patient cancelled the appointment.
    CreateNotification(
        $conn,
        $appointment['doctor_user_id'],
        "appointment",
        $_SESSION['name'] . " ביטל תור.",
        "/HealthGuard/doctor/my_appointments.php",
        "appointment_cancelled_$appointment_id"
    );
}

// Close the database and return to the appointment list.
CloseCon($conn);


header("Location: /HealthGuard/patient/my_appointments.php");
exit();
?>
