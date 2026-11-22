<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a doctor may view their patient list.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the doctor's details row.
$conn = OpenCon();


$user_id = $_SESSION['user_id'];
$sqlDoctor = "SELECT doctor_id
              FROM doctors
              WHERE user_id='$user_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);

// Stop when the signed-in user has no doctor details row.
if (!$doctor) {
    CloseCon($conn);
    header("Location: /HealthGuard/login.php");
    exit();
}

$doctor_id = $doctor['doctor_id'];


// Load patients who have booked at least one appointment with this doctor.
$sql = "SELECT DISTINCT
            users.*
        FROM users
        JOIN appointments
            ON appointments.patient_id=users.user_id
        WHERE users.role='patient'
        AND appointments.doctor_id='$doctor_id'
        ORDER BY users.user_id DESC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>המטופלים שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">המטופלים שלי</h1>
    <p class="section-subtitle">מטופלים שקבעו אצלך תור</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה מטופל</th>
                <th>שם</th>
                <th>אימייל</th>
                <th>נוצר בתאריך</th>
                <th>תזונה</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="/HealthGuard/doctor/manage_nutrition.php?patient_id=<?php echo $row['user_id']; ?>" class="primary-btn">ניהול תוכנית</a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center;">עדיין אין מטופלים שקבעו אצלך תור.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>

<?php
// Close the database after the patient table has been printed.
CloseCon($conn);
?>
