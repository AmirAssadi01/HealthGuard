<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a patient may view their medical reports.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in patient.
$conn = OpenCon();


$patient_id = $_SESSION['user_id'];


// Load reports with their appointment and doctor information.
$sql = "SELECT
            medical_reports.*,
            appointments.appointment_date,
            appointments.appointment_time,
            users.name AS doctor_name,
            users.email AS doctor_email,
            doctors.specialization
        FROM medical_reports
        JOIN appointments
            ON medical_reports.appointment_id=appointments.appointment_id
        JOIN doctors
            ON medical_reports.doctor_id=doctors.doctor_id
        JOIN users
            ON doctors.user_id=users.user_id
        WHERE medical_reports.patient_id='$patient_id'
        ORDER BY medical_reports.created_at DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הדוחות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">הדוחות שלי</h1>
    <p class="section-subtitle">דוחות רופא לאחר סיום התורים</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <div class="cards">
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="card">
                    <h3>Dr. <?php echo $row['doctor_name']; ?></h3>
                    <p><strong>אימייל:</strong> <?php echo $row['doctor_email']; ?></p>
                    <p><strong>התמחות:</strong> <?php echo HebrewText($row['specialization']); ?></p>
                    <p><strong>תור:</strong> <?php echo $row['appointment_date']; ?> - <?php echo $row['appointment_time']; ?></p>
                    <p><strong>אבחנה:</strong> <?php echo nl2br($row['diagnosis']); ?></p>
                    <p><strong>בדיקה:</strong> <?php echo nl2br($row['examination']); ?></p>
                    <p><strong>טיפול שבוצע:</strong> <?php echo nl2br($row['treatment_done']); ?></p>
                    <p><strong>הערות הרופא:</strong> <?php echo nl2br($row['doctor_notes']); ?></p>
                    <p><strong>נדרש מעקב:</strong> <?php echo $row['follow_up_needed']; ?></p>
                    <p><strong>הערת מעקב:</strong> <?php echo nl2br($row['follow_up_note']); ?></p>

                    <div class="table-wrapper" style="margin-top:18px;">
                        <table>
                            <tr>
                                <th>תרופה</th>
                                <th>מינון</th>
                                <th>תדירות</th>
                                <th>משך</th>
                                <th>הוראות</th>
                            </tr>

                            <?php
                            // Load the medicines connected to the current report.
                            $report_id = $row['report_id'];
                            $sqlDrugs = "SELECT
                                             report_drugs.*,
                                             drugs.drug_name
                                         FROM report_drugs
                                         JOIN drugs
                                             ON report_drugs.drug_id=drugs.drug_id
                                         WHERE report_drugs.report_id='$report_id'";
                            $resultDrugs = mysqli_query($conn, $sqlDrugs);
                            ?>

                            <?php if (mysqli_num_rows($resultDrugs) > 0) { ?>
                                <?php while ($rowDrug = mysqli_fetch_assoc($resultDrugs)) { ?>
                                <tr>
                                    <td><?php echo $rowDrug['drug_name']; ?></td>
                                    <td><?php echo $rowDrug['dosage']; ?></td>
                                    <td><?php echo $rowDrug['frequency']; ?></td>
                                    <td><?php echo $rowDrug['duration']; ?></td>
                                    <td><?php echo $rowDrug['instructions']; ?></td>
                                </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="5">לא נרשמו תרופות.</td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card">
                <h3>עדיין אין דוחות</h3>
                <p>עדיין לא נוצר דוח רפואי עבור התורים שלך.</p>
            </div>
        <?php } ?>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the report cards have been printed.
CloseCon($conn);
?>
