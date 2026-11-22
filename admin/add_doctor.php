<?php

// Start the session and load the shared database and city functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// The city list is used by the doctor form.
$cities = HealthGuardCities();

// Only an administrator may create a doctor account.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Create the doctor after the form is submitted.
if (isset($_POST['name'])) {
    // Open the database and read all form values.
    $conn = OpenCon();

    $name             =  $_POST['name'];
    $email            =  $_POST['email'];
    $password         =  $_POST['password'];
    $specialization   =  $_POST['specialization'];
    $city             = $_POST['city'] ?? '';
    $consultation_fee = $_POST['consultation_fee'] ?? '';
    // Save an empty or invalid fee as SQL NULL.
    if ($consultation_fee === '' || !is_numeric($consultation_fee)) {
        $fee_sql = "NULL";
    } else {
        $fee_sql = "'$consultation_fee'";
    }

    // Every account created on this page has the doctor role.
    $role = "doctor";

    // Make sure every required value is valid.
    if (
        $name == ""
        || $email == ""
        || $password == ""
        || $specialization == ""
        || !isset($cities[$city])
    ) {
        $message = "נא למלא את כל השדות";
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
            $sqlUser = "INSERT INTO users (
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

            if (mysqli_query($conn, $sqlUser)) {

                // Use the new user ID to create the doctor's details row.
                $user_id = mysqli_insert_id($conn);

                $sqlDoctor = "INSERT INTO doctors (
                                  user_id,
                                  specialization,
                                  consultation_fee
                              )
                              VALUES (
                                  '$user_id',
                                  '$specialization',
                                  $fee_sql
                              )";

                if (mysqli_query($conn, $sqlDoctor)) {
                    $message = "הרופא נוסף בהצלחה";
                } else {
                    $message = "שגיאה בהוספת רופא: " . mysqli_error($conn);
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
    <title>הוספת רופא</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת רופא</h2>
        <p class="subtitle">יצירת חשבון לרופא</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/admin_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם הרופא</label>
        <input type="text" name="name" placeholder="הזנת שם רופא">

        <label>כתובת אימייל</label>
        <input type="email" name="email" placeholder="הזנת אימייל רופא">

        <label>סיסמה</label>
        <input type="password" name="password" placeholder="הזנת סיסמה">

        <label>התמחות</label>
        <select name="specialization">
            <option value="">בחירת התמחות</option>
            <option value="cardiologist">קרדיולוג</option>
            <option value="dermatologist">רופא עור</option>
            <option value="ophthalmologist">רופא עיניים</option>
            <option value="orthopedic">אורתופד</option>
            <option value="dentist">רופא שיניים</option>
            <option value="dietitian">דיאטן</option>
            <option value="family_doctor">רופא משפחה</option>
        </select>

        <label>עיר</label>
        <select name="city" required>
            <option value="">בחירת עיר</option>
            <?php foreach ($cities as $city_name => $coordinates) { ?>
                <option value="<?php echo $city_name; ?>">
                    <?php echo $city_name; ?>
                </option>
            <?php } ?>
        </select>

        <label>מחיר ייעוץ (להשאיר ריק אם חינם)</label>
        <input type="number" step="0.01" min="0" name="consultation_fee" placeholder="e.g. 50.00">

        <button type="submit">הוספת רופא</button>
    </form>
</div>

</body>
</html>
