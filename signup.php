<?php

// Start a session for the visitor.
session_start();

// Load the database functions and the shared city list.
include("db_connection.php");
include("app_helpers.php");

// These cities are shown in the signup form.
$cities = HealthGuardCities();

// This message shows a form error or a success result.
$message = "";

// Create an account only after the signup form is submitted.
if (isset($_POST['name'])) {
    // Open the database for the signup process.
    $conn = OpenCon();

    // Read all values entered in the form.
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $city = $_POST['city'] ?? '';

    // Make sure every required value is present and the city is supported.
    if (
        $name == ""
        || $email == ""
        || $password == ""
        || $confirm_password == ""
        || !isset($cities[$city])
    ) {
        $message = "נא למלא את כל השדות";
    } elseif ($password != $confirm_password) {
        $message = "הסיסמאות אינן תואמות";
    } else {

        // Check whether another account already uses this email address.
        $check_sql = "SELECT user_id
                      FROM users
                      WHERE email='$email'";
        $result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($result) > 0) {
            $message = "כתובת האימייל כבר קיימת";
        } else {

            // Hash the password before saving the new patient account.
            $saved_password = password_hash($password, PASSWORD_DEFAULT);

            // Save the main user account.
            $sql = "INSERT INTO users (
                        name,
                        role,
                        email,
                        password,
                        city,
                        created_at
                    )
                    VALUES (
                        '$name',
                        'patient',
                        '$email',
                        '$saved_password',
                        '$city',
                        NOW()
                    )";

            if (mysqli_query($conn, $sql)) {
                // Read the ID created for the new user account.
                $user_id = mysqli_insert_id($conn);

                // Create the matching empty patient profile.
                $patient_sql = "INSERT INTO patients (
                                    user_id,
                                    age,
                                    gender,
                                    medical_notes
                                )
                                VALUES (
                                    '$user_id',
                                    NULL,
                                    NULL,
                                    ''
                                )";
                mysqli_query($conn, $patient_sql);

                $message = "ההרשמה הושלמה בהצלחה";
            } else {
                $message = "שגיאה: " . mysqli_error($conn);
            }
        }
    }

    // Close the database after signup finishes.
    CloseCon($conn);
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הרשמה - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>יצירת חשבון</h2>
        <p class="subtitle">יצירת חשבון HealthGuard חדש</p>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם מלא</label>
        <input type="text" name="name" placeholder="הזנת שם">

        <label>כתובת אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל">

        <label>עיר</label>
        <select name="city" required>
            <option value="">בחירת עיר</option>
            <?php foreach ($cities as $city_name => $coordinates) { ?>
                <option value="<?php echo $city_name; ?>">
                    <?php echo $city_name; ?>
                </option>
            <?php } ?>
        </select>

        <label>סיסמה</label>
        <input type="password" name="password" placeholder="הזנת סיסמה">

        <label>אימות סיסמה</label>
        <input type="password" name="confirm_password" placeholder="אימות סיסמה">

        <button type="submit">הרשמה</button>

        <p class="bottom-text">
            כבר יש לך חשבון? <a href="/HealthGuard/login.php">כניסה</a>
        </p>
    </form>
</div>

</body>
</html>
