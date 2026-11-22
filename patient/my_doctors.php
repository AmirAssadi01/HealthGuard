<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a patient may view the doctor directory.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database.
$conn = OpenCon();

// Load all doctors and their user account details.
$sql = "SELECT
            doctors.doctor_id,
            users.name,
            users.email,
            doctors.specialization
        FROM doctors
        INNER JOIN users
            ON doctors.user_id=users.user_id
        ORDER BY users.name ASC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הרופאים שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">הרופאים שלי</h1>
    <p class="section-subtitle">רופאים הזמינים במערכת</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>שם</th>
                <th>אימייל</th>
                <th>התמחות</th>
                <th>הודעה</th>
            </tr>

            <?php // Print one table row for every doctor. ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td>Dr. <?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo HebrewText($row['specialization']); ?></td>
                <td>
                    <a href="/HealthGuard/patient/my_messages.php?doctor_id=<?php echo $row['doctor_id']; ?>" class="primary-btn">פתיחת צ׳אט</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the doctor table has been printed.
CloseCon($conn);
?>
