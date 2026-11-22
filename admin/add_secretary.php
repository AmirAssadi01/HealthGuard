<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may create a secretary account.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Create the secretary after the form is submitted.
if (isset($_POST['name'])) {
    // Open the database and read the submitted values.
    $conn = OpenCon();

    $name     =  $_POST['name'];
    $email    =  $_POST['email'];
    $password =  $_POST['password'];

    // Make sure all required values were entered.
    if ($name == "" || $email == "" || $password == "") {
        $message = "נא למלא את כל השדות";
    } else {

        // Check whether the email is already registered.
        $check = "SELECT user_id
                  FROM users
                  WHERE email='$email'";
        if (mysqli_num_rows(mysqli_query($conn, $check)) > 0) {
            $message = "כתובת האימייל כבר קיימת";
        } else {

            // Hash the password and create the main user account.
            $password = password_hash($password, PASSWORD_DEFAULT);
            $sqlUser = "INSERT INTO users (
                            name,
                            role,
                            email,
                            password,
                            created_at
                        )
                        VALUES (
                            '$name',
                            'secretary',
                            '$email',
                            '$password',
                            NOW()
                        )";

            if (mysqli_query($conn, $sqlUser)) {
                // Create the matching secretary details row.
                $user_id = mysqli_insert_id($conn);
                $sqlSec = "INSERT INTO secretaries (user_id)
                           VALUES ('$user_id')";
                if (mysqli_query($conn, $sqlSec)) {
                    $message = "המזכירה נוספה בהצלחה";
                } else {
                    $message = "שגיאה בהוספת מזכירה: " . mysqli_error($conn);
                }
            } else {
                $message = "שגיאה בהוספת משתמש: " . mysqli_error($conn);
            }
        }
    }

    // Close the database after the form has been handled.
    CloseCon($conn);
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הוספת מזכירה</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת מזכירה</h2>
        <p class="subtitle">יצירת חשבון מזכירות לבדיקת דוחות</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/manage_secretaries.php" class="secondary-btn">חזרה</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם מלא</label>
        <input type="text" name="name" placeholder="הזנת שם מזכירה">

        <label>כתובת אימייל</label>
        <input type="email" name="email" placeholder="Enter email">

        <label>סיסמה</label>
        <input type="password" name="password" placeholder="הזנת סיסמה">

        <button type="submit">הוספת מזכירה</button>
    </form>
</div>

</body>
</html>
