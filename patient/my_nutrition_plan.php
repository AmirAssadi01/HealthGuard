<?php

// Start the session and load the shared database functions.
session_start();
include("../db_connection.php");

// Keep this page available only to patient accounts.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}
// Open the database and identify the signed-in patient.
$conn = OpenCon();
$patient_id = $_SESSION['user_id'];

// Load all weeks that have a nutrition plan, newest week first.
$sqlWeeks = "SELECT DISTINCT
                 week_start
             FROM nutrition_plans
             WHERE patient_id='$patient_id'
             ORDER BY week_start DESC";
$resultWeeks = mysqli_query($conn, $sqlWeeks);
$all_weeks = array();

while ($wRow = mysqli_fetch_assoc($resultWeeks)) {
    $all_weeks[] = $wRow['week_start'];
}

// Use the requested week, or show the newest available week by default.
if (isset($_GET['week_start']) && $_GET['week_start'] != '') {
    $week_start = $_GET['week_start'];
} elseif (!empty($all_weeks)) {
    $week_start = $all_weeks[0];
} else {
    $week_start = '';
}

$result = null;

// Load the daily meals and doctor details for the selected week.
if ($week_start != '') {
    $sql = "SELECT
                nutrition_plans.*,
                users.name AS doctor_name,
                users.email AS doctor_email
            FROM nutrition_plans
            JOIN doctors ON nutrition_plans.doctor_id = doctors.doctor_id
            JOIN users ON doctors.user_id = users.user_id
            WHERE nutrition_plans.patient_id='$patient_id'
            AND nutrition_plans.week_start='$week_start'
            ORDER BY FIELD(
                day_name,
                'Sunday',
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday'
            )";
    $result = mysqli_query($conn, $sql);
}

// Prepare values used by the weekly progress report form.
$weeklyReport = null;
$plan_doctor_id = null;
$weekEnded = false;
$reportMessage = "";
$reportError = "";

// Load the patient's current measurements for the report starting values.
$sqlProfile = "SELECT
                   current_weight,
                   waist_cm
               FROM patients
               WHERE user_id='$patient_id'";
$rowProfile = mysqli_fetch_assoc(mysqli_query($conn, $sqlProfile));
$current_weight = $rowProfile['current_weight'] ?? null;
$current_waist = $rowProfile['waist_cm'] ?? null;

// Load an existing weekly report and the doctor who created the plan.
if ($week_start != '') {
    $sqlWR = "SELECT *
              FROM weekly_nutrition_reports
              WHERE patient_id='$patient_id'
              AND week_start='$week_start'
              LIMIT 1";
    $weeklyReport = mysqli_fetch_assoc(mysqli_query($conn, $sqlWR));

    $sqlPD = "SELECT
                  doctor_id
              FROM nutrition_plans
              WHERE patient_id='$patient_id'
              AND week_start='$week_start'
              LIMIT 1";
    $rowPD = mysqli_fetch_assoc(mysqli_query($conn, $sqlPD));

    if ($rowPD) {
        $plan_doctor_id = $rowPD['doctor_id'];
    }

    // The report becomes available after all seven days have passed.
    $weekEnded = (strtotime($week_start) + (7 * 86400)) <= time();
}

