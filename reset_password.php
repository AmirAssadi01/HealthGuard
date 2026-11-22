<?php
// Start the session and load the password reset functions.
session_start();
include("db_connection.php");
include("password_reset_helpers.php");

// Read the token from the form first, or from the link on the first visit.
$token = $_POST['token'] ?? $_GET['token'] ?? "";
$message = "";

// Open the database and check whether the token is still valid.
$conn = OpenCon();
$reset = ValidatePasswordResetToken($conn, $token);

// Save a new password only after the reset form is submitted.
if ($_SERVER['REQUEST_METHOD'] === "POST" && $reset) {
    // Read both password fields.
    $new_password = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";

    // Validate the password before saving it.
    if (strlen($new_password) < 8) {
        $message = "הסיסמה חייבת להכיל לפחות 8 תווים.";
    } elseif ($new_password !== $confirm_password) {
        $message = "הסיסמאות אינן תואמות.";
    } else {
        // Save the password and mark the reset token as used.
        $password_saved = ConsumePasswordResetToken(
            $conn,
            (int)$reset['reset_id'],
            (int)$reset['user_id'],
            $new_password
        );

        if ($password_saved) {
            CloseCon($conn);
            header("Location: /HealthGuard/login.php?reset=success");
            exit();
        }

        $message = "קישור האיפוס אינו תקף עוד.";
        $reset = null;
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>איפוס סיסמה - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>בחירת סיסמה חדשה</h2>

        <?php if ($message !== "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <?php if ($reset) { ?>
            <p class="subtitle">
                איפוס הסיסמה עבור <?php echo $reset['email']; ?>.
            </p>
            <input type="hidden" name="token" value="<?php echo $token; ?>">

            <label>סיסמה חדשה</label>
            <input type="password" name="new_password" minlength="8" required>

            <label>אימות סיסמה חדשה</label>
            <input type="password" name="confirm_password" minlength="8" required>

            <button type="submit">שמירת הסיסמה החדשה</button>
        <?php } else { ?>
            <div class="message">קישור האיפוס אינו תקף או שפג תוקפו.</div>
            <a class="primary-btn full-width-btn" href="/HealthGuard/forgot_password.php">
                בקשת קישור חדש
            </a>
        <?php } ?>

        <p class="bottom-text"><a href="/HealthGuard/login.php">חזרה לכניסה</a></p>
    </form>
</div>
</body>
</html>
<?php
// Close the database after the reset page has finished.
CloseCon($conn);
?>
