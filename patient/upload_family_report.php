<?php

// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only a patient may upload or remove their referral files.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();
$patient_id = (int)$_SESSION['user_id'];
$message = "";
$message_type = "";

// Delete an unverified upload after its remove link is pressed.
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Load the upload only when it belongs to this patient.
    $sqlOwn = "SELECT
                   file_path,
                   secretary_status
               FROM family_doctor_uploads
               WHERE upload_id='$delete_id'
               AND patient_id='$patient_id'";
    $rowOwn = mysqli_fetch_assoc(mysqli_query($conn, $sqlOwn));

    if ($rowOwn && $rowOwn['secretary_status'] !== 'verified') {

        // Delete the physical file when it still exists.
        $abs = __DIR__ . '/../' . $rowOwn['file_path'];
        if (is_file($abs)) {
            @unlink($abs);
        }

        // Delete the matching database row.
        $delete_sql = "DELETE FROM family_doctor_uploads
                       WHERE upload_id='$delete_id'
                       AND patient_id='$patient_id'";
        mysqli_query($conn, $delete_sql);
    }

    // Return to the page so refreshing does not repeat the deletion.
    CloseCon($conn);
    header("Location: /HealthGuard/patient/upload_family_report.php");
    exit();
}

// Validate and save a referral after the upload form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['report_file'])) {
    $file = $_FILES['report_file'];

    // These are the supported file types and maximum file size.
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "ההעלאה נכשלה. נא לנסות שוב.";
        $message_type = "error";
    } elseif ($file['size'] > $max_size) {
        $message = "הקובץ גדול מדי. הגודל המרבי הוא 5MB.";
        $message_type = "error";
    } else {

        // Read both the file extension and the detected file type.
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($ext, $allowed_ext) || !in_array($mime, $allowed_mime)) {
            $message = "סוג הקובץ אינו תקין. יש להעלות PDF, JPG או PNG.";
            $message_type = "error";
        } else {

            // Create the upload folder when it does not exist yet.
            $upload_dir = __DIR__ . '/../uploads/family_reports';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Build a unique saved filename and both file paths.
            $safe_name = bin2hex(random_bytes(16)) . '.' . $ext;
            $abs_path = $upload_dir . '/' . $safe_name;
            $rel_path = 'uploads/family_reports/' . $safe_name;

            // Move the temporary upload into the HealthGuard upload folder.
            if (move_uploaded_file($file['tmp_name'], $abs_path)) {
                $original_esc = $file['name'];
                $rel_esc = $rel_path;

                // Save the upload so a secretary can review it.
                $sqlIns = "INSERT INTO family_doctor_uploads (
                               patient_id,
                               file_path,
                               original_filename,
                               uploaded_at,
                               secretary_status
                           )
                           VALUES (
                               '$patient_id',
                               '$rel_esc',
                               '$original_esc',
                               NOW(),
                               'pending'
                           )";
                if (mysqli_query($conn, $sqlIns)) {
                    $message = "ההפניה הועלתה ותיבדק בקרוב על ידי המזכירות.";
                    $message_type = "success";
                } else {
                    @unlink($abs_path);
                    $message = "שגיאת מסד נתונים: " . mysqli_error($conn);
                    $message_type = "error";
                }
            } else {
                $message = "לא ניתן לשמור את הקובץ. נא לנסות שוב.";
                $message_type = "error";
            }
        }
    }
}


// Load the patient's uploads with the newest file first.
$sqlList = "SELECT *
            FROM family_doctor_uploads
            WHERE patient_id='$patient_id'
            ORDER BY uploaded_at DESC";
$resultList = mysqli_query($conn, $sqlList);

// Choose the message box colors before printing the HTML.
$message_style = "";
if ($message_type === 'success') {
    $message_style = "background:#dcfce7; border-color:#86efac; color:#166534;";
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>העלאת הפניית רופא משפחה</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">הפניית רופא משפחה</h1>
    <p class="section-subtitle">העלה את ההפניה מרופא המשפחה. לאחר אישור המזכירות יינתן פטור מתשלום עבור תור אחד למומחה. לאחר השלמת התור יש להעלות הפניה חדשה לקבלת פטור נוסף.</p>

    <div class="cards">
        <div class="card">
            <h3>העלאת הפניה חדשה</h3>

            <?php if (!empty($message)) { ?>
                <div class="message" style="<?php echo $message_style; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">
                <label>קובץ הדוח (PDF, JPG או PNG &mdash; עד 5MB)</label>
                <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" required>
                <button type="submit" style="margin-top:14px;">העלאת הפניה</button>
            </form>
        </div>

        <div class="card">
            <h3>ההפניות שהעליתי</h3>

            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>קובץ</th>
                        <th>הועלה</th>
                        <th>מצב</th>
                        <th>הערה</th>
                        <th>פעולה</th>
                    </tr>
                    <?php if (mysqli_num_rows($resultList) > 0) { ?>
                        <?php
                        // Print one table row for every uploaded referral.
                        while ($row = mysqli_fetch_assoc($resultList)) {
                            $status = $row['secretary_status'];

                            // Give each review status a clear color.
                            if ($status === 'verified') {
                                $color = '#16a34a';
                            } elseif ($status === 'rejected') {
                                $color = '#dc2626';
                            } else {
                                $color = '#d97706';
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="/HealthGuard/<?php echo $row['file_path']; ?>" target="_blank">
                                    <?php echo $row['original_filename']; ?>
                                </a>
                            </td>
                            <td><?php echo $row['uploaded_at']; ?></td>
                            <td>
                                <strong style="color:<?php echo $color; ?>;">
                                    <?php echo HebrewText($status); ?>
                                </strong>
                            </td>
                            <td><?php echo $row['secretary_note'] ?? ''; ?></td>
                            <td>
                                <?php if ($status !== 'verified') { ?>
                                    <a href="/HealthGuard/patient/upload_family_report.php?delete_id=<?php echo $row['upload_id']; ?>"
                                       class="danger-btn"
                                       onclick="return confirm('למחוק את הקובץ?');">מחיקה</a>
                                <?php } else { ?>
                                    <span style="color:#94a3b8;">נעול</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5">עדיין לא הועלו הפניות.</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the page has finished using it.
CloseCon($conn);
?>
