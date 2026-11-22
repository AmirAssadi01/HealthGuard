<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may delete a patient.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the patient list when no user ID was provided.
if (!isset($_GET['user_id'])) {
    header("Location: /HealthGuard/admin/manage_patients.php");
    exit();
}

// Open the database and read the selected user ID.
$conn = OpenCon();


$user_id = $_GET['user_id'];

// Delete the selected patient account.
$delete_sql = "DELETE FROM users
               WHERE user_id='$user_id'";
mysqli_query($conn, $delete_sql);

// Close the database and return to the patient list.
CloseCon($conn);


header("Location: /HealthGuard/admin/manage_patients.php");


exit();
?>
