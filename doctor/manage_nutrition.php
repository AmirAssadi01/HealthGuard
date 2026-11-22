<?php
// Start the session and load the database and notification functions.
session_start();
include("../db_connection.php");
include("../app_helpers.php");

// Only a doctor may manage a patient's nutrition plan.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the patient list when no patient ID was provided.
if (!isset($_GET['patient_id'])) {
    header("Location: /HealthGuard/doctor/my_patients.php");
    exit();
}

// Open the database and prepare the page values.
$conn = OpenCon();

$doctor_user_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'];
$message = "";

// Load the doctor row connected to the signed-in user.
$sqlDoctor = "SELECT *
              FROM doctors
              WHERE user_id='$doctor_user_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);

// Stop when the signed-in account has no doctor row.
if (!$doctor) {
    CloseCon($conn);
    header("Location: /HealthGuard/login.php");
    exit();
}

// Read the doctor ID and check whether this doctor is a dietitian.
$doctor_id = $doctor['doctor_id'];

$is_dietitian = (strtolower($doctor['specialization']) === 'dietitian');

// Load the selected patient account.
$sqlPatient = "SELECT *
               FROM users
               WHERE user_id='$patient_id'
               AND role='patient'";
$resultPatient = mysqli_query($conn, $sqlPatient);
$patient = mysqli_fetch_assoc($resultPatient);

// Return to the patient list when the selected patient does not exist.
if (!$patient) {
    CloseCon($conn);
    header("Location: /HealthGuard/doctor/my_patients.php");
    exit();
}


// Load the patient's newest diagnosis for the information box.
$sqlLatestReport = "SELECT diagnosis
                    FROM medical_reports
                    WHERE patient_id='$patient_id'
                    ORDER BY created_at DESC
                    LIMIT 1";
$resultLatestReport = mysqli_query($conn, $sqlLatestReport);
$latestReport = mysqli_fetch_assoc($resultLatestReport);
if ($latestReport) {
    $latest_diagnosis = $latestReport['diagnosis'];
} else {
    $latest_diagnosis = "עדיין אין אבחנה";
}

// Load the patient's allergies in alphabetical order.
$sqlAllergies = "SELECT *
                 FROM patient_allergies
                 WHERE patient_id='$patient_id'
                 ORDER BY allergy_name ASC";
$resultAllergies = mysqli_query($conn, $sqlAllergies);


// Use the submitted week, the URL week, or today's date.
$week_start = $_POST['week_start']
    ?? ($_GET['week_start'] ?? date('Y-m-d'));

// Every plan contains one row for each day of the week.
$days = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

// Load the days that already exist for the selected week.
$existing = array();
$existing_sql = "SELECT day_name
                 FROM nutrition_plans
                 WHERE patient_id='$patient_id'
                 AND week_start='$week_start'";
$resExisting = mysqli_query($conn, $existing_sql);
while ($rowExisting = mysqli_fetch_assoc($resExisting)) {
    $existing[$rowExisting['day_name']] = true;
}


// Load all saved week start dates for this patient.
$planStarts = array();
$starts_sql = "SELECT week_start
               FROM nutrition_plans
               WHERE patient_id='$patient_id'
               GROUP BY week_start
               ORDER BY week_start ASC";
$resStarts = mysqli_query($conn, $starts_sql);
while ($rowStart = mysqli_fetch_assoc($resStarts)) {
    $planStarts[] = $rowStart['week_start'];
}

