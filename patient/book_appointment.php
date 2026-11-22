<?php

// Start the session and load the database and notification functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a patient may book an appointment.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to doctor search when no doctor ID was provided.
if (!isset($_GET['doctor_id'])) {
    header("Location: /HealthGuard/patient/find_doctor.php");
    exit();
}


// Open the database and prepare the booking values.
$conn = OpenCon();


$patient_id = $_SESSION['user_id'];
$doctor_id = (int)$_GET['doctor_id'];
$message = "";

// Load the selected doctor's account and booking information.
$sqlDoctor = "SELECT
                  doctors.doctor_id,
                  doctors.user_id,
                  doctors.consultation_fee,
                  users.name,
                  users.email,
                  doctors.specialization
              FROM doctors
              JOIN users
                  ON doctors.user_id=users.user_id
              WHERE doctors.doctor_id='$doctor_id'";
$resultDoctor = mysqli_query($conn, $sqlDoctor);
$doctor = mysqli_fetch_assoc($resultDoctor);

// Return to search when the doctor does not exist.
if (!$doctor) {
    CloseCon($conn);
    header("Location: /HealthGuard/patient/find_doctor.php");
    exit();
}


// Check whether a verified referral can cover this consultation.
$sqlFreeCheck = "SELECT 1
                 FROM family_doctor_uploads
                 WHERE patient_id='$patient_id'
                 AND secretary_status='verified'
                 AND linked_appointment_id IS NULL
                 AND consumed_at IS NULL
                 LIMIT 1";
$has_verified_report = mysqli_num_rows(mysqli_query($conn, $sqlFreeCheck)) > 0;


// A fee needs confirmation only when no unused verified referral exists.
$fee = $doctor['consultation_fee'];
$fee_required = !$has_verified_report
    && $fee !== null
    && (float)$fee > 0;

// These values control the selected date and available time slots.
$today = date('Y-m-d');
$selected_date = '';
$schedule = null;
$available_slots = [];
$date_error = '';

// Build the time slots after the patient selects a date.
if (isset($_GET['date']) && $_GET['date'] != '') {
    $selected_date = $_GET['date'];

    if ($selected_date < $today) {
        $date_error    = "אי אפשר להזמין תאריך שעבר. נא לבחור היום או תאריך עתידי.";
        $selected_date = '';
    } else {

        // Convert the selected date to the weekday saved in doctor schedules.
        $day_of_week = date('l', strtotime($selected_date));

        $sqlSchedule = "SELECT *
                        FROM doctor_schedules
                        WHERE doctor_id='$doctor_id'
                        AND day_of_week='$day_of_week'";
        $schedule = mysqli_fetch_assoc(mysqli_query($conn, $sqlSchedule));

        if ($schedule) {
            // Convert the working hours to timestamps for slot generation.
            $ts_start = strtotime($selected_date . ' ' . $schedule['start_time']);
            $ts_end = strtotime($selected_date . ' ' . $schedule['end_time']);
            $duration = (int)$schedule['slot_duration'] * 60;

            // Load times that are already booked on this date.
            $sqlBooked = "SELECT appointment_time
                          FROM appointments
                          WHERE doctor_id='$doctor_id'
                          AND appointment_date='$selected_date'
                          AND status != 'cancelled'";
            $resultBooked = mysqli_query($conn, $sqlBooked);
            $booked = [];
            while ($rb = mysqli_fetch_assoc($resultBooked)) {
                $booked[] = $rb['appointment_time'];
            }

            // Add only unbooked times to the available slot list.
            for ($t = $ts_start; $t < $ts_end; $t += $duration) {
                $slot_24 = date('H:i:s', $t);
                if (!in_array($slot_24, $booked)) {
                    $available_slots[] = date('H:i', $t);
                }
            }
        }
    }
}