// Validate and save the patient's report at the end of the selected week.
if (isset($_POST['save_report']) && $week_start != '') {
    if (!$weekEnded) {
        $reportError = "אפשר לשלוח את הדוח רק לאחר סיום השבוע בתאריך "
            . date('M j, Y', strtotime($week_start . ' +7 days'))
            . ".";
    } elseif ($weeklyReport) {
        $reportError = "כבר שלחת דוח לשבוע זה.";
    } else {
        // Read the final measurements and optional notes from the form.
        $end_weight = $_POST['end_weight'] ?? '';
        $end_waist = $_POST['end_waist'] ?? '';
        $report_text = $_POST['report_text'] ?? '';

        if ($end_weight === '' || $end_waist === '') {
            $reportError = "נא להזין משקל והיקף בסוף השבוע.";
        } else {
            // Calculate the patient's weight change for this week.
            $end_weight_num = (float)$end_weight;
            $end_waist_num = (float)$end_waist;
            $start_weight = $current_weight;
            $weight_change = null;

            if ($start_weight !== null) {
                $weight_change = round(
                    $end_weight_num - (float)$start_weight,
                    2
                );
            }

            // Use SQL NULL when an optional number is not available.
            $start_weight_sql = "NULL";
            if ($start_weight !== null) {
                $start_weight_sql = "'$start_weight'";
            }

            $weight_change_sql = "NULL";
            if ($weight_change !== null) {
                $weight_change_sql = "'$weight_change'";
            }

            $doctor_sql = "NULL";
            if ($plan_doctor_id !== null) {
                $doctor_sql = "'$plan_doctor_id'";
            }

            // Save the completed weekly report.
            $insert_report_sql = "INSERT INTO weekly_nutrition_reports (
                                      patient_id,
                                      doctor_id,
                                      week_start,
                                      start_weight,
                                      end_weight,
                                      weight_change,
                                      waist_cm,
                                      report_text,
                                      created_at
                                  )
                                  VALUES (
                                      '$patient_id',
                                      $doctor_sql,
                                      '$week_start',
                                      $start_weight_sql,
                                      '$end_weight_num',
                                      $weight_change_sql,
                                      '$end_waist_num',
                                      '$report_text',
                                      NOW()
                                  )";
            $ok = mysqli_query($conn, $insert_report_sql);

            if ($ok) {
                // Make the final measurements the patient's new current values.
                $update_patient_sql = "UPDATE patients
                                       SET
                                           current_weight='$end_weight_num',
                                           waist_cm='$end_waist_num'
                                       WHERE user_id='$patient_id'";
                mysqli_query($conn, $update_patient_sql);

                $current_weight = $end_weight_num;
                $current_waist = $end_waist_num;

                // Reload the saved report so it appears immediately.
                $weeklyReport = mysqli_fetch_assoc(
                    mysqli_query($conn, $sqlWR)
                );
                $reportMessage = "הדוח השבועי נשלח ונתוני המשקל וההיקף עודכנו.";
            } else {
                $reportError = "השמירה נכשלה: " . mysqli_error($conn);
            }
        }
    }
}

// Prepare form attributes once so the HTML stays simple.
$report_field_state = "";
$report_button_state = "";