// Save meals or medical advice after the form is submitted.
if (isset($_POST['save_plan'])) {

    // Look for another plan that overlaps the selected seven-day period.
    $overlap_with = null;

    if (!in_array($week_start, $planStarts)) {
        foreach ($planStarts as $s) {
            $diff = abs(round((strtotime($week_start) - strtotime($s)) / 86400));

            if ($diff <= 6) {
                $overlap_with = $s;
                break;
            }
        }
    }

    // Do not save when the selected week overlaps another plan.
    if ($overlap_with !== null) {
        $message = "That start date is inside the plan week beginning "
                 . date('M j, Y', strtotime($overlap_with))
                 . ". פתיחת השבוע to edit it, or pick a date at least 7 days clear of existing plans.";
    } elseif ($is_dietitian) {
        // A dietitian edits meals and calorie goals.
        foreach ($days as $day) {
            // Read this day's meals and calorie goal.
            $breakfast = $_POST['breakfast'][$day] ?? '';
            $lunch = $_POST['lunch'][$day] ?? '';
            $dinner = $_POST['dinner'][$day] ?? '';
            $snack = $_POST['snack'][$day] ?? '';
            $calories_goal = $_POST['calories_goal'][$day] ?? '';

            if ($calories_goal === "") {
                $calories_sql = "NULL";
            } else {
                $calories_sql = "'$calories_goal'";
            }

            // Update an existing day or insert a new day.
            if (isset($existing[$day])) {
                $update_sql = "UPDATE nutrition_plans
                               SET
                                   breakfast='$breakfast',
                                   lunch='$lunch',
                                   dinner='$dinner',
                                   snack='$snack',
                                   calories_goal=$calories_sql,
                                   updated_at=NOW()
                               WHERE patient_id='$patient_id'
                               AND week_start='$week_start'
                               AND day_name='$day'";
                mysqli_query($conn, $update_sql);
            } else {
                $insert_sql = "INSERT INTO nutrition_plans (
                                   patient_id,
                                   doctor_id,
                                   week_start,
                                   day_name,
                                   breakfast,
                                   lunch,
                                   dinner,
                                   snack,
                                   calories_goal,
                                   illness_advice,
                                   created_at,
                                   updated_at
                               )
                               VALUES (
                                   '$patient_id',
                                   '$doctor_id',
                                   '$week_start',
                                   '$day',
                                   '$breakfast',
                                   '$lunch',
                                   '$dinner',
                                   '$snack',
                                   $calories_sql,
                                   '',
                                   NOW(),
                                   NOW()
                               )";
                mysqli_query($conn, $insert_sql);
            }
        }

        // Add the new week to the local list used by the page.
        if (!in_array($week_start, $planStarts)) {
            $planStarts[] = $week_start;
            sort($planStarts);
        }

        $message = "תוכנית הארוחות השבועית נשמרה בהצלחה.";
        CreateNotification(
            $conn,
            $patient_id,
            "nutrition",
            "נשמרה תוכנית תזונה שבועית לשבוע שמתחיל ב־$week_start.",
            "/HealthGuard/patient/my_nutrition_plan.php?week_start=$week_start",
            "nutrition_plan_" . $week_start
        );
    } else {
        // Other doctors edit only illness-related nutrition advice.
        foreach ($days as $day) {
            $illness_advice = $_POST['illness_advice'][$day] ?? '';

            // Update an existing day or insert a new day.
            if (isset($existing[$day])) {
                $update_sql = "UPDATE nutrition_plans
                               SET
                                   illness_advice='$illness_advice',
                                   updated_at=NOW()
                               WHERE patient_id='$patient_id'
                               AND week_start='$week_start'
                               AND day_name='$day'";
                mysqli_query($conn, $update_sql);
            } else {
                $insert_sql = "INSERT INTO nutrition_plans (
                                   patient_id,
                                   doctor_id,
                                   week_start,
                                   day_name,
                                   breakfast,
                                   lunch,
                                   dinner,
                                   snack,
                                   calories_goal,
                                   illness_advice,
                                   created_at,
                                   updated_at
                               )
                               VALUES (
                                   '$patient_id',
                                   '$doctor_id',
                                   '$week_start',
                                   '$day',
                                   '',
                                   '',
                                   '',
                                   '',
                                   NULL,
                                   '$illness_advice',
                                   NOW(),
                                   NOW()
                               )";
                mysqli_query($conn, $insert_sql);
            }
        }

        // Add the new week to the local list used by the page.
        if (!in_array($week_start, $planStarts)) {
            $planStarts[] = $week_start;
            sort($planStarts);
        }

        $message = "ההמלצה התזונתית נשמרה בהצלחה.";
        CreateNotification(
            $conn,
            $patient_id,
            "nutrition",
            "הרופא הוסיף המלצה בריאותית לשבוע שמתחיל ב־$week_start.",
            "/HealthGuard/patient/my_nutrition_plan.php?week_start=$week_start",
            "nutrition_advice_" . $week_start
        );
    }
}


