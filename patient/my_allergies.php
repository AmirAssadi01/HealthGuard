<?php

// Start the session and load the database functions.
session_start();


include("../db_connection.php");

// Only a patient may manage their allergies.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();


$patient_id = $_SESSION['user_id'];
$message = "";

// Add an allergy after the form is submitted.
if (isset($_POST['allergy_name'])) {
    $allergy_name = $_POST['allergy_name'];
    $reaction = $_POST['reaction'];

    // An allergy name is required.
    if ($allergy_name == "") {
        $message = "נא לבחור אלרגיה";
    } else {

        // Check whether this allergy is already saved for the patient.
        $check = "SELECT *
                  FROM patient_allergies
                  WHERE patient_id='$patient_id'
                  AND allergy_name='$allergy_name'";
        $resultCheck = mysqli_query($conn, $check);

        if (mysqli_num_rows($resultCheck) > 0) {
            $message = "האלרגיה כבר נוספה";
        } else {

            // Save the allergy and its optional reaction description.
            $sqlInsert = "INSERT INTO patient_allergies (
                              patient_id,
                              allergy_name,
                              reaction,
                              created_at
                          )
                          VALUES (
                              '$patient_id',
                              '$allergy_name',
                              '$reaction',
                              NOW()
                          )";

            if (mysqli_query($conn, $sqlInsert)) {
                $message = "האלרגיה נוספה בהצלחה";
            } else {
                $message = "שגיאה: " . mysqli_error($conn);
            }
        }
    }
}

// Delete one allergy after its remove link is pressed.
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM patient_allergies
                   WHERE allergy_id='$delete_id'
                   AND patient_id='$patient_id'";
    mysqli_query($conn, $delete_sql);
    header("Location: /HealthGuard/patient/my_allergies.php");
    exit();
}


// Load the patient's saved allergies.
$sql = "SELECT *
        FROM patient_allergies
        WHERE patient_id='$patient_id'
        ORDER BY allergy_name ASC";
$result = mysqli_query($conn, $sql);


// Load the available allergy groups from the drug table.
$groups_sql = "SELECT DISTINCT allergy_group
               FROM drugs
               WHERE allergy_group IS NOT NULL
               AND allergy_group<>''
               ORDER BY allergy_group ASC";
$allergyGroups = mysqli_query($conn, $groups_sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>האלרגיות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">האלרגיות שלי</h1>
    <p class="section-subtitle">הוספת אלרגיות כדי שהרופא יוכל להתאים תרופות</p>

    <div class="cards">
        <div class="card">
            <h3>הוספת אלרגיה</h3>

            <?php if (!empty($message)) { ?>
                <div class="message"><?php echo $message; ?></div>
            <?php } ?>

            <form method="POST">
                <label>שם האלרגיה</label>
                <select name="allergy_name" required>
                    <option value="">-- בחירת אלרגיה --</option>
                    <?php while ($g = mysqli_fetch_assoc($allergyGroups)) { ?>
                        <option value="<?php echo $g['allergy_group']; ?>"><?php echo $g['allergy_group']; ?></option>
                    <?php } ?>
                </select>

                <label>תגובה</label>
                <input type="text" name="reaction" placeholder="לדוגמה: פריחה / נפיחות / קושי בנשימה">

                <button type="submit">הוספת אלרגיה</button>
            </form>
        </div>

        <div class="card">
            <h3>אלרגיות שמורות</h3>

            <div class="table-wrapper">
                <table>
                    <tr>
                        <th>אלרגיה</th>
                        <th>תגובה</th>
                        <th>פעולה</th>
                    </tr>

                    <?php if (mysqli_num_rows($result) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $row['allergy_name']; ?></td>
                            <td><?php echo $row['reaction']; ?></td>
                            <td>
                                <a
                                    href="/HealthGuard/patient/my_allergies.php?delete_id=<?php echo $row['allergy_id']; ?>"
                                    class="danger-btn"
                                    onclick="return confirm('למחוק את האלרגיה?');"
                                >
                                    מחיקה
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="3">עדיין לא נוספו אלרגיות.</td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
// Close the database after the allergy page has been printed.
CloseCon($conn);
?>
