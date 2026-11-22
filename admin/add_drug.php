<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only an administrator may add a drug.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// This message shows the result of the form.
$message = "";

// Add the drug after the form is submitted.
if (isset($_POST['drug_name'])) {
    // Open the database and read the submitted values.
    $conn = OpenCon();

    $drug_name = $_POST['drug_name'];
    $allergy_group = $_POST['allergy_group'];
    $notes = $_POST['notes'];

    // A drug must have a name.
    if ($drug_name == "") {
        $message = "נא להזין שם תרופה";
    } else {

        // Check whether a drug with the same name already exists.
        $check = "SELECT *
                  FROM drugs
                  WHERE drug_name='$drug_name'";
        $resultCheck = mysqli_query($conn, $check);

        if (mysqli_num_rows($resultCheck) > 0) {
            $message = "התרופה כבר קיימת";
        } else {

            // Save the new drug and its optional details.
            $sql = "INSERT INTO drugs (
                        drug_name,
                        allergy_group,
                        notes,
                        created_at
                    )
                    VALUES (
                        '$drug_name',
                        '$allergy_group',
                        '$notes',
                        NOW()
                    )";

            if (mysqli_query($conn, $sql)) {
                $message = "התרופה נוספה בהצלחה";
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
    <title>הוספת תרופה</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="admin-page">

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הוספת תרופה</h2>
        <p class="subtitle">יצירת תרופה חדשה למרשמים</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/admin/manage_drugs.php" class="secondary-btn">חזרה</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם התרופה</label>
        <input type="text" name="drug_name" placeholder="הזנת שם תרופה">

        <label>קבוצת אלרגיה</label>
        <input type="text" name="allergy_group" placeholder="לדוגמה: Penicillin / NSAID">

        <label>הערות</label>
        <textarea name="notes" placeholder="הערות לא חובה"></textarea>

        <button type="submit">הוספת תרופה</button>
    </form>
</div>

</body>
</html>