// Load the seven plan rows for the selected week in weekday order.
$sqlPlan = "SELECT *
            FROM nutrition_plans
            WHERE patient_id='$patient_id'
            AND week_start='$week_start'
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
$resultPlan = mysqli_query($conn, $sqlPlan);

$planData = array();
while ($rowPlan = mysqli_fetch_assoc($resultPlan)) {
    $planData[$rowPlan['day_name']] = $rowPlan;
}


// Lock meal fields for regular doctors and advice fields for dietitians.
$readonly_style = 'style="background:#eee; color:#666; cursor:not-allowed;"';

if ($is_dietitian) {
    $meal_lock = "";
    $advice_lock = "readonly " . $readonly_style;
} else {
    $meal_lock = "readonly " . $readonly_style;
    $advice_lock = "";
}

// Prepare labels and state for the selected seven-day period.
$week_end_label = date('M j, Y', strtotime($week_start . ' +6 days'));
$current_is_existing_start = in_array($week_start, $planStarts);

// Find the saved week that locks this date when the date is inside another plan.
$locked_by = null;
if (!$current_is_existing_start) {
    foreach ($planStarts as $s) {
        $diff = round((strtotime($week_start) - strtotime($s)) / 86400);
        if ($diff >= 1 && $diff <= 6) {
            $locked_by = $s;
            break;
        }
    }
}



// Show buttons for the eight newest plans.
$chipStarts = array_slice(array_reverse($planStarts), 0, 8);
if ($current_is_existing_start && !in_array($week_start, $chipStarts)) {
    $chipStarts[] = $week_start;
}


// Load the newest weekly result submitted by the patient for this plan.
$weeklyReport = null;
$weekly_report_sql = "SELECT *
                      FROM weekly_nutrition_reports
                      WHERE patient_id='$patient_id'
                      AND week_start='$week_start'
                      ORDER BY created_at DESC
                      LIMIT 1";
$resWR = mysqli_query($conn, $weekly_report_sql);

if ($resWR) {
    $weeklyReport = mysqli_fetch_assoc($resWR);
}

// Prepare role-specific text before printing the page.
$page_description = "הוספת המלצה תזונתית בהתאם למחלה";
$role_instructions = "באפשרותך לערוך רק את ההמלצה בהתאם למחלה. תוכנית הארוחות של הדיאטן מוצגת לקריאה בלבד.";

if ($is_dietitian) {
    $page_description = "יצירה ועדכון של תוכנית הארוחות השבועית למטופל";
    $role_instructions = "כדיאטן, באפשרותך לנהל את הארוחות השבועיות. המלצות הרופאים מוצגות לקריאה בלבד.";
}

// Prepare readable values for the optional weekly report.
$report_start_weight = '&mdash;';
$report_end_weight = '&mdash;';
$report_weight_change = '&mdash;';
$report_waist = '&mdash;';

