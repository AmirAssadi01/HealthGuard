<?php

// Start the session and load the database and city functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a patient may search for a doctor.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and load the patient's city.
$conn = OpenCon();

$user_id = (int)$_SESSION['user_id'];
$city_sql = "SELECT city
             FROM users
             WHERE user_id='$user_id'";
$city_result = mysqli_query($conn, $city_sql);
$patient_city = mysqli_fetch_assoc($city_result)['city'] ?? '';

// Load illnesses in alphabetical order for the search select box.
$sql = "SELECT *
        FROM illnesses
        ORDER BY illness_name ASC";


$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חיפוש רופא</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST" action="/HealthGuard/patient/show_doctors.php">
        <h2>חיפוש רופא</h2>
        <p class="subtitle">מציאת רופאים מתאימים עד 50 ק״מ מהעיר שלך</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if ($patient_city) { ?>
            <div class="message">חיפוש בקרבת <?php echo $patient_city; ?></div>
        <?php } else { ?>
            <div class="message">יש להגדיר את העיר ב־ <a href="/HealthGuard/patient/my_profile.php">הפרופיל שלי</a> לפני החיפוש.</div>
        <?php } ?>

        <label>בחירת מחלה</label>
        <select name="illness_id" required>
            <option value="">בחירת מחלה</option>

            <?php
            // Print one option for every illness.
            while ($row = mysqli_fetch_assoc($result)) {
            ?>
                <option value="<?php echo $row['illness_id']; ?>">
                    <?php echo $row['illness_name']; ?>
                </option>
            <?php } ?>

        </select>

        <?php
        // Disable searching until the patient has saved a city.
        $disabled = "disabled";

        if ($patient_city) {
            $disabled = "";
        }
        ?>
        <button type="submit" <?php echo $disabled; ?>>
            חיפוש רופאים קרובים
        </button>
    </form>
</div>

</body>
</html>

<?php
// Close the database after the search form has been printed.
CloseCon($conn);
?>
