<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may delete a doctor.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the dashboard when either required ID is missing.
if (!isset($_GET['doctor_id']) || !isset($_GET['user_id'])) {
    header("Location: /HealthGuard/admin/admin_dashboard.php");
    exit();
}

// Open the database and read both IDs.
$conn = OpenCon();


$doctor_id = $_GET['doctor_id'];
$user_id = $_GET['user_id'];

// Delete the doctor's details row first.
$delete_doctor_sql = "DELETE FROM doctors
                      WHERE doctor_id='$doctor_id'";
mysqli_query($conn, $delete_doctor_sql);

// Delete the matching user account.
$delete_user_sql = "DELETE FROM users
                    WHERE user_id='$user_id'";
mysqli_query($conn, $delete_user_sql);

// Close the database and return to the dashboard.
CloseCon($conn);


header("Location: /HealthGuard/admin/admin_dashboard.php");


exit();
?>
