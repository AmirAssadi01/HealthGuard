<?php

// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only a patient may view their progress page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and identify the signed-in patient.
$conn = OpenCon();
$patient_id = (int)$_SESSION['user_id'];


// Load the eight newest weekly weight results.
$weights_sql = "SELECT
                    week_start,
                    end_weight
                FROM weekly_nutrition_reports
                WHERE patient_id='$patient_id'
                ORDER BY week_start DESC
                LIMIT 8";
$weights_result = mysqli_query($conn, $weights_sql);

// Calculate the average weight for the six newest months.
$months_sql = "SELECT
                   DATE_FORMAT(week_start, '%Y-%m') AS month,
                   ROUND(AVG(end_weight), 1) AS avg_weight
               FROM weekly_nutrition_reports
               WHERE patient_id='$patient_id'
               GROUP BY DATE_FORMAT(week_start, '%Y-%m')
               ORDER BY month DESC
               LIMIT 6";
$months_result = mysqli_query($conn, $months_sql);

// Count taken and skipped medication diary entries.
$medicine_sql = "SELECT
                     SUM(status='taken') AS נלקחו,
                     SUM(status='skipped') AS דולגו
                 FROM medication_logs
                 WHERE patient_id='$patient_id'";
$medicine_result = mysqli_query($conn, $medicine_sql);
$medicine = mysqli_fetch_assoc($medicine_result);

// Copy weekly results into an array and find the largest weight for chart scaling.
$weight_rows = [];
$max_weight = 1;
while ($row = mysqli_fetch_assoc($weights_result)) {
    $weight_rows[] = $row;
    $max_weight = max($max_weight, (float)$row['end_weight']);
}

// Copy monthly results into an array for display.
$month_rows = [];
while ($row = mysqli_fetch_assoc($months_result)) {
    $month_rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ההתקדמות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">
<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ההתקדמות שלי</h1>
    <p class="section-subtitle">התקדמות במשקל וסיכום מעקב התרופות</p>

    <div class="feature-grid">
        <div class="card">
            <h3>סיכום מעקב תרופות</h3>
            <div class="feature-number"><?php echo (int)($medicine['taken'] ?? 0); ?> נלקחו</div>
            <p><?php echo (int)($medicine['skipped'] ?? 0); ?> דולגו</p>
        </div>

        <div class="card">
            <h3>משקל שבועי</h3>
            <?php foreach (array_reverse($weight_rows) as $row) {
                $width = round((float)$row['end_weight'] / $max_weight * 100); ?>
                <div class="chart-line">
                    <span><?php echo $row['week_start']; ?></span>
                    <div class="chart-track">
                        <div class="chart-bar" style="width:<?php echo $width; ?>%"></div>
                    </div>
                    <strong><?php echo $row['end_weight']; ?></strong>
                </div>
            <?php } ?>
            <?php if (!$weight_rows) { ?>
                <p>עדיין אין דוחות משקל.</p>
            <?php } ?>
        </div>

        <div class="card">
            <h3>ממוצע משקל חודשי</h3>
            <?php foreach (array_reverse($month_rows) as $row) { ?>
                <p>
                    <strong><?php echo $row['month']; ?></strong>
                    — <?php echo $row['avg_weight']; ?> kg
                </p>
            <?php } ?>
            <?php if (!$month_rows) { ?>
                <p>עדיין אין נתונים חודשיים.</p>
            <?php } ?>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <tr><th>שבוע</th><th>משקל סופי</th></tr>
            <?php foreach ($weight_rows as $row) { ?>
                <tr>
                    <td><?php echo $row['week_start']; ?></td>
                    <td><?php echo $row['end_weight']; ?> kg</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>
</body>
</html>
<?php
// Close the database after the progress page has been printed.
CloseCon($conn);
?>
