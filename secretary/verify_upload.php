<?php

// Start the session and load the shared database functions.
session_start();
include("../db_connection.php");

// Keep this page available only to secretary accounts.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "secretary") {
    header("Location: /HealthGuard/login.php");
    exit();
}
// An upload number is required so the page knows which file to review.
if (!isset($_GET['upload_id'])) {
    header("Location: /HealthGuard/secretary/secretary_dashboard.php");
    exit();
}
// Open the database and prepare the page values.
$conn = OpenCon();
$secretary_user_id = (int)$_SESSION['user_id'];
$upload_id = (int)$_GET['upload_id'];
$message = "";

// Load the selected referral together with the patient's details.
$sqlUpload = "SELECT
                  fdu.*,
                  u.name AS patient_name,
                  u.email AS patient_email
              FROM family_doctor_uploads fdu
              JOIN users u ON fdu.patient_id = u.user_id
              WHERE fdu.upload_id='$upload_id'";
$resultUpload = mysqli_query($conn, $sqlUpload);
$upload = mysqli_fetch_assoc($resultUpload);
// Return to the dashboard when the upload does not exist.
if (!$upload) {
    CloseCon($conn);
    header("Location: /HealthGuard/secretary/secretary_dashboard.php");
    exit();
}
// Save the secretary's decision after either action button is pressed.
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $note = $_POST['secretary_note'] ?? '';

    if ($action === 'verify') {
        // Mark the referral as approved and save who reviewed it.
        $verify_sql = "UPDATE family_doctor_uploads
                       SET
                           secretary_status='verified',
                           secretary_note='$note',
                           verified_by='$secretary_user_id',
                           verified_at=NOW()
                       WHERE upload_id='$upload_id'";
        mysqli_query($conn, $verify_sql);
        $message = "הקובץ אושר. המטופל יכול כעת להזמין תור ללא תשלום.";
    } elseif ($action === 'reject') {
        // Mark the referral as rejected and save the secretary's note.
        $reject_sql = "UPDATE family_doctor_uploads
                       SET
                           secretary_status='rejected',
                           secretary_note='$note',
                           verified_by='$secretary_user_id',
                           verified_at=NOW()
                       WHERE upload_id='$upload_id'";
        mysqli_query($conn, $reject_sql);
        $message = "הקובץ נדחה.";
    }

    // Reload the upload so the page shows the new status immediately.
    $resultUpload = mysqli_query($conn, $sqlUpload);
    $upload = mysqli_fetch_assoc($resultUpload);
}
// Image referrals can be previewed directly on this page.
$ext = strtolower(pathinfo($upload['file_path'], PATHINFO_EXTENSION));
$image_extensions = array(
    'jpg',
    'jpeg',
    'png'
);
$is_image = in_array($ext, $image_extensions);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>בדיקת קובץ מטופל</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">בדיקת הפניית מטופל</h1>
    <p class="section-subtitle">העלאה מספר <?php echo $upload_id; ?></p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/secretary/secretary_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="cards">
        <div class="card">
            <h3>מטופל</h3>
            <p><strong>שם:</strong> <?php echo $upload['patient_name']; ?></p>
            <p><strong>אימייל:</strong> <?php echo $upload['patient_email']; ?></p>
        </div>
        <div class="card">
            <h3>פרטי ההעלאה</h3>
            <p><strong>שם הקובץ המקורי:</strong> <?php echo $upload['original_filename']; ?></p>
            <p><strong>הועלה בתאריך:</strong> <?php echo $upload['uploaded_at']; ?></p>
            <p>
                <a href="/HealthGuard/<?php echo $upload['file_path']; ?>" target="_blank" class="primary-btn"
                   style="display:inline-block; margin-top:8px; font-size:13px; padding:8px 14px; min-height:36px;">
                    פתיחת הקובץ בלשונית חדשה
                </a>
            </p>
        </div>
        <div class="card">
            <h3>מצב נוכחי</h3>
            <?php
            // Choose a clear color for the current review status.
            $s = $upload['secretary_status'];

            if ($s === 'verified') {
                $color = '#16a34a';
            } elseif ($s === 'rejected') {
                $color = '#dc2626';
            } else {
                $color = '#d97706';
            }
            ?>
            <p>
                <strong style="color:<?php echo $color; ?>; font-size:18px;">
                    <?php echo HebrewText($s); ?>
                </strong>
            </p>
            <?php if (!empty($upload['secretary_note'])) { ?>
                <p><strong>הערה:</strong> <?php echo $upload['secretary_note']; ?></p>
            <?php } ?>
            <?php if (!empty($upload['verified_at'])) { ?>
                <p><strong>נבדק בתאריך:</strong> <?php echo $upload['verified_at']; ?></p>
            <?php } ?>
        </div>
    </div>

    <?php if ($is_image) { ?>
        <div class="cards" style="margin-top:20px;">
            <div class="card" style="grid-column:1/-1;">
                <h3>תצוגה מקדימה</h3>
                <img src="/HealthGuard/<?php echo $upload['file_path']; ?>"
                     alt="תצוגה מקדימה של ההפניה"
                     style="max-width:100%; border:1px solid #e2e8f0; border-radius:8px;">
            </div>
        </div>
    <?php } ?>

    <div class="form-container" style="min-height:auto; padding-top:20px;">
        <form class="form-box" method="POST" style="max-width:600px;">
            <h2>החלטת בדיקה</h2>
            <p class="subtitle">אפשר להוסיף הערה ולאחר מכן לאשר או לדחות את הקובץ</p>

            <label>הערת מזכירה</label>
            <textarea name="secretary_note" placeholder="הערה לא חובה..."><?php echo $upload['secretary_note'] ?? ''; ?></textarea>

            <div class="hero-actions" style="justify-content:center; margin-top:18px; gap:12px;">
                <button type="submit" name="action" value="verify"
                        style="background:linear-gradient(135deg,#16a34a,#15803d)!important; color:#fff!important;">
                    אישור הקובץ
                </button>
                <button type="submit" name="action" value="reject"
                        style="background:linear-gradient(135deg,#ef4444,#dc2626)!important; color:#fff!important;">
                    דחיית הקובץ
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the page has finished using it.
CloseCon($conn);
?>
