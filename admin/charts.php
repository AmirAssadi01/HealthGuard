<?php
// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only an administrator may view system statistics.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database for all chart queries.
$conn = OpenCon();

// Run a query that returns one number.
function scalar($conn, $sql)
{
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        return 0;
    }

    $row = mysqli_fetch_row($res);

    if ($row) {
        return (int)$row[0];
    }

    return 0;
}

// Run a query that returns labels and their totals.
function grouped($conn, $sql)
{
    $out = array();
    $res = mysqli_query($conn, $sql);

    if ($res) {
        while ($r = mysqli_fetch_row($res)) {
            // Use a readable label when the saved value is empty.
            if ($r[0] === null || $r[0] === '') {
                $label = '(none)';
            } else {
                $label = $r[0];
            }

            $out[$label] = (int)$r[1];
        }
    }

    return $out;
}

// Print one horizontal bar inside a chart.
function bar($label, $count, $max, $color)
{
    // Calculate the bar width as a percentage of the largest value.
    if ($max > 0) {
        $pct = round(($count / $max) * 100);
    } else {
        $pct = 0;
    }

    // Keep small non-zero bars visible.
    if ($count > 0 && $pct < 6) {
        $pct = 6;
    }

    // Print the label, bar, and total.
    echo '<div style="display:flex; align-items:center; gap:12px; margin:8px 0;">';
    echo '<div style="width:170px; text-transform:capitalize; color:#333;">';
    echo HebrewText($label);
    echo '</div>';
    echo '<div style="flex:1; background:#eef1f6; border-radius:6px;">';

    if ($count > 0) {
        $minimum_width = '34px';
    } else {
        $minimum_width = '0';
    }

    echo '<div style="width:' . $pct . '%; min-width:' . $minimum_width;
    echo '; background:' . $color;
    echo '; color:#fff; padding:5px 10px; border-radius:6px;';
    echo ' box-sizing:border-box; text-align:right; font-weight:600;">';
    echo $count;
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Print a complete bar chart from a label-and-total array.
function barChart($data, $palette)
{
    // Show a friendly message when the query returned no data.
    if (count($data) === 0) {
        echo '<p>עדיין אין נתונים.</p>';
        return;
    }
    // Find the largest total so every bar uses the same scale.
    $max = max($data);
    $i = 0;

    // Print each bar with the next colour in the palette.
    foreach ($data as $label => $count) {
        bar($label, $count, $max, $palette[$i % count($palette)]);
        $i++;
    }
}

// Colours are reused when a chart contains more bars than colours.
$palette = array('#4a90d9', '#43b97f', '#e0883a', '#9b59b6', '#e05a6b', '#3aa6b9', '#7f8c8d');

// Load the large totals shown at the top of the page.
$kpi = array(
    'Total Users' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM users"
    ),
    'Patients' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM users
         WHERE role='patient'"
    ),
    'Doctors' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM users
         WHERE role='doctor'"
    ),
    'Secretaries' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM users
         WHERE role='secretary'"
    ),
    'Appointments' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM appointments"
    ),
    'Illnesses' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM illnesses"
    ),
    'Drugs' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM drugs"
    ),
    'דוח רפואיs' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM medical_reports"
    ),
    'תוכנית תזונהs' => scalar(
        $conn,
        "SELECT COUNT(DISTINCT patient_id, week_start)
         FROM nutrition_plans"
    ),
    'Weekly Results' => scalar(
        $conn,
        "SELECT COUNT(*)
         FROM weekly_nutrition_reports"
    ),
);

// Group users, doctors, and appointments for the first three charts.
$byRole = grouped(
    $conn,
    "SELECT
         role,
         COUNT(*)
     FROM users
     GROUP BY role
     ORDER BY COUNT(*) DESC"
);
$bySpec = grouped(
    $conn,
    "SELECT
         specialization,
         COUNT(*)
     FROM doctors
     GROUP BY specialization
     ORDER BY COUNT(*) DESC"
);
$byStatus = grouped(
    $conn,
    "SELECT
         status,
         COUNT(*)
     FROM appointments
     GROUP BY status
     ORDER BY COUNT(*) DESC"
);

// Load appointment totals for the six newest months.
$byMonthRaw = grouped(
    $conn,
    "SELECT
         DATE_FORMAT(appointment_date, '%Y-%m'),
         COUNT(*)
     FROM appointments
     GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
     ORDER BY DATE_FORMAT(appointment_date, '%Y-%m') DESC
     LIMIT 6"
);

// Reverse the month order so the chart reads from old to new.
$byMonth = array();
foreach (array_reverse($byMonthRaw, true) as $ym => $c) {
    $byMonth[date('M Y', strtotime($ym . '-01'))] = $c;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>תרשימים וסטטיסטיקה - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">תרשימים וסטטיסטיקה</h1>
    <p class="section-subtitle">תמונת מצב עדכנית של מערכת HealthGuard</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:16px; margin-bottom:30px;">
        <?php foreach ($kpi as $label => $value) { ?>
            <div class="card" style="text-align:center;">
                <div style="font-size:2.2rem; font-weight:700; color:#1c4d80;"><?php echo $value; ?></div>
                <div style="color:#555; margin-top:4px;"><?php echo $label; ?></div>
            </div>
        <?php } ?>
    </div>

    <div class="cards">
        <div class="card">
            <h3>משתמשים לפי תפקיד</h3>
            <?php barChart($byRole, $palette); ?>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>רופאים לפי התמחות</h3>
            <?php barChart($bySpec, $palette); ?>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>תורים לפי מצב</h3>
            <?php barChart($byStatus, $palette); ?>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>תורים בחודש <span style="font-weight:400; color:#777;">(6 חודשים אחרונים)</span></h3>
            <?php barChart($byMonth, $palette); ?>
        </div>
    </div>
</div>

</body>
</html>
<?php
// Close the database after all charts have been printed.
CloseCon($conn);
?>
