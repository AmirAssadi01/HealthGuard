<?php

// Start the session and load the database and notification functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a doctor may open and update an appointment report.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Return to the appointment list when no appointment ID was provided.
if (!isset($_GET['appointment_id'])) {
    header("Location: /HealthGuard/doctor/my_appointments.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();


$appointment_id = $_GET['appointment_id'];
$doctor_user_id = $_SESSION['user_id'];
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
    header("Location: /HealthGuard/doctor/doctor_dashboard.php");
    exit();
}

$doctor_id = $doctor['doctor_id'];



// Load the selected appointment only when it belongs to this doctor.
$sqlAppointment = "SELECT
                       appointments.*,
                       users.name AS patient_name,
                       users.email AS patient_email
                   FROM appointments
                   JOIN users
                       ON appointments.patient_id=users.user_id
                   WHERE appointments.appointment_id='$appointment_id'
                   AND appointments.doctor_id='$doctor_id'";
$resultAppointment = mysqli_query($conn, $sqlAppointment);
$appointment = mysqli_fetch_assoc($resultAppointment);

// Return to the list when the appointment does not belong to this doctor.
if (!$appointment) {
    CloseCon($conn);
    header("Location: /HealthGuard/doctor/my_appointments.php");
    exit();
}


// Read the patient ID used by the report and allergy queries.
$patient_id = $appointment['patient_id'];

// Load an existing report so this page can update it instead of duplicating it.
$sqlExistingReport = "SELECT *
                      FROM medical_reports
                      WHERE appointment_id='$appointment_id'";
$resultExistingReport = mysqli_query($conn, $sqlExistingReport);
$existingReport = mysqli_fetch_assoc($resultExistingReport);

// Save the medical report after the form is submitted.
if (isset($_POST['diagnosis'])) {
    // Read the report and follow-up values.
    $diagnosis = $_POST['diagnosis'];
    $examination = $_POST['examination'];
    $treatment_done = $_POST['treatment_done'];
    $doctor_notes = $_POST['doctor_notes'];
    $follow_up_needed = $_POST['follow_up_needed'];
    $follow_up_note = $_POST['follow_up_note'];
    $follow_up_date = $_POST['follow_up_date'];
    $follow_up_time = $_POST['follow_up_time'];

    // Use empty arrays when no medicines were entered.
    $drug_ids = $_POST['drug_id'] ?? array();
    $dosages = $_POST['dosage'] ?? array();
    $frequencies = $_POST['frequency'] ?? array();
    $durations = $_POST['duration'] ?? array();
    $instructions = $_POST['instructions'] ?? array();

    // A report must include a diagnosis.
    if ($diagnosis == "") {
        $message = "חובה להזין אבחנה";
    } else {
        // The warning stays empty unless a prescribed drug matches an allergy.
        $warning = "";

        // Load all saved allergies for this patient.
        $sqlAllergies = "SELECT *
                         FROM patient_allergies
                         WHERE patient_id='$patient_id'";
        $resultAllergies = mysqli_query($conn, $sqlAllergies);

        $allergy_names = array();
        while ($rowAllergy = mysqli_fetch_assoc($resultAllergies)) {
            $allergy_names[] = strtolower($rowAllergy['allergy_name']);
        }

        // Check each selected drug against the patient's allergy list.
        for ($i = 0; $i < count($drug_ids); $i++) {
            $selected_drug_id = $drug_ids[$i];

            if ($selected_drug_id != "") {

                $sqlDrug = "SELECT *
                            FROM drugs
                            WHERE drug_id='$selected_drug_id'";
                $resultDrug = mysqli_query($conn, $sqlDrug);
                $drug = mysqli_fetch_assoc($resultDrug);

                if ($drug) {
                    $drug_name = strtolower($drug['drug_name']);
                    $allergy_group = strtolower($drug['allergy_group']);

                    if (
                        in_array($drug_name, $allergy_names)
                        || (
                            $allergy_group != ""
                            && in_array($allergy_group, $allergy_names)
                        )
                    ) {
                        $warning = "Warning: patient has allergy to selected drug or allergy group.";
                        break;
                    }
                }
            }
        }

        if ($warning != "") {
            $message = $warning;
        } else {

            // Update the current report or create a new report.
            if ($existingReport) {
                $report_id = $existingReport['report_id'];

                $sqlUpdate = "UPDATE medical_reports
                              SET patient_id='$patient_id',
                                  doctor_id='$doctor_id',
                                  diagnosis='$diagnosis',
                                  examination='$examination',
                                  treatment_done='$treatment_done',
                                  doctor_notes='$doctor_notes',
                                  follow_up_needed='$follow_up_needed',
                                  follow_up_note='$follow_up_note',
                                  updated_at=NOW()
                              WHERE report_id='$report_id'";
                mysqli_query($conn, $sqlUpdate);

                // Replace the old medicine rows with the submitted medicine list.
                $delete_drugs_sql = "DELETE FROM report_drugs
                                     WHERE report_id='$report_id'";
                mysqli_query($conn, $delete_drugs_sql);
            } else {
                $sqlInsert = "INSERT INTO medical_reports (
                                  appointment_id,
                                  patient_id,
                                  doctor_id,
                                  diagnosis,
                                  examination,
                                  treatment_done,
                                  doctor_notes,
                                  follow_up_needed,
                                  follow_up_note,
                                  created_at,
                                  updated_at
                              )
                              VALUES (
                                  '$appointment_id',
                                  '$patient_id',
                                  '$doctor_id',
                                  '$diagnosis',
                                  '$examination',
                                  '$treatment_done',
                                  '$doctor_notes',
                                  '$follow_up_needed',
                                  '$follow_up_note',
                                  NOW(),
                                  NOW()
                              )";
                mysqli_query($conn, $sqlInsert);
                $report_id = mysqli_insert_id($conn);
            }

            // Save every complete medicine row from the form.
            for ($i = 0; $i < count($drug_ids); $i++) {
                $selected_drug_id = $drug_ids[$i];
                $dose = $dosages[$i];
                $freq = $frequencies[$i];
                $dur = $durations[$i];
                $instr = $instructions[$i];

                if ($selected_drug_id != "" && $dose != "" && $freq != "" && $dur != "") {
                    $sqlDrugInsert = "INSERT INTO report_drugs (
                                          report_id,
                                          drug_id,
                                          dosage,
                                          frequency,
                                          duration,
                                          instructions,
                                          created_at
                                      )
                                      VALUES (
                                          '$report_id',
                                          '$selected_drug_id',
                                          '$dose',
                                          '$freq',
                                          '$dur',
                                          '$instr',
                                          NOW()
                                      )";
                    mysqli_query($conn, $sqlDrugInsert);
                }
            }

            // Mark the appointment as completed after the report is saved.
            $complete_sql = "UPDATE appointments
                             SET status='completed'
                             WHERE appointment_id='$appointment_id'";
            mysqli_query($conn, $complete_sql);

            // Mark a linked family-doctor referral as used.
            $consume_sql = "UPDATE family_doctor_uploads
                            SET consumed_at=NOW()
                            WHERE linked_appointment_id='$appointment_id'
                            AND consumed_at IS NULL";
            mysqli_query($conn, $consume_sql);

            // Create a follow-up appointment when the doctor requested one.
            if ($follow_up_needed == "yes" && $follow_up_date != "" && $follow_up_time != "") {

                $checkFollow = "SELECT *
                                FROM appointments
                                WHERE doctor_id='$doctor_id'
                                AND appointment_date='$follow_up_date'
                                AND appointment_time='$follow_up_time'";
                $resultFollow = mysqli_query($conn, $checkFollow);

                if (mysqli_num_rows($resultFollow) == 0) {

                    $follow_up_sql = "INSERT INTO appointments (
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
                                          '$follow_up_date',
                                          '$follow_up_time',
                                          'pending',
                                          NOW()
                                      )";
                    mysqli_query($conn, $follow_up_sql);

                    $message = "הדוח נשמר ונוצר תור מעקב.";
                } else {
                    $message = "הדוח נשמר, אך שעת המעקב כבר תפוסה.";
                }
            } else {
                $message = "הדוח נשמר בהצלחה.";
            }

            // Tell the patient that the report and prescription are ready.
            CreateNotification(
                $conn,
                $patient_id,
                "report",
                "נשמרו דוח רפואי ומרשם לאחר התור שלך.",
                "/HealthGuard/patient/my_reports.php",
                "report_" . $report_id
            );

            // Reload the report so the saved values appear immediately.
            $reload_sql = "SELECT *
                           FROM medical_reports
                           WHERE appointment_id='$appointment_id'";
            $resultExistingReport = mysqli_query($conn, $reload_sql);
            $existingReport = mysqli_fetch_assoc($resultExistingReport);
        }
    }
}