if (!$weekEnded) {
    $report_field_state = "disabled";
    $report_button_state = 'disabled style="opacity:0.5; cursor:not-allowed;"';
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>תוכנית התזונה שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">תוכנית התזונה השבועית שלי</h1>
    <p class="section-subtitle">תוכנית הארוחות שהרופא הכין ומתעדכנת לאחר כל שינוי</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <?php if (empty($all_weeks)) { ?>
        <div class="card" style="max-width:600px; margin:0 auto;">
            <h3>עדיין אין תוכנית תזונה</h3>
            <p>הרופא עדיין לא הוסיף תוכנית תזונה שבועית. היא תופיע כאן לאחר השמירה.</p>
        </div>
    <?php } else { ?>

        <div style="max-width:300px; margin:0 auto 24px;">
            <label style="font-weight:700; display:block; margin-bottom:8px;">בחירת שבוע</label>
            <select
                onchange="window.location='/HealthGuard/patient/my_nutrition_plan.php?week_start='+this.value"
                style="width:100%; padding:12px; border-radius:12px; border:1px solid #dbe7f3; font-size:15px;"
            >
                <?php foreach ($all_weeks as $w) { ?>
                    <?php
                    // Mark the week currently shown on the page.
                    $selected_week = "";
                    if ($w == $week_start) {
                        $selected_week = "selected";
                    }
                    ?>
                    <option
                        value="<?php echo $w; ?>"
                        <?php echo $selected_week; ?>
                    >
                        השבוע שמתחיל ב־ <?php echo date('M j, Y', strtotime($w)); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>יום</th>
                        <th>ארוחת בוקר</th>
                        <th>ארוחת צהריים</th>
                        <th>ארוחת ערב</th>
                        <th>ארוחת ביניים</th>
                        <th>יעד קלוריות</th>
                        <th>המלצה בהתאם למחלה</th>
                        <th>רופא</th>
                    </tr>
                    <?php
                    // Print the plan for one day in each row.
                    while ($row = mysqli_fetch_assoc($result)) {
                        $calories_text = '—';

                        if (($row['calories_goal'] ?? '') != '') {
                            $calories_text = (string)$row['calories_goal'] . ' kcal';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo HebrewText($row['day_name'] ?? ''); ?></strong></td>
                        <td><?php echo nl2br($row['breakfast'] ?? ''); ?></td>
                        <td><?php echo nl2br($row['lunch'] ?? ''); ?></td>
                        <td><?php echo nl2br($row['dinner'] ?? ''); ?></td>
                        <td><?php echo nl2br($row['snack'] ?? ''); ?></td>
                        <td><?php echo $calories_text; ?></td>
                        <td><?php echo nl2br($row['illness_advice'] ?? ''); ?></td>
                        <td>
                            Dr. <?php echo $row['doctor_name'] ?? ''; ?><br>
                            <small><?php echo $row['doctor_email'] ?? ''; ?></small>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>

            <div class="form-container" style="min-height:auto; padding-top:20px;">
                <form class="form-box" method="POST" style="max-width:600px;">
                    <h2>דוח תזונה שבועי</h2>
                    <p class="subtitle">השבוע שמתחיל ב־ <?php echo date('M j, Y', strtotime($week_start)); ?></p>

                    <?php if (!empty($reportMessage)) { ?>
                        <div class="message"><?php echo $reportMessage; ?></div>
                    <?php } ?>
                    <?php if (!empty($reportError)) { ?>
                        <div class="message" style="background:#ffe5e5; color:#c0392b; border-color:#c0392b;">
                            <?php echo $reportError; ?>
                        </div>
                    <?php } ?>

                    <?php if ($weeklyReport) { ?>
                        <p style="color:#16a34a; font-weight:600;">הדוח השבועי נשלח.</p>

                        <label>משקל התחלתי (KG)</label>
                        <input
                            type="text"
                            value="<?php echo $weeklyReport['start_weight'] ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>משקל סופי (KG)</label>
                        <input
                            type="text"
                            value="<?php echo $weeklyReport['end_weight'] ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>שינוי במשקל (KG)</label>
                        <input
                            type="text"
                            value="<?php echo $weeklyReport['weight_change'] ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>היקף סופי (CM)</label>
                        <input
                            type="text"
                            value="<?php echo $weeklyReport['waist_cm'] ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>הערות</label>
                        <textarea readonly style="background:#f5f5f5;"><?php echo $weeklyReport['report_text'] ?? ''; ?></textarea>
                    <?php } else { ?>
                        <input type="hidden" name="week_start" value="<?php echo $week_start; ?>">

                        <label>משקל בתחילת השבוע (ק״ג)</label>
                        <input
                            type="text"
                            value="<?php echo $current_weight ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>היקף בתחילת השבוע (ס״מ)</label>
                        <input
                            type="text"
                            value="<?php echo $current_waist ?? '—'; ?>"
                            readonly
                            style="background:#f5f5f5;"
                        >

                        <label>משקל סופי (KG)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="1"
                            name="end_weight"
                            placeholder="המשקל שלך בסוף השבוע"
                            <?php echo $report_field_state; ?>
                            required
                        >

                        <label>היקף סופי (CM)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="1"
                            name="end_waist"
                            placeholder="ההיקף שלך בסוף השבוע"
                            <?php echo $report_field_state; ?>
                            required
                        >

                        <label>הערות</label>
                        <textarea
                            name="report_text"
                            placeholder="איך עבר השבוע?"
                            <?php echo $report_field_state; ?>
                        ></textarea>

                        <?php if (!$weekEnded) { ?>
                            <p style="color:#e67e22; font-size:0.9em; margin-top:8px;">
                                &#9888; אפשר לשלוח את הדוח רק לאחר סיום השבוע בתאריך
                                <strong><?php echo date('M j, Y', strtotime($week_start . ' +7 days')); ?></strong>.
                            </p>
                        <?php } ?>

                        <button
                            type="submit"
                            name="save_report"
                            <?php echo $report_button_state; ?>
                        >
                            שליחת הדוח
                        </button>
                    <?php } ?>
                </form>
            </div>
        <?php } else { ?>
            <div class="card" style="max-width:600px; margin:0 auto;">
                <h3>אין תוכנית לשבוע זה</h3>
                <p>הרופא לא שמר תוכנית תזונה לשבוע שמתחיל ב־ <?php echo date('M j, Y', strtotime($week_start)); ?>.</p>
            </div>
        <?php } ?>

    <?php } ?>
</div>

</body>
</html>
<?php
// Close the database after the page has finished using it.
CloseCon($conn);
?>
