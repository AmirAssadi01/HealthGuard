<?php

// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only a patient may open their private medical diary.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and prepare the page values.
$conn = OpenCon();
$patient_id = (int)$_SESSION['user_id'];
$message = "";

// Add a diary entry after the form is submitted.
if (isset($_POST['add_entry'])) {
    $entry_date = $_POST['entry_date'];
    $title = $_POST['title'];
    $notes = $_POST['notes'];

    // Save only entries that contain both a title and notes.
    if ($title != "" && $notes != "") {
        $insert_sql = "INSERT INTO diary_entries (
                           patient_id,
                           entry_date,
                           title,
                           notes
                       )
                       VALUES (
                           '$patient_id',
                           '$entry_date',
                           '$title',
                           '$notes'
                       )";
        mysqli_query($conn, $insert_sql);
        $message = "הרשומה נוספה ליומן.";
    }
}

// Delete one diary entry after its remove button is pressed.
if (isset($_POST['delete_entry'])) {
    $entry_id = (int)$_POST['entry_id'];
    mysqli_query($conn, "DELETE FROM diary_entries
                         WHERE entry_id='$entry_id' AND patient_id='$patient_id'");
    $message = "הרשומה הוסרה מהיומן.";
}

// Load the newest diary entries first.
$diary_sql = "SELECT *
              FROM diary_entries
              WHERE patient_id='$patient_id'
              ORDER BY
                  entry_date DESC,
                  entry_id DESC";
$diary = mysqli_query($conn, $diary_sql);
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>יומן רפואי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">
<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">היסטוריה ויומן רפואי</h1>
    <p class="section-subtitle">שמירת הערות הבריאות האישיות לפי תאריך</p>

    <?php if ($message != "") { ?>
        <div class="message"><?php echo $message; ?></div>
    <?php } ?>

    <div class="feature-grid">
        <form method="POST" class="card feature-form">
            <h3>הוספת רשומה ליומן</h3>
            <label>תאריך</label>
            <input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" required>
            <label>כותרת</label>
            <input type="text" name="title" required>
            <label>הערות</label>
            <textarea name="notes" required></textarea>
            <button name="add_entry">שמירת רשומה</button>
        </form>

        <div class="card">
            <h3>רשומות ביומן</h3>
            <?php while ($row = mysqli_fetch_assoc($diary)) { ?>
                <div class="mini-entry">
                    <strong><?php echo $row['title']; ?></strong>
                    <small><?php echo $row['entry_date']; ?></small>
                    <p><?php echo nl2br($row['notes']); ?></p>
                    <form method="POST">
                        <input type="hidden" name="entry_id" value="<?php echo $row['entry_id']; ?>">
                        <button class="danger-btn" name="delete_entry">הסרה</button>
                    </form>
                </div>
            <?php } ?>

            <?php if (mysqli_num_rows($diary) == 0) { ?>
                <p>עדיין אין רשומות ביומן.</p>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
<?php
// Close the database after the diary page has been printed.
CloseCon($conn);
?>