// Save the appointment after the patient selects a time.
if (isset($_POST['appointment_time'])) {

    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    if (isset($_POST['fee_ack'])) {
        $fee_ack = 1;
    } else {
        $fee_ack = 0;
    }

    // Validate the selected date, time, and fee confirmation.
    if ($appointment_date == '' || $appointment_time == '') {
        $message = "נא לבחור תאריך ושעה תקינים.";
    } elseif ($fee_required && !$fee_ack) {
        $message = "נא לאשר את מחיר הייעוץ כדי להמשיך.";
    } else {

        // Check again because another patient may have just taken the slot.
        $check = "SELECT appointment_id
                  FROM appointments
                  WHERE doctor_id='$doctor_id'
                  AND appointment_date='$appointment_date'
                  AND appointment_time='$appointment_time:00'
                  AND status != 'cancelled'";
        if (mysqli_num_rows(mysqli_query($conn, $check)) > 0) {
            $message = "השעה הזו נתפסה. נא לבחור שעה אחרת.";
        } else {

            // Save the new pending appointment.
            $sql = "INSERT INTO appointments (
                        patient_id,
                        doctor_id,
                        appointment_date,
                        appointment_time,
                        status,
                        created_at
                    )
                    VALUES (
                        '$patient_id',
                        '$doctor_id',
                        '$appointment_date',
                        '$appointment_time:00',
                        'pending',
                        NOW()
                    )";

            if (mysqli_query($conn, $sql)) {
                $new_appt_id = mysqli_insert_id($conn);

                // Link one unused verified referral to a paid appointment.
                if ($has_verified_report && $fee !== null && (float)$fee > 0) {
                    mysqli_query($conn, "UPDATE family_doctor_uploads
                                         SET linked_appointment_id='$new_appt_id'
                                         WHERE patient_id='$patient_id'
                                         AND secretary_status='verified'
                                         AND linked_appointment_id IS NULL
                                         AND consumed_at IS NULL
                                         ORDER BY verified_at DESC
                                         LIMIT 1");
                }

                // Notify the doctor about the new booking.
                CreateNotification(
                    $conn,
                    $doctor['user_id'],
                    "appointment",
                    $_SESSION['name'] . " הזמין תור לתאריך $appointment_date בשעה $appointment_time.",
                    "/HealthGuard/doctor/my_appointments.php",
                    "appointment_booked_$new_appt_id"
                );

                // Notify the patient that the booking was saved.
                CreateNotification(
                    $conn,
                    $patient_id,
                    "appointment",
                    "נקבע תור לתאריך $appointment_date בשעה $appointment_time.",
                    "/HealthGuard/patient/my_appointments.php",
                    "appointment_booked_patient_$new_appt_id"
                );

                $message = "התור נקבע בהצלחה לתאריך $appointment_date בשעה $appointment_time.";
                $selected_date = '';
                $available_slots = [];
            } else {
                $message = "שגיאה בהזמנת התור: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הזמנת תור</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="booking-shell">
    <div class="booking-left">
        <span class="patient-badge">תור</span>
        <h1>הזמנת תור</h1>
        <p>בחר תאריך זמין ולאחר מכן שעה מתאימה.</p>
        <div class="booking-help">
            <h3>לפני ההזמנה</h3>
            <p>יוצגו שעות רק בתאריכים שבהם הרופא זמין. אי אפשר להזמין תאריך שעבר.</p>
        </div>
    </div>

    <div class="booking-right">
        <div class="form-box booking-form">
            <h2>פרטי התור</h2>
            <p class="subtitle">השלמת בקשת התור</p>

            <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
                <a href="/HealthGuard/patient/find_doctor.php" class="secondary-btn">חזרה</a>
                <a href="/HealthGuard/patient/my_appointments.php" class="secondary-btn">התורים שלי</a>
            </div>

            <?php if (!empty($message)) { ?>
                <div class="message"><?php echo $message; ?></div>
            <?php } ?>

            <label>שם הרופא</label>
            <input type="text" value="Dr. <?php echo $doctor['name']; ?>" readonly>

            <label>אימייל</label>
            <input type="text" value="<?php echo $doctor['email']; ?>" readonly>

            <label>התמחות</label>
            <input type="text" value="<?php echo HebrewText($doctor['specialization']); ?>" readonly>

            <?php if ($fee_required) { ?>
                <div class="fee-notice">
                    <strong>מחיר הייעוץ: $<?php echo number_format((float)$fee, 2); ?></strong><br>
                    נדרש תשלום מכיוון שאין הפניית רופא משפחה מאושרת.<br>
                    <a href="/HealthGuard/patient/upload_family_report.php"
                       style="color:#92400e; font-weight:600; text-decoration:underline;">
                        יש לך הפניית רופא משפחה? אפשר להעלות אותה לקבלת פטור.
                    </a>
                </div>
            <?php } elseif ($has_verified_report) { ?>
                <div class="free-notice">
                    <strong>ייעוץ ללא תשלום</strong> — הפניית רופא המשפחה שאושרה פוטרת מתשלום.
                </div>
            <?php } elseif ($fee === null || (float)$fee == 0) { ?>
                <div class="free-notice">
                    <strong>ייעוץ ללא תשלום</strong> — רופא זה אינו גובה תשלום.
                </div>
            <?php } ?>

            <form method="GET" action="" style="margin-bottom:0;">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                <label>בחירת תאריך</label>
                <input type="date" name="date"
                       value="<?php echo $selected_date; ?>"
                       min="<?php echo $today; ?>">
                <button type="submit" style="margin-top:10px;">בדיקת שעות זמינות</button>
            </form>

            <?php if (!empty($date_error)) { ?>
                <div class="message" style="margin-top:12px;"><?php echo $date_error; ?></div>
            <?php } ?>

            <?php if ($selected_date && !$schedule) { ?>
                <div class="message" style="margin-top:12px; background:#fff7ed; border-color:#fed7aa; color:#92400e;">
                    לד״ר <?php echo $doctor['name']; ?> אין שעות עבודה ביום
                    <?php echo HebrewText(date('l', strtotime($selected_date))); ?>. נא לבחור תאריך אחר.
                </div>
            <?php } ?>

            <?php if ($selected_date && $schedule && count($available_slots) == 0) { ?>
                <div class="message" style="margin-top:12px; background:#fff7ed; border-color:#fed7aa; color:#92400e;">
                    כל השעות בתאריך <?php echo $selected_date; ?> תפוסות. נא לבחור תאריך אחר.
                </div>
            <?php } ?>

            <?php if ($selected_date && $schedule && count($available_slots) > 0) { ?>
                <form method="POST" style="margin-top:18px;">
                    <input type="hidden" name="appointment_date" value="<?php echo $selected_date; ?>">

                    <label>שעות זמינות בתאריך <?php echo $selected_date; ?>
                        (<?php echo date('l', strtotime($selected_date)); ?>)</label>

                    <div class="slot-grid">
                        <?php foreach ($available_slots as $slot) { ?>
                            <input
                                type="radio"
                                class="slot-radio"
                                id="slot_<?php echo $slot; ?>"
                                name="appointment_time"
                                value="<?php echo $slot; ?>"
                                required
                            >
                            <label for="slot_<?php echo $slot; ?>" class="slot-label">
                                <?php echo date('g:i A', strtotime($slot)); ?>
                            </label>
                        <?php } ?>
                    </div>

                    <?php if ($fee_required) { ?>
                        <div style="margin:14px 0; display:flex; align-items:flex-start; gap:10px;">
                            <input type="checkbox" name="fee_ack" id="fee_ack" style="margin-top:3px; width:18px; height:18px;">
                            <label for="fee_ack" style="font-size:14px; color:#92400e; cursor:pointer;">
                                ידוע לי שמחיר הייעוץ בסך
                                <strong>$<?php echo number_format((float)$fee, 2); ?></strong>
                                חל על תור זה.
                            </label>
                        </div>
                    <?php } ?>

                    <button type="submit">אישור התור</button>
                </form>
            <?php } ?>
        </div>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the page has finished using it.
CloseCon($conn);
?>