// Load allergies for the information box shown above the report form.
$sqlAllergiesView = "SELECT *
                     FROM patient_allergies
                     WHERE patient_id='$patient_id'
                     ORDER BY allergy_name ASC";
$resultAllergiesView = mysqli_query($conn, $sqlAllergiesView);



// Load medicines already connected to an existing report.
$existingDrugs = array();
if ($existingReport) {
    $report_id_now = $existingReport['report_id'];
    $sqlExistingDrugs = "SELECT report_drugs.*, drugs.drug_name
                         FROM report_drugs
                         JOIN drugs
                             ON report_drugs.drug_id=drugs.drug_id
                         WHERE report_drugs.report_id='$report_id_now'";
    $resultExistingDrugs = mysqli_query($conn, $sqlExistingDrugs);

    while ($rowDrug = mysqli_fetch_assoc($resultExistingDrugs)) {
        $existingDrugs[] = $rowDrug;
    }
}


// Load all drugs for the prescription select boxes.
$drugsList = array();
$drugs_sql = "SELECT *
              FROM drugs
              ORDER BY drug_name ASC";
$resultDrugs2 = mysqli_query($conn, $drugs_sql);
while ($rowDrug2 = mysqli_fetch_assoc($resultDrugs2)) {
    $drugsList[] = $rowDrug2;
}


