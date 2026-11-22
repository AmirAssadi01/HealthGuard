<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may delete an illness.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the illness list when no illness ID was provided.
if (!isset($_GET['illness_id'])) {
    header("Location: /HealthGuard/admin/manage_illnesses.php");
    exit();
}

// Open the database and read the selected illness ID.
$conn = OpenCon();


$illness_id = $_GET['illness_id'];

// Delete the selected illness.
$delete_sql = "DELETE FROM illnesses
               WHERE illness_id='$illness_id'";
mysqli_query($conn, $delete_sql);

// Close the database and return to the illness list.
CloseCon($conn);


header("Location: /HealthGuard/admin/manage_illnesses.php");


exit();
?>
