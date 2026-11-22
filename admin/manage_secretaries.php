<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may manage secretaries.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database.
$conn = OpenCon();

// Load secretary details together with their user accounts.
$sql = "SELECT
            secretaries.secretary_id,
            users.user_id,
            users.name,
            users.email,
            users.created_at
        FROM secretaries
        JOIN users
            ON secretaries.user_id=users.user_id
        ORDER BY secretaries.secretary_id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול מזכירות</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ניהול מזכירות</h1>
    <p class="section-subtitle">המזכירות בודקות הפניות רופא משפחה של מטופלים</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/add_secretary.php" class="primary-btn">הוספת מזכירה</a>
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה</th>
                <th>שם</th>
                <th>אימייל</th>
                <th>נוצר בתאריך</th>
                <th>פעולה</th>
            </tr>
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <?php // Print one table row for every secretary. ?>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['secretary_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <a href="/HealthGuard/admin/delete_secretary.php?user_id=<?php echo $row['user_id']; ?>"
                           class="danger-btn"
                           onclick="return confirm('למחוק את המזכירה?')">מחיקה</a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="5">לא נמצאו מזכירות.</td></tr>
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
