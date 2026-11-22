<?php

// Start the session so the login can remember the signed-in user.
session_start();

// Load the shared database functions.
include("db_connection.php");

// Show a success message after a completed password reset.
$message = isset($_GET['reset']) && $_GET['reset'] === "success"
    ? "הסיסמה אופסה בהצלחה. אפשר להתחבר."
    : "";

// Run the login logic only after the login form is submitted.
if (isset($_POST['email'])) {
    // Open the database for this login attempt.
    $conn = OpenCon();

    // Read the email and password entered in the form.
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Make sure both form fields have a value.
    if ($email == "" || $password == "") {
        $message = "נא למלא את כל השדות";
    } else {

        // Find the account that uses this email address.
        $sql = "SELECT *
                FROM users
                WHERE email='$email'";
        $result = mysqli_query($conn, $sql);

        // Continue only when exactly one account was found.
        if (mysqli_num_rows($result) == 1) {
            // Read the account row returned by MySQL.
            $row = mysqli_fetch_assoc($result);

            // Compare the entered password with the saved password hash.
            if (password_verify($password, $row['password'])) {
                // Save the account information in the session.
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['email'] = $row['email'];
                // Each role has its own dashboard page.
                $dashboards = [
                    'admin' => 'admin/admin_dashboard.php',
                    'doctor' => 'doctor/doctor_dashboard.php',
                    'patient' => 'patient/patient_dashboard.php',
                    'secretary' => 'secretary/secretary_dashboard.php'
                ];

                // Send the user to the dashboard for their role.
                header("Location: /HealthGuard/" . ($dashboards[$row['role']] ?? 'index.php'));
                exit();

            } else {
                $message = "סיסמה שגויה";
            }

        } else {
            $message = "כתובת האימייל לא נמצאה";
        }
    }

    // Close the connection after the login attempt finishes.
    CloseCon($conn);
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>כניסה - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>כניסה</h2>
        <p class="subtitle">כניסה לחשבון HealthGuard שלך</p>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>כתובת אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל">

        <label>סיסמה</label>
        <input type="password" name="password" placeholder="הזנת סיסמה">

        <div class="form-link-row">
            <a href="/HealthGuard/forgot_password.php">שכחת סיסמה?</a>
        </div>

        <button type="submit">כניסה</button>

        <p class="bottom-text">
            אין לך חשבון? <a href="/HealthGuard/signup.php">הרשמה</a>
        </p>
    </form>
</div>

</body>
</html>
