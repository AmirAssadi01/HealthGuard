<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a doctor may edit a doctor schedule.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in doctor.
$conn = OpenCon();


$doctor_user_id = $_SESSION['user_id'];


// This message shows the result of saving the schedule.
$message = "";

$sqlDoctor = "SELECT *
              FROM doctors
              WHERE user_id='$doctor_user_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);

// Stop when the signed-in account has no doctor details row.
if (!$doctor) {
    CloseCon($conn);
    header("Location: /HealthGuard/login.php");
    exit();
}


// Read the doctor ID used by the schedules table.
$doctor_id = $doctor['doctor_id'];

// These are all days that can appear in a weekly schedule.
$days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

// Replace the saved schedule after the form is submitted.
if (isset($_POST['save_schedule'])) {
    // Remove the old rows before inserting the new weekly schedule.
    $delete_sql = "DELETE FROM doctor_schedules
                   WHERE doctor_id='$doctor_id'";
    mysqli_query($conn, $delete_sql);

    // Read and save every active day from the form.
    foreach ($days as $day) {
        $active = 0;

        if (isset($_POST['active'][$day])) {
            $active = 1;
        }

        if (!$active) {
            continue;
        }

        // Read the working hours and appointment length for this day.
        $start = $_POST['start_time'][$day] ?? '';
        $end = $_POST['end_time'][$day] ?? '';
        $slot = (int)($_POST['slot_duration'][$day] ?? 30);

        // Skip a day when its working hours are incomplete or invalid.
        if ($start == '' || $end == '' || $end <= $start) {
            continue;
        }

        // Use 30 minutes when an unsupported appointment length was sent.
        if (!in_array($slot, [15, 30, 45, 60])) {
            $slot = 30;
        }

        // Save this active day in the schedule table.
        $insert_sql = "INSERT INTO doctor_schedules (
                           doctor_id,
                           day_of_week,
                           start_time,
                           end_time,
                           slot_duration
                       )
                       VALUES (
                           '$doctor_id',
                           '$day',
                           '$start',
                           '$end',
                           '$slot'
                       )";
        mysqli_query($conn, $insert_sql);
    }

    $message = "לוח הזמנים נשמר בהצלחה.";
}


// Load the saved schedule and arrange rows by day name.
$sqlSchedule = "SELECT *
                FROM doctor_schedules
                WHERE doctor_id='$doctor_id'";
$resultSchedule = mysqli_query($conn, $sqlSchedule);
$scheduleData = [];
while ($row = mysqli_fetch_assoc($resultSchedule)) {
    $scheduleData[$row['day_of_week']] = $row;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שעות העבודה שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">שעות העבודה שלי</h1>
    <p class="section-subtitle">הגדרת הימים והשעות שבהם מטופלים יכולים לקבוע תור</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="form-container" style="min-height:auto; padding-top:20px;">
        <form class="form-box" method="POST" style="max-width:1000px;">
            <h2>לוח זמנים שבועי</h2>
            <p class="subtitle">סמן יום עבודה והגדר שעת התחלה, שעת סיום ואורך תור.</p>

            <div class="table-wrapper" style="margin-top:20px;">
                <table>
                    <tr>
                        <th>יום</th>
                        <th>פעיל</th>
                        <th>שעת התחלה</th>
                        <th>שעת סיום</th>
                        <th>משך תור</th>
                    </tr>
                    <?php
                    // Print one editable row for every day of the week.
                    foreach ($days as $day) {
                        $s = $scheduleData[$day] ?? null;

                        // Prepare the saved values or the default working hours.
                        $checked = "";
                        $start_time = "09:00";
                        $end_time = "17:00";

                        if ($s) {
                            $checked = "checked";
                            $start_time = $s['start_time'];
                            $end_time = $s['end_time'];
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo HebrewText($day); ?></strong></td>
                        <td>
                            <input
                                type="checkbox"
                                name="active[<?php echo $day; ?>]"
                                value="1"
                                <?php echo $checked; ?>
                            >
                        </td>
                        <td>
                            <input
                                type="time"
                                name="start_time[<?php echo $day; ?>]"
                                value="<?php echo $start_time; ?>"
                            >
                        </td>
                        <td>
                            <input
                                type="time"
                                name="end_time[<?php echo $day; ?>]"
                                value="<?php echo $end_time; ?>"
                            >
                        </td>
                        <td>
                            <select name="slot_duration[<?php echo $day; ?>]">
                                <?php
                                // Print the four supported appointment lengths.
                                foreach ([15, 30, 45, 60] as $m) {
                                    $sel = "";

                                    if ($s && (int)$s['slot_duration'] == $m) {
                                        $sel = "selected";
                                    }
                                ?>
                                    <option value="<?php echo $m; ?>" <?php echo $sel; ?>>
                                        <?php echo $m; ?> min
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>

            <button type="submit" name="save_schedule" style="margin-top:20px;">שמירת לוח הזמנים</button>
        </form>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the schedule page has been printed.
CloseCon($conn);
?>
