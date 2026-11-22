<?php
// Start the session and load the functions used for reset links and email.
session_start();
include("db_connection.php");
include("mailer.php");
include("password_reset_helpers.php");

// These values control the messages shown under the reset form.
$message = "";
$local_reset_link = "";

// Handle the email address after the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // Read the email, or use an empty value when it was not sent.
    $email = $_POST['email'] ?? "";

    // Continue only when the visitor entered an email address.
    if ($email != "") {
        // Create a reset token for the matching account.
        $conn = OpenCon();
        $reset = CreatePasswordResetToken($conn, $email);

        // Build and send the reset link when an account was found.
        if ($reset) {
            $reset_link = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                . "/HealthGuard/reset_password.php?token=" . $reset['token'];

            SendPasswordResetEmail(
                $reset['email'],
                $reset['name'],
                $reset_link
            );

            $local_reset_link = $reset_link;
        }

        // Close the database and show the same result for every email.
        CloseCon($conn);
        $message = "אם האימייל קיים, קישור לאיפוס הסיסמה מוכן.";
    } else {
        $message = "נא להזין כתובת אימייל.";
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>שחזור סיסמה - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>שחזור סיסמה</h2>
        <p class="subtitle">הזן את כתובת האימייל לקבלת קישור לאיפוס הסיסמה.</p>

        <?php if ($message !== "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>כתובת אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל" required>

        <button type="submit">שליחת קישור איפוס</button>

        <?php if ($local_reset_link !== "") { ?>
            <div class="local-reset-box">
                <strong>קישור בדיקה מקומי</strong>
                <p>לשימוש מקומי ב־WampServer:</p>
                <a href="<?php echo $local_reset_link; ?>">פתיחת איפוס הסיסמה</a>
            </div>
        <?php } ?>

        <p class="bottom-text">
            נזכרת בסיסמה? <a href="/HealthGuard/login.php">חזרה לכניסה</a>
        </p>
    </form>
</div>
</body>
</html>
