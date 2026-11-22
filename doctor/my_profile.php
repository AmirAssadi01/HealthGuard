<?php

// Start the session and load the database and city functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// The city list is shown in the profile form.
$cities = HealthGuardCities();

// Only a doctor may open this profile page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();


$user_id = $_SESSION['user_id'];
$message = "";

// Save the selected city after the form is submitted.
if (isset($_POST['city']) && isset($cities[$_POST['city']])) {
    $city = $_POST['city'];
    $update_sql = "UPDATE users
                   SET city='$city'
                   WHERE user_id='$user_id'";
    mysqli_query($conn, $update_sql);
    $message = "העיר עודכנה בהצלחה";
}

// Load the user and doctor details for the profile form.
$sql = "SELECT
            users.name,
            users.email,
            users.city,
            doctors.specialization,
            doctors.consultation_fee
        FROM users
        INNER JOIN doctors
            ON users.user_id=doctors.user_id
        WHERE users.user_id='$user_id'
        AND users.role='doctor'";

$result = mysqli_query($conn, $sql);
$doctor = mysqli_fetch_assoc($result);


// Use empty values when a profile field has not been saved yet.
$doctor_name = $doctor['name'] ?? '';
$doctor_email = $doctor['email'] ?? '';
$doctor_specialization = $doctor['specialization'] ?? '';
$doctor_fee = $doctor['consultation_fee'] ?? null;
$doctor_city = $doctor['city'] ?? '';

// Show a friendly value when the doctor does not charge a consultation fee.
$doctor_fee_text = 'Free';

if ($doctor_fee !== null) {
    $doctor_fee_text = '$' . number_format(
        (float)$doctor_fee,
        2
    );
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הפרופיל שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="doctor-page">

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הפרופיל שלי</h2>
        <p class="subtitle">פרטי חשבון הרופא</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if ($message) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <?php if ($doctor) { ?>

            <label>שם הרופא</label>
            <input type="text" value="<?php echo $doctor_name; ?>" readonly>

            <label>אימייל</label>
            <input type="text" value="<?php echo $doctor_email; ?>" readonly>

            <label>התמחות</label>
            <input type="text" value="<?php echo $doctor_specialization; ?>" readonly>

            <label>עיר</label>
            <select name="city" required>
                <option value="">בחירת עיר</option>
                <?php foreach ($cities as $city_name => $coordinates) { ?>
                    <?php
                    // Select the doctor's currently saved city.
                    $selected = '';

                    if ($doctor_city === $city_name) {
                        $selected = 'selected';
                    }
                    ?>
                    <option value="<?php echo $city_name; ?>" <?php echo $selected; ?>>
                        <?php echo $city_name; ?>
                    </option>
                <?php } ?>
            </select>

            <label>מחיר ייעוץ</label>
            <input
                type="text"
                value="<?php echo $doctor_fee_text; ?>"
                readonly
            >

            <button type="submit">שמירת העיר</button>

        <?php } else { ?>
            <div class="message">פרופיל הרופא לא נמצא.</div>
        <?php } ?>
    </form>
</div>

</body>
</html>
<?php
// Close the database after the profile has been printed.
CloseCon($conn);
?>
