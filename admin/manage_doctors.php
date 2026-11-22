<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may manage doctors.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database.
$conn = OpenCon();

// Load doctor details together with the matching user account.
$sql = "SELECT
            doctors.doctor_id,
            users.user_id,
            users.name,
            users.email,
            users.city,
            doctors.specialization,
            doctors.consultation_fee
        FROM doctors
        INNER JOIN users
            ON doctors.user_id=users.user_id
        ORDER BY doctors.doctor_id DESC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול רופאים</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ניהול רופאים</h1>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/add_doctor.php" class="primary-btn">הוספת רופא</a>
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה רופא</th>
                <th>שם</th>
                <th>אימייל</th>
                <th>עיר</th>
                <th>התמחות</th>
                <th>מחיר</th>
                <th>פעולה</th>
            </tr>

            <?php
            // Print one table row for every doctor.
            while ($row = mysqli_fetch_assoc($result)) {
                $city_text = 'לא הוגדר';
                if (!empty($row['city'])) {
                    $city_text = $row['city'];
                }

                $fee_text = 'Free';
                if ($row['consultation_fee'] !== null) {
                    $fee_text = '$' . number_format($row['consultation_fee'], 2);
                }
            ?>
            <tr>
                <td><?php echo $row['doctor_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $city_text; ?></td>
                <td><?php echo HebrewText($row['specialization']); ?></td>
                <td><?php echo $fee_text; ?></td>
                <td>
                    <a href="/HealthGuard/admin/delete_doctor.php?doctor_id=<?php echo $row['doctor_id']; ?>&user_id=<?php echo $row['user_id']; ?>" class="danger-btn">מחיקה</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>

<?php
// Close the database after the table has been printed.
CloseCon($conn);
?>
