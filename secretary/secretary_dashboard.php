<?php

// Start the session and load the shared database functions.
session_start();
include("../db_connection.php");

// Keep this dashboard available only to secretary accounts.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "secretary") {
    header("Location: /HealthGuard/login.php");
    exit();
}
// Open the database and read the signed-in secretary's name.
$conn = OpenCon();
$secretary_name = $_SESSION['name'];

// Load all patient referrals, with pending referrals shown first.
$sqlUploads = "SELECT
                   fdu.*,
                   u.name AS patient_name,
                   u.email AS patient_email
               FROM family_doctor_uploads fdu
               JOIN users u ON fdu.patient_id = u.user_id
               ORDER BY
                   FIELD(
                       fdu.secretary_status,
                       'pending',
                       'rejected',
                       'verified'
                   ),
                   fdu.uploaded_at DESC";
$resultUploads = mysqli_query($conn, $sqlUploads);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>לוח מזכירות</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">לוח מזכירות</h1>
    <p class="section-subtitle">ברוכים הבאים, <?php echo $secretary_name; ?> — בדיקת הפניות רופא משפחה שהמטופלים העלו</p>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>מזהה העלאה</th>
                <th>מטופל</th>
                <th>קובץ</th>
                <th>הועלה</th>
                <th>מצב</th>
                <th>פעולה</th>
            </tr>
            <?php if (mysqli_num_rows($resultUploads) > 0) { ?>
                <?php
                // Print one table row for each referral.
                while ($row = mysqli_fetch_assoc($resultUploads)) {
                    $status = $row['secretary_status'];

                    // Use a different text color for each review result.
                    if ($status === 'verified') {
                        $badge = 'color:#16a34a; font-weight:700;';
                    } elseif ($status === 'rejected') {
                        $badge = 'color:#dc2626; font-weight:700;';
                    } else {
                        $badge = 'color:#d97706; font-weight:700;';
                    }
                ?>
                <tr>
                    <td><?php echo $row['upload_id']; ?></td>
                    <td><?php echo $row['patient_name']; ?></td>
                    <td>
                        <a href="/HealthGuard/<?php echo $row['file_path']; ?>" target="_blank">
                            <?php echo $row['original_filename']; ?>
                        </a>
                    </td>
                    <td><?php echo $row['uploaded_at']; ?></td>
                    <td>
                        <span style="<?php echo $badge; ?>">
                            <?php echo HebrewText($status); ?>
                        </span>
                    </td>
                    <td>
                        <a href="/HealthGuard/secretary/verify_upload.php?upload_id=<?php echo $row['upload_id']; ?>"
                           class="primary-btn" style="font-size:13px; padding:8px 14px; min-height:36px;">
                            בדיקה
                        </a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6">אין הפניות מטופלים שממתינות לבדיקה.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the page has finished using it.
CloseCon($conn);
?>
