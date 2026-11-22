<?php

// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only a patient may open the medication diary.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and identify the signed-in patient.
$conn = OpenCon();
$patient_id = $_SESSION['user_id'];

// Save one taken or skipped medication entry after a button is pressed.
if (isset($_POST['status'])) {
    $drug_id = $_POST['report_drug_id'];
    $status = $_POST['status'];

    $log_sql = "INSERT INTO medication_logs (
                    report_drug_id,
                    patient_id,
                    taken_date,
                    taken_time,
                    status
                )
                VALUES (
                    '$drug_id',
                    '$patient_id',
                    CURDATE(),
                    CURTIME(),
                    '$status'
                )";
    mysqli_query($conn, $log_sql);
}

// Load all medicines prescribed to this patient.
$medications_sql = "SELECT
                        rd.report_drug_id,
                        d.drug_name,
                        rd.dosage,
                        rd.frequency,
                        rd.duration,
                        rd.instructions,
                        u.name AS doctor_name
                    FROM report_drugs AS rd
                    JOIN drugs AS d
                        ON d.drug_id=rd.drug_id
                    JOIN medical_reports AS mr
                        ON mr.report_id=rd.report_id
                    JOIN doctors AS dr
                        ON dr.doctor_id=mr.doctor_id
                    JOIN users AS u
                        ON u.user_id=dr.user_id
                    WHERE mr.patient_id='$patient_id'
                    ORDER BY mr.created_at DESC";
$medications = mysqli_query($conn, $medications_sql);

// Load the newest 20 medication diary entries.
$logs_sql = "SELECT
                 ml.*,
                 d.drug_name
             FROM medication_logs AS ml
             JOIN report_drugs AS rd
                 ON rd.report_drug_id=ml.report_drug_id
             JOIN drugs AS d
                 ON d.drug_id=rd.drug_id
             WHERE ml.patient_id='$patient_id'
             ORDER BY ml.created_at DESC
             LIMIT 20";
$logs = mysqli_query($conn, $logs_sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התרופות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">התרופות שלי</h1>
    <p class="section-subtitle">מרשמים ומעקב נטילת תרופות</p>

    <div class="cards">
        <?php
        // Print one card for every prescribed medicine.
        while ($row = mysqli_fetch_assoc($medications)) {
            $instructions_text = 'אין';

            if (!empty($row['instructions'])) {
                $instructions_text = $row['instructions'];
            }
        ?>
            <div class="card">
                <h3><?php echo $row['drug_name']; ?></h3>
                <p><strong>מינון:</strong> <?php echo $row['dosage']; ?></p>
                <p><strong>תדירות:</strong> <?php echo $row['frequency']; ?></p>
                <p><strong>משך:</strong> <?php echo $row['duration']; ?></p>
                <p><strong>הוראות:</strong> <?php echo $instructions_text; ?></p>
                <p><strong>רופא:</strong> <?php echo $row['doctor_name']; ?></p>

                <form method="POST" class="hero-actions">
                    <input
                        type="hidden"
                        name="report_drug_id"
                        value="<?php echo $row['report_drug_id']; ?>"
                    >
                    <button name="status" value="taken">לקחתי</button>
                    <button name="status" value="skipped" class="secondary-btn">
                        דילגתי
                    </button>
                </form>
            </div>
        <?php } ?>

        <?php if (mysqli_num_rows($medications) == 0) { ?>
            <div class="card">עדיין אין תרופות במרשם.</div>
        <?php } ?>
    </div>

    <h2 class="content-heading">היסטוריה אחרונה</h2>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>תרופה</th>
                <th>תאריך</th>
                <th>שעה</th>
                <th>מצב</th>
            </tr>

            <?php // Print one row for every recent diary entry. ?>
            <?php while ($row = mysqli_fetch_assoc($logs)) { ?>
                <?php
                // Translate the saved diary status for the patient.
                if ($row['status'] == 'taken') {
                    $status_text = 'נלקחה';
                } else {
                    $status_text = 'דולגה';
                }
                ?>
                <tr>
                    <td><?php echo $row['drug_name']; ?></td>
                    <td><?php echo $row['taken_date']; ?></td>
                    <td><?php echo substr($row['taken_time'], 0, 5); ?></td>
                    <td><?php echo $status_text; ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the medication page has been printed.
CloseCon($conn);
?>
