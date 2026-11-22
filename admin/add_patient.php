<?php

// Start the session and load the database and city functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// The city list is used by the patient form.
$cities = HealthGuardCities();

// Only an administrator may create a patient account.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Create the patient after the form is submitted.
if (isset($_POST['name'])) {
    // Open the database and read the submitted values.
    $conn = OpenCon();

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $medical_notes = $_POST['medical_notes'];
    $city = $_POST['city'] ?? '';

    // Every account created here has the patient role.
    $role = "patient";

    // Make sure all required values are present and valid.
    if (
        $name == ""
        || $email == ""
        || $password == ""
        || $age == ""
        || $gender == ""
        || !isset($cities[$city])
    ) {
        $message = "נא למלא את כל שדות החובה";
    } else {

        // Check whether the email is already registered.
        $check = "SELECT *
                  FROM users
                  WHERE email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $message = "כתובת האימייל כבר קיימת";
        } else {

            // Hash the password and create the main user account.
            $password = password_hash($password, PASSWORD_DEFAULT);
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
                        '$role',
                        '$email',
                        '$password',
                        '$city',
                        NOW()
                    )";

            if (mysqli_query($conn, $sql)) {

                // Use the new user ID to create the patient details row.
                $user_id = mysqli_insert_id($conn);

                $sql2 = "INSERT INTO patients (
                             user_id,
                             age,
                             gender,
                             medical_notes
                         )
                         VALUES (
                             '$user_id',
                             '$age',
                             '$gender',
                             '$medical_notes'
                         )";

                if (mysqli_query($conn, $sql2)) {
                    $message = "המטופל נוסף בהצלחה";
                } else {
                    // Remove the user row when the patient details could not be saved.
                    $delete_sql = "DELETE FROM users
                                   WHERE user_id='$user_id'";
                    mysqli_query($conn, $delete_sql);
                    $message = "שגיאה בהוספת פרטי מטופל: " . mysqli_error($conn);
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
    <title>הוספת מטופל</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת מטופל</h2>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם</label>
        <input type="text" name="name" placeholder="הזנת שם מטופל">

        <label>אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל מטופל">

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

        <label>גיל</label>
        <input type="number" name="age" placeholder="הזנת גיל">

        <label>מגדר</label>
        <select name="gender">
            <option value="">בחירת מגדר</option>
            <option value="male">זכר</option>
            <option value="female">נקבה</option>
            <option value="other">אחר</option>
        </select>

        <label>הערות רפואיות</label>
        <textarea name="medical_notes" placeholder="הזנת הערות רפואיות"></textarea>

        <button type="submit">הוספת מטופל</button>
    </form>
</div>

</body>
</html>
