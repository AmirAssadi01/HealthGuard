<?php

// Start the session and load the shared functions used on this page.
session_start();
include("db_connection.php");
include("app_helpers.php");

// Only signed-in users may open their notifications.
if (!isset($_SESSION['user_id'])) {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and identify the signed-in user.
$conn = OpenCon();
$user_id = (int)$_SESSION['user_id'];

// Create any reminders that are due before showing the list.
GenerateAutomaticNotifications($conn, $user_id, $_SESSION['role']);

// Mark one notification as read when its Read link is pressed.
if (isset($_GET['read'])) {
    $notification_id = (int)$_GET['read'];
    $read_sql = "UPDATE notifications
                 SET is_read=1
                 WHERE notification_id='$notification_id'
                 AND user_id='$user_id'";
    mysqli_query($conn, $read_sql);
}

// Mark all notifications as read when the form button is pressed.
if (isset($_POST['read_all'])) {
    $read_all_sql = "UPDATE notifications
                     SET is_read=1
                     WHERE user_id='$user_id'";
    mysqli_query($conn, $read_all_sql);
}

// Load this user's notifications with the newest notification first.
$notifications_sql = "SELECT *
                      FROM notifications
                      WHERE user_id='$user_id'
                      ORDER BY created_at DESC";
$result = mysqli_query($conn, $notifications_sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>התראות</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>
<?php include("navbar.php"); ?>
<div class="section">
    <h1 class="section-title">התראות</h1>
    <p class="section-subtitle">הודעות, תורים, תרופות ותזכורות בריאות</p>
    <form method="POST" style="text-align:center; margin-bottom:20px;">
        <button class="secondary-btn" name="read_all">סימון הכול כנקרא</button>
    </form>
    <div class="feature-list">
        <?php
        // Print every notification and highlight unread ones.
        while ($row = mysqli_fetch_assoc($result)) {
            $notification_class = "";

            if (!$row['is_read']) {
                $notification_class = "feature-unread";
            }
        ?>
            <div class="feature-row <?php echo $notification_class; ?>">
                <div>
                    <strong><?php echo HebrewText($row['type']); ?></strong>
                    <p><?php echo $row['message']; ?></p>
                    <small><?php echo $row['created_at']; ?></small>
                </div>
                <div>
                    <?php if ($row['link']) { ?>
                        <a class="primary-btn" href="<?php echo $row['link']; ?>">פתיחה</a>
                    <?php } ?>
                    <?php if (!$row['is_read']) { ?>
                        <a class="secondary-btn" href="?read=<?php echo $row['notification_id']; ?>">נקרא</a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
        <?php if (mysqli_num_rows($result) == 0) { ?>
            <p>עדיין אין התראות.</p>
        <?php } ?>
    </div>
</div>
</body>
</html>
<?php
// Close the database after the page has finished reading notifications.
CloseCon($conn);
?>
