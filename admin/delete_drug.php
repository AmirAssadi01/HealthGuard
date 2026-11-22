<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may delete a drug.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the drug list when no drug ID was provided.
if (!isset($_GET['drug_id'])) {
    header("Location: /HealthGuard/admin/manage_drugs.php");
    exit();
}

// Open the database and read the selected drug ID.
$conn = OpenCon();


$drug_id = $_GET['drug_id'];

// Delete the selected drug.
$delete_sql = "DELETE FROM drugs
               WHERE drug_id='$drug_id'";
mysqli_query($conn, $delete_sql);

// Close the database and return to the drug list.
CloseCon($conn);


header("Location: /HealthGuard/admin/manage_drugs.php");
exit();
?>