// Always show at least three medicine rows in the form.
$rowsToShow = 3;
if (count($existingDrugs) > 3) {
    $rowsToShow = count($existingDrugs);
}

// Prepare the saved report values so the HTML fields stay easy to read.
$diagnosis_value = "";
$examination_value = "";
$treatment_value = "";
$doctor_notes_value = "";
$follow_up_note_value = "";
$follow_up_no_selected = "";
$follow_up_yes_selected = "";

if ($existingReport) {
    $diagnosis_value = $existingReport['diagnosis'];
    $examination_value = $existingReport['examination'];
    $treatment_value = $existingReport['treatment_done'];
    $doctor_notes_value = $existingReport['doctor_notes'];
    $follow_up_note_value = $existingReport['follow_up_note'];

    if ($existingReport['follow_up_needed'] == 'no') {
        $follow_up_no_selected = "selected";
    }

    if ($existingReport['follow_up_needed'] == 'yes') {
        $follow_up_yes_selected = "selected";
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ניהול תור</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">דוח ביקור</h1>
    <p class="section-subtitle">דוח הרופא לאחר סיום התור</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/my_appointments.php" class="secondary-btn">חזרה לתורים</a>
        <a href="/HealthGuard/doctor/my_reports.php" class="secondary-btn">הדוחות שלי</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="cards">
        <div class="card">
            <h3>פרטי התור</h3>
            <p><strong>מטופל:</strong> <?php echo $appointment['patient_name']; ?></p>
            <p><strong>אימייל:</strong> <?php echo $appointment['patient_email']; ?></p>
            <p><strong>תאריך:</strong> <?php echo $appointment['appointment_date']; ?></p>
            <p><strong>שעה:</strong> <?php echo $appointment['appointment_time']; ?></p>
            <p><strong>מצב:</strong> <?php echo HebrewText($appointment['status']); ?></p>
        </div>

        <div class="card">
            <h3>אלרגיות המטופל</h3>
            <?php if (mysqli_num_rows($resultAllergiesView) > 0) { ?>
                <?php while ($rowAllergyView = mysqli_fetch_assoc($resultAllergiesView)) { ?>
                    <p>
                        <strong><?php echo $rowAllergyView['allergy_name']; ?></strong>
                        <?php if ($rowAllergyView['reaction'] != "") { ?>
                            - <?php echo $rowAllergyView['reaction']; ?>
                        <?php } ?>
                    </p>
                <?php } ?>
            <?php } else { ?>
                <p>המטופל לא שמר אלרגיות.</p>
            <?php } ?>
        </div>
    </div>

    <div class="form-container" style="min-height:auto; padding-top:20px;">
        <form class="form-box" method="POST" style="max-width:1100px;">
            <h2>דוח רפואי</h2>
            <p class="subtitle">תיאור מהלך התור</p>

            <label>אבחנה</label>
            <textarea name="diagnosis"><?php echo $diagnosis_value; ?></textarea>

            <label>בדיקה</label>
            <textarea name="examination"><?php echo $examination_value; ?></textarea>

            <label>טיפול שבוצע</label>
            <textarea name="treatment_done"><?php echo $treatment_value; ?></textarea>

            <label>הערות הרופא</label>
            <textarea name="doctor_notes"><?php echo $doctor_notes_value; ?></textarea>

            <h3 style="margin-top:25px; margin-bottom:15px;">תרופות שנרשמו</h3>

            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>תרופה</th>
                        <th>מינון</th>
                        <th>תדירות</th>
                        <th>משך</th>
                        <th>הוראות</th>
                    </tr>

                    <?php
                    // Print enough medicine rows for the saved prescription.
                    for ($i = 0; $i < $rowsToShow; $i++) {
                        $dosage_value = "";
                        $frequency_value = "";
                        $duration_value = "";
                        $instructions_value = "";

                        if (isset($existingDrugs[$i])) {
                            $dosage_value = $existingDrugs[$i]['dosage'];
                            $frequency_value = $existingDrugs[$i]['frequency'];
                            $duration_value = $existingDrugs[$i]['duration'];
                            $instructions_value = $existingDrugs[$i]['instructions'];
                        }
                    ?>
                    <tr>
                        <td>
                            <select name="drug_id[]">
                                <option value="">בחירת תרופה</option>
                                <?php foreach ($drugsList as $oneDrug) { ?>
                                    <?php
                                    $selected = "";
                                    if (isset($existingDrugs[$i]) && $existingDrugs[$i]['drug_id'] == $oneDrug['drug_id']) {
                                        $selected = "selected";
                                    }
                                    ?>
                                    <option value="<?php echo $oneDrug['drug_id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $oneDrug['drug_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                        <td>
                            <input
                                type="text"
                                name="dosage[]"
                                value="<?php echo $dosage_value; ?>"
                                placeholder="לדוגמה: 500mg"
                            >
                        </td>
                        <td>
                            <input
                                type="text"
                                name="frequency[]"
                                value="<?php echo $frequency_value; ?>"
                                placeholder="לדוגמה: פעמיים ביום"
                            >
                        </td>
                        <td>
                            <input
                                type="text"
                                name="duration[]"
                                value="<?php echo $duration_value; ?>"
                                placeholder="לדוגמה: 7 ימים"
                            >
                        </td>
                        <td>
                            <input
                                type="text"
                                name="instructions[]"
                                value="<?php echo $instructions_value; ?>"
                                placeholder="אחרי אוכל / לפני השינה"
                            >
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>

            <h3 style="margin-top:25px; margin-bottom:15px;">תור מעקב</h3>

            <label>נדרש מעקב?</label>
            <select name="follow_up_needed">
                <option value="no" <?php echo $follow_up_no_selected; ?>>לא</option>
                <option value="yes" <?php echo $follow_up_yes_selected; ?>>כן</option>
            </select>

            <label>הערת מעקב</label>
            <textarea name="follow_up_note"><?php echo $follow_up_note_value; ?></textarea>

            <label>תאריך מעקב</label>
            <input type="date" name="follow_up_date">

            <label>שעת מעקב</label>
            <input type="time" name="follow_up_time">

            <button type="submit">שמירת הדוח</button>
        </form>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the appointment report page has been printed.
CloseCon($conn);
?>
