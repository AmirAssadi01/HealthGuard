<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may view all users.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database.
$conn = OpenCon();

// Load all users with the newest account first.
$sql = "SELECT *
        FROM users
        ORDER BY user_id DESC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול משתמשים</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ניהול משתמשים</h1>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה</th>
                <th>שם</th>
                <th>תפקיד</th>
                <th>אימייל</th>
                <th>נוצר בתאריך</th>
                <th>פעולה</th>
            </tr>

            <?php
            // Print one table row for every user.
            while ($row = mysqli_fetch_assoc($result)) {
            ?>
            <tr>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo HebrewText($row['role']); ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <a href="/HealthGuard/admin/delete_user.php?user_id=<?php echo $row['user_id']; ?>" class="danger-btn">מחיקה</a>
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
