<?php

// Start the session and load the database and city functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// The city list is shown in the profile form.
$cities = HealthGuardCities();

// Only a patient may open this profile page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();


$user_id = $_SESSION['user_id'];
$message = "";

// Convert an optional profile value to SQL NULL or a quoted value.
function ProfileSqlValue($value)
{
    if ($value === "") {
        return "NULL";
    }

    return "'$value'";
}

// Delete the patient account after the separate delete form is confirmed.
if (isset($_POST['delete_account'])) {
    $delete_sql = "DELETE FROM users
                   WHERE user_id='$user_id'
                   AND role='patient'";

    if (mysqli_query($conn, $delete_sql)) {
        session_destroy();
        header("Location: /HealthGuard/index.php");
        exit();
    }
    $message = "לא ניתן למחוק את החשבון כי קיימים אליו נתונים מקושרים.";
}

// Save the profile after the main form is submitted.
if (isset($_POST['age'])) {
    // Read the account and patient values from the form.
    $age_raw = $_POST['age'] ?? '';

    if ($age_raw === '') {
        $age = '';
    } else {
        $age = (int)$age_raw;
    }

    $gender = $_POST['gender'] ?? '';
    $medical_notes = $_POST['medical_notes'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $national_id = $_POST['national_id'] ?? '';
    $city = $_POST['city'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // Update the main user account when the required values are valid.
    if ($name != "" && $email != "" && isset($cities[$city])) {
        $user_sql = "UPDATE users
                     SET
                         name='$name',
                         email='$email',
                         phone='$phone',
                         national_id='$national_id',
                         city='$city'
                     WHERE user_id='$user_id'";
        mysqli_query($conn, $user_sql);

        // Keep the session name and email in sync with the saved account.
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;

        // Save a new password only when the optional field has a value.
        if ($new_password != "") {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = "UPDATE users
                             SET password='$hash'
                             WHERE user_id='$user_id'";
            mysqli_query($conn, $password_sql);
        }
    }

    // Check whether this account already has a patient details row.
    $sqlCheck = "SELECT *
                 FROM patients
                 WHERE user_id='$user_id'";
    $resultCheck = mysqli_query($conn, $sqlCheck);

    // Convert the optional body measurements to numbers when they are present.
    $weight_raw = $_POST['current_weight'] ?? '';
    $waist_raw = $_POST['waist_cm'] ?? '';

    if ($weight_raw === '') {
        $current_weight = '';
    } else {
        $current_weight = (float)$weight_raw;
    }

    if ($waist_raw === '') {
        $waist_cm = '';
    } else {
        $waist_cm = (float)$waist_raw;
    }

    // Convert optional values to the SQL text used below.
    $age_sql = ProfileSqlValue($age);
    $gender_sql = ProfileSqlValue($gender);
    $notes_sql = ProfileSqlValue($medical_notes);
    $weight_sql = ProfileSqlValue($current_weight);
    $waist_sql = ProfileSqlValue($waist_cm);

    // Update the current patient row or insert it when it is missing.
    if (mysqli_num_rows($resultCheck) > 0) {
        $sqlUpdate = "UPDATE patients
                      SET
                          age=$age_sql,
                          gender=$gender_sql,
                          medical_notes=$notes_sql,
                          current_weight=$weight_sql,
                          waist_cm=$waist_sql
                      WHERE user_id='$user_id'";
        mysqli_query($conn, $sqlUpdate);
    } else {
        $sqlInsert = "INSERT INTO patients (
                          user_id,
                          age,
                          gender,
                          medical_notes,
                          current_weight,
                          waist_cm
                      )
                      VALUES (
                          '$user_id',
                          $age_sql,
                          $gender_sql,
                          $notes_sql,
                          $weight_sql,
                          $waist_sql
                      )";
        mysqli_query($conn, $sqlInsert);
    }

    $message = "הפרופיל עודכן בהצלחה";
}


// Load the complete profile for the form.
$sql = "SELECT
            users.name,
            users.email,
            users.phone,
            users.national_id,
            users.city,
            users.created_at,
            patients.age,
            patients.gender,
            patients.medical_notes,
            patients.current_weight,
            patients.waist_cm
        FROM users
        LEFT JOIN patients
            ON users.user_id=patients.user_id
        WHERE users.user_id='$user_id'
        AND users.role='patient'";

$result = mysqli_query($conn, $sql);
$patient = mysqli_fetch_assoc($result);


// Use empty strings for profile fields that have not been saved yet.
$patient_name = $patient['name'] ?? '';
$patient_email = $patient['email'] ?? '';
$patient_phone = $patient['phone'] ?? '';
$patient_national_id = $patient['national_id'] ?? '';
$patient_city = $patient['city'] ?? '';
$patient_created_at = $patient['created_at'] ?? '';
$patient_age = $patient['age'] ?? '';
$patient_gender = $patient['gender'] ?? '';
$patient_medical_notes = $patient['medical_notes'] ?? '';
$patient_weight = $patient['current_weight'] ?? '';
$patient_waist = $patient['waist_cm'] ?? '';

// Prepare the selected gender option before printing the form.
$male_selected = '';
$female_selected = '';
$other_selected = '';

if ($patient_gender == 'male') {
    $male_selected = 'selected';
} elseif ($patient_gender == 'female') {
    $female_selected = 'selected';
} elseif ($patient_gender == 'other') {
    $other_selected = 'selected';
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
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="form-container">
    <form class="form-box" method="POST">
        <h2>הפרופיל שלי</h2>
        <p class="subtitle">פרטי חשבון המטופל</p>

        <div class="hero-actions" style="justify-content:center; margin-bottom:20px;">
            <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        </div>

        <?php if (!empty($message)) { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם המטופל</label>
        <input type="text" name="name" value="<?php echo $patient_name; ?>" required>

        <label>אימייל</label>
        <input type="email" name="email" value="<?php echo $patient_email; ?>" required>

        <label>טלפון</label>
        <input type="text" name="phone" value="<?php echo $patient_phone; ?>">

        <label>עיר</label>
        <select name="city" required>
            <option value="">בחירת עיר</option>
            <?php foreach ($cities as $city_name => $coordinates) { ?>
                <?php
                // Select the patient's currently saved city.
                $selected = '';

                if ($patient_city === $city_name) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?php echo $city_name; ?>" <?php echo $selected; ?>>
                    <?php echo $city_name; ?>
                </option>
            <?php } ?>
        </select>

        <label>מספר זהות</label>
        <input type="text" name="national_id" value="<?php echo $patient_national_id; ?>">

        <label>סיסמה חדשה (לא חובה)</label>
        <input type="password" name="new_password">

        <label>נוצר בתאריך</label>
        <input type="text" value="<?php echo $patient_created_at; ?>" readonly>

        <label>גיל</label>
        <input type="number" name="age" value="<?php echo $patient_age; ?>">

        <label>משקל נוכחי KG</label>
        <input type="number" step="0.1" name="current_weight" value="<?php echo $patient_weight; ?>">

        <label>היקף מותניים CM</label>
        <input type="number" step="0.1" name="waist_cm" value="<?php echo $patient_waist; ?>">

        <label>מגדר</label>
        <select name="gender">
            <option value="">בחירת מגדר</option>
            <option value="male" <?php echo $male_selected; ?>>זכר</option>
            <option value="female" <?php echo $female_selected; ?>>נקבה</option>
            <option value="other" <?php echo $other_selected; ?>>אחר</option>
        </select>

        <label>הערות רפואיות</label>
        <textarea name="medical_notes"><?php echo $patient_medical_notes; ?></textarea>

        <button type="submit">שמירת הפרופיל</button>
    </form>
    <form class="form-box" method="POST" style="margin-top:18px;"
          onsubmit="return confirm('למחוק את החשבון ואת כל הנתונים שלו?');">
        <button class="danger-btn" name="delete_account">מחיקת החשבון שלי</button>
    </form>
</div>

</body>
</html>
<?php
// Close the database after the profile page has been printed.
CloseCon($conn);
?>
