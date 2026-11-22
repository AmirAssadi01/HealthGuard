<?php

// Start the session and load the shared database functions.
session_start();


include("../db_connection.php");

// Only an administrator may create another administrator account.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Create the account after the form is submitted.
if (isset($_POST['name'])) {
    // Open the database and read the form values.
    $conn = OpenCon();

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Every account created on this page has the admin role.
    $role = "admin";

    // Make sure the required fields are not empty.
    if ($name == "" || $email == "" || $password == "") {
        $message = "נא למלא את כל השדות";
    } else {

        // Check whether the email already belongs to another user.
        $check = "SELECT *
                  FROM users
                  WHERE email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $message = "כתובת האימייל כבר קיימת";
        } else {

            // Hash the password before saving the account.
            $password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new administrator into the users table.
            $sql = "INSERT INTO users (
                        name,
                        role,
                        email,
                        password,
                        created_at
                    )
                    VALUES (
                        '$name',
                        '$role',
                        '$email',
                        '$password',
                        NOW()
                    )";

            if (mysqli_query($conn, $sql)) {
                $message = "המנהל נוסף בהצלחה";
            } else {
                $message = "שגיאה: " . mysqli_error($conn);
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
    <title>הוספת מנהל</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת מנהל</h2>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם</label>
        <input type="text" name="name" placeholder="הזנת שם מנהל">

        <label>אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל מנהל">

        <label>סיסמה</label>
        <input type="password" name="password" placeholder="הזנת סיסמה">

        <button type="submit">הוספת מנהל</button>
    </form>
</div>

</body>
</html>
