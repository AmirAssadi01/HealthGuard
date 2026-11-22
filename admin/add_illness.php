<?php

// Show PHP errors while this administration form is being developed.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may add an illness.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Add the illness after the form is submitted.
if (isset($_POST['illness_name'])) {
    // Open the database and read the submitted values.
    $conn = OpenCon();

    $illness_name = $_POST['illness_name'];
    $specialization = $_POST['specialization'];

    // Both the illness name and specialization are required.
    if ($illness_name == "" || $specialization == "") {
        $message = "נא למלא את כל השדות";
    } else {

        // Check whether this illness already exists.
        $check = "SELECT *
                  FROM illnesses
                  WHERE illness_name='$illness_name'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0) {
            $message = "המחלה כבר קיימת";
        } else {

            // Save the illness and the doctor specialization that treats it.
            $sql = "INSERT INTO illnesses (
                        illness_name,
                        specialization
                    )
                    VALUES (
                        '$illness_name',
                        '$specialization'
                    )";

            if (mysqli_query($conn, $sql)) {
                $message = "המחלה נוספה בהצלחה";
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
    <title>הוספת מחלה</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת מחלה</h2>
        <p class="subtitle">יצירת מחלה חדשה ושיוכה להתמחות</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/manage_illnesses.php" class="secondary-btn">חזרה</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם המחלה</label>
        <input type="text" name="illness_name" placeholder="הזנת שם מחלה">

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

        <button type="submit">הוספת מחלה</button>
    </form>
</div>

</body>
</html>
