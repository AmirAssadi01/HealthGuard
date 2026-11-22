<?php

// Start the session and load the database functions.
session_start();
include("../db_connection.php");

// Only an administrator may change system settings.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Open the database and prepare the page message.
$conn = OpenCon();
$message = "";

// Save each setting after the form is submitted.
if (isset($_POST['save_settings'])) {
    $setting_names = ["site_name", "appointment_reminder"];

    foreach ($setting_names as $name) {
        // Read the submitted value for the current setting.
        $value = $_POST[$name] ?? "";

        // Insert a new setting or update the existing setting.
        $save_sql = "INSERT INTO system_settings (
                         setting_name,
                         setting_value
                     )
                     VALUES (
                         '$name',
                         '$value'
                     )
                     ON DUPLICATE KEY UPDATE
                         setting_value='$value'";
        mysqli_query($conn, $save_sql);
    }

    $message = "ההגדרות נשמרו.";
}


// Load all saved settings into an array by setting name.
$settings = [];
$settings_sql = "SELECT *
                 FROM system_settings";
$result = mysqli_query($conn, $settings_sql);

while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_name']] = $row['setting_value'];
}


// These are the reminder choices shown in the select box.
$reminder_options = [
    "Same day",
    "1 day before",
    "2 days before"
];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגדרות מערכת</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>
<?php include("../navbar.php"); ?>

<div class="form-container">
    <form method="POST" class="form-box">
        <h2>הגדרות מערכת</h2>
        <p class="subtitle">שינוי ההגדרות הראשיות של HealthGuard</p>

        <?php if ($message != "") { ?>
            <div class="message"><?php echo $message; ?></div>
        <?php } ?>

        <label>שם האתר</label>
        <input
            type="text"
            name="site_name"
            value="<?php echo $settings['site_name'] ?? 'HealthGuard'; ?>"
            required
        >

        <label>תזכורת לתור</label>
        <select name="appointment_reminder">
            <?php foreach ($reminder_options as $option) { ?>
                <?php
                // Mark the reminder option currently saved in the database.
                $selected_option = "";

                if (($settings['appointment_reminder'] ?? '') == $option) {
                    $selected_option = "selected";
                }
                ?>
                <option
                    value="<?php echo $option; ?>"
                    <?php echo $selected_option; ?>
                >
                    <?php echo $option; ?>
                </option>
            <?php } ?>
        </select>

        <button type="submit" name="save_settings">שמירת הגדרות</button>
    </form>
</div>
</body>
</html>
<?php
// Close the database after the settings page has finished.
CloseCon($conn);
?>