if ($weeklyReport) {
    if ($weeklyReport['start_weight'] !== null && $weeklyReport['start_weight'] !== '') {
        $report_start_weight = $weeklyReport['start_weight'] . ' kg';
    }

    if ($weeklyReport['end_weight'] !== null && $weeklyReport['end_weight'] !== '') {
        $report_end_weight = $weeklyReport['end_weight'] . ' kg';
    }

    if ($weeklyReport['weight_change'] !== null && $weeklyReport['weight_change'] !== '') {
        $report_weight_change = $weeklyReport['weight_change'] . ' kg';
    }

    if ($weeklyReport['waist_cm'] !== null && $weeklyReport['waist_cm'] !== '') {
        $report_waist = $weeklyReport['waist_cm'] . ' cm';
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול תוכנית תזונה</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">תוכנית תזונה שבועית</h1>
    <p class="section-subtitle">
        <?php echo $page_description; ?>
    </p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/my_patients.php" class="secondary-btn">חזרה למטופלים</a>
    </div>

    <div class="message" style="background:#eef6ff; color:#1c4d80;">
        <?php echo $role_instructions; ?>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="cards">
        <div class="card">
            <h3>פרטי המטופל</h3>
            <p><strong>שם:</strong> <?php echo $patient['name']; ?></p>
            <p><strong>אימייל:</strong> <?php echo $patient['email']; ?></p>
            <p><strong>אבחנה אחרונה:</strong> <?php echo $latest_diagnosis; ?></p>

            <h4 style="margin-top:15px;">אלרגיות</h4>
            <?php if (mysqli_num_rows($resultAllergies) > 0) { ?>
                <?php while ($rowAllergy = mysqli_fetch_assoc($resultAllergies)) { ?>
                    <p>
                        <strong><?php echo $rowAllergy['allergy_name']; ?></strong>
                        <?php if (!empty($rowAllergy['reaction'])) { ?>
                            - <?php echo $rowAllergy['reaction']; ?>
                        <?php } ?>
                    </p>
                <?php } ?>
            <?php } else { ?>
                <p>לא נמצאו אלרגיות.</p>
            <?php } ?>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>שבוע התוכנית</h3>

            <p>
                <strong>מוצג:</strong>
                <?php echo date('M j', strtotime($week_start)) . ' &ndash; ' . $week_end_label; ?>
                <?php if ($current_is_existing_start) { ?>
                    <span style="color:#1c7c34;">(תחילת תוכנית קיימת)</span>
                <?php } elseif ($locked_by) { ?>
                    <span style="color:#b00020;">(נעול &ndash; בתוך שבוע של תוכנית אחרת)</span>
                <?php } else { ?>
                    <span style="color:#777;">(שבוע חדש / ריק)</span>
                <?php } ?>
            </p>

            <?php if ($locked_by) { ?>
                <div class="message" style="background:#fff3cd; color:#7a5b00;">
                    תאריך זה נמצא בתוך שבוע תוכנית שמתחיל בתאריך
                    <strong><?php echo date('M j, Y', strtotime($locked_by)); ?></strong>.
                    <a href="?patient_id=<?php echo urlencode($patient_id); ?>&week_start=<?php echo urlencode($locked_by); ?>">פתיחת השבוע</a>
                    לעריכה. השמירה כאן חסומה כדי למנוע חפיפה בין תוכניות.
                </div>
            <?php } ?>

            <form method="GET" style="margin-top:10px;">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <label>פתיחת שבוע לפי תאריך ההתחלה</label>
                <input type="date" name="week_start" value="<?php echo $week_start; ?>">
                <button type="submit" class="secondary-btn">פתיחת שבוע</button>
            </form>

            <h4 style="margin-top:18px;">תוכניות אחרונות</h4>
            <?php if (count($chipStarts) > 0) { ?>
                <div class="hero-actions" style="justify-content:flex-start; flex-wrap:wrap; gap:8px;">
                    <?php
                    // Print a shortcut button for each recent plan.
                    foreach ($chipStarts as $s) {
                        $chip_label = date('M j', strtotime($s)) . ' &ndash; ' . date('M j', strtotime($s . ' +6 days'));
                        $chip_class = 'secondary-btn';

                        if ($s === $week_start) {
                            $chip_class = 'primary-btn';
                        }
                    ?>
                        <a
                            href="?patient_id=<?php echo urlencode($patient_id); ?>&week_start=<?php echo urlencode($s); ?>"
                            class="<?php echo $chip_class; ?>"
                        >
                            <?php echo $chip_label; ?>
                        </a>
                    <?php } ?>
                </div>
                <p class="subtitle" style="margin-top:8px;">
                    מוצגות <?php echo count($chipStarts); ?> מתוך
                    <?php echo count($planStarts); ?> התוכניות האחרונות.
                    כל כפתור מייצג תחילת תוכנית, והימים שבין התוכניות נעולים
                    כדי למנוע חפיפה.
                </p>
            <?php } else { ?>
                <p>עדיין לא נוצרו תוכניות. בחר תאריך התחלה כדי ליצור את הראשונה.</p>
            <?php } ?>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>הדוח שהמטופל שלח</h3>
            <p class="subtitle">לשבוע <?php echo date('M j', strtotime($week_start)) . ' &ndash; ' . $week_end_label; ?></p>
            <?php if ($weeklyReport) { ?>
                <p><strong>משקל התחלתי:</strong> <?php echo $report_start_weight; ?></p>
                <p><strong>משקל סופי:</strong> <?php echo $report_end_weight; ?></p>
                <p><strong>שינוי במשקל:</strong> <?php echo $report_weight_change; ?></p>
                <p><strong>היקף:</strong> <?php echo $report_waist; ?></p>
                <?php if (!empty($weeklyReport['report_text'])) { ?>
                    <p><strong>הערות המטופל:</strong> <?php echo nl2br($weeklyReport['report_text']); ?></p>
                <?php } ?>
                <p class="subtitle" style="margin-top:8px;">נשלח בתאריך <?php echo $weeklyReport['created_at']; ?></p>
            <?php } else { ?>
                <p>המטופל עדיין לא שלח דוח לשבוע זה.</p>
            <?php } ?>
        </div>
    </div>

    <div class="form-container" style="min-height:auto; padding-top:20px;">
        <form class="form-box" method="POST" style="max-width:1200px;">
            <h2>פרטי התוכנית</h2>
            <p class="subtitle">
                <?php echo $is_dietitian
                    ? "יצירת ארוחות לכל ימות השבוע"
                    : "הוספת המלצה בהתאם למחלה לכל ימות השבוע"; ?>
            </p>

            <p><strong>עריכת השבוע:</strong>
               <?php echo date('M j', strtotime($week_start)) . ' &ndash; ' . $week_end_label; ?>
               &mdash; אפשר לשנות אותו באזור <em>שבוע התוכנית</em> שמופיע למעלה.</p>
            <input type="hidden" name="week_start" value="<?php echo $week_start; ?>">

            <div class="table-wrapper" style="margin-top:20px;">
                <table>
                    <tr>
                        <th>יום</th>
                        <th>ארוחת בוקר</th>
                        <th>ארוחת צהריים</th>
                        <th>ארוחת ערב</th>
                        <th>ארוחת ביניים</th>
                        <th>יעד קלוריות</th>
                        <th>המלצה בהתאם למחלה</th>
                    </tr>

                    <?php foreach ($days as $day) { ?>
                    <tr>
                        <td><strong><?php echo HebrewText($day); ?></strong></td>
                        <td>
                            <textarea
                                name="breakfast[<?php echo $day; ?>]"
                                placeholder="ארוחת בוקר"
                                <?php echo $meal_lock; ?>
                            ><?php echo $planData[$day]['breakfast'] ?? ""; ?></textarea>
                        </td>
                        <td>
                            <textarea
                                name="lunch[<?php echo $day; ?>]"
                                placeholder="ארוחת צהריים"
                                <?php echo $meal_lock; ?>
                            ><?php echo $planData[$day]['lunch'] ?? ""; ?></textarea>
                        </td>
                        <td>
                            <textarea
                                name="dinner[<?php echo $day; ?>]"
                                placeholder="ארוחת ערב"
                                <?php echo $meal_lock; ?>
                            ><?php echo $planData[$day]['dinner'] ?? ""; ?></textarea>
                        </td>
                        <td>
                            <textarea
                                name="snack[<?php echo $day; ?>]"
                                placeholder="ארוחת ביניים"
                                <?php echo $meal_lock; ?>
                            ><?php echo $planData[$day]['snack'] ?? ""; ?></textarea>
                        </td>
                        <td>
                            <input
                                type="number"
                                name="calories_goal[<?php echo $day; ?>]"
                                value="<?php echo $planData[$day]['calories_goal'] ?? ''; ?>"
                                placeholder="קלוריות"
                                <?php echo $meal_lock; ?>
                            >
                        </td>
                        <td>
                            <textarea
                                name="illness_advice[<?php echo $day; ?>]"
                                placeholder="המלצה בהתאם למחלה"
                                <?php echo $advice_lock; ?>
                            ><?php echo $planData[$day]['illness_advice'] ?? ""; ?></textarea>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php
            // Use a button label that matches the doctor's editable fields.
            if ($is_dietitian) {
                $save_label = "שמירת התוכנית השבועית";
            } else {
                $save_label = "שמירת ההמלצה";
            }
            ?>
            <button type="submit" name="save_plan">
                <?php echo $save_label; ?>
            </button>
        </form>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the nutrition page has been printed.
CloseCon($conn);
?>
