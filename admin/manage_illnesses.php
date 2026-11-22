<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may manage illnesses.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database.
$conn = OpenCon();

// Load illnesses with the newest entry first.
$sql = "SELECT *
        FROM illnesses
        ORDER BY illness_id DESC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול מחלות</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ניהול מחלות</h1>
    <p class="section-subtitle">הוספה ומחיקה של קטגוריות מחלה</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/add_illness.php" class="primary-btn">הוספת מחלה</a>
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה</th>
                <th>שם המחלה</th>
                <th>התמחות</th>
                <th>פעולה</th>
            </tr>

            <?php
            // Print one table row for every illness.
            while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
                <td><?php echo $row['illness_id']; ?></td>
                <td><?php echo $row['illness_name']; ?></td>
                <td><?php echo HebrewText($row['specialization']); ?></td>
                <td>
                    <a href="/HealthGuard/admin/delete_illness.php?illness_id=<?php echo $row['illness_id']; ?>" class="danger-btn">מחיקה</a>
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
