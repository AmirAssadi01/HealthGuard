<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may manage drugs.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and load drugs in alphabetical order.
$conn = OpenCon();
$sql = "SELECT *
        FROM drugs
        ORDER BY drug_name ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול תרופות</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="admin-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ניהול תרופות</h1>
    <p class="section-subtitle">תרופות המשמשות במרשמי רופאים</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/add_drug.php" class="primary-btn">הוספת תרופה</a>
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה</th>
                <th>שם התרופה</th>
                <th>קבוצת אלרגיה</th>
                <th>הערות</th>
                <th>פעולה</th>
            </tr>

            <?php // Print one table row for every drug. ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo $row['drug_id']; ?></td>
                <td><?php echo $row['drug_name']; ?></td>
                <td><?php echo $row['allergy_group']; ?></td>
                <td><?php echo $row['notes']; ?></td>
                <td>
                    <a href="/HealthGuard/admin/delete_drug.php?drug_id=<?php echo $row['drug_id']; ?>" class="danger-btn" onclick="return confirm('למחוק את התרופה?');">מחיקה</a>
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
