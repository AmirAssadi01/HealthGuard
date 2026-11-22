<?php

// Start the session and load the database, email, and notification functions.
session_start();


include("../db_connection.php");


include("../mailer.php");
include("../app_helpers.php");

// Only a patient may open the patient messaging page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the page values.
$conn = OpenCon();


$patient_id = $_SESSION['user_id'];
$message = "";
$doctor_id = "";

// Read the selected doctor from the page address.
if (isset($_GET['doctor_id'])) {
    $doctor_id = (int)$_GET['doctor_id'];
}

// Send a message after the form is submitted.
if (isset($_POST['doctor_id']) && isset($_POST['content'])) {
    $doctor_id = (int)$_POST['doctor_id'];

    $content_raw = $_POST['content'];
    $content = $content_raw;

    // A blank message cannot be sent.
    if ($content == "") {
        $message = "נא לכתוב הודעה.";
    } else {
        // A patient may message only a doctor from one of their appointments.
        $sqlApptCheck = "SELECT appointment_id
                         FROM appointments
                         WHERE patient_id='$patient_id'
                         AND doctor_id='$doctor_id'
                         LIMIT 1";
        if (mysqli_num_rows(mysqli_query($conn, $sqlApptCheck)) == 0) {
            $message = "אפשר לשלוח הודעה רק לרופא שקבעת איתו תור.";
        } else {

            // Load the doctor's user account and email address.
            $sqlGetDoctorUser = "SELECT
                                     users.user_id,
                                     users.name,
                                     users.email
                                 FROM doctors
                                 JOIN users
                                     ON doctors.user_id=users.user_id
                                 WHERE doctors.doctor_id='$doctor_id'";
            $doctorData = mysqli_fetch_assoc(mysqli_query($conn, $sqlGetDoctorUser));

            if ($doctorData) {
                $doctor_user_id = $doctorData['user_id'];
                // Save the message in the conversation.
                $sqlInsert = "INSERT INTO messages (
                                  sender_id,
                                  receiver_id,
                                  content,
                                  timestamp
                              )
                              VALUES (
                                  '$patient_id',
                                  '$doctor_user_id',
                                  '$content',
                                  NOW()
                              )";

                if (mysqli_query($conn, $sqlInsert)) {
                    // Create an in-app notification for the doctor.
                    CreateNotification(
                        $conn,
                        $doctor_user_id,
                        "message",
                        "הודעה חדשה מאת " . $_SESSION['name'],
                        "/HealthGuard/doctor/my_messages.php?patient_id=$patient_id",
                        "message_" . mysqli_insert_id($conn)
                    );

                    // Send the same alert by email.
                    $mail_sent = SendMessageNotification(
                        $doctorData['email'],
                        "Dr. " . $doctorData['name'],
                        $_SESSION['name'],
                        $content_raw
                    );

                    if ($mail_sent) {
                        $message = "ההודעה נשלחה וגם נשלחה התראת אימייל.";
                    } else {
                        $message = "ההודעה נשלחה, אך שליחת האימייל נכשלה.";
                    }
                } else {
                    $message = "שגיאה: " . mysqli_error($conn);
                }
            } else {
                $message = "הרופא לא נמצא.";
            }
        }
    }
}


// Load doctors who have at least one appointment with this patient.
$sqlDoctors = "SELECT DISTINCT
                   doctors.doctor_id,
                   doctors.user_id,
                   users.name,
                   users.email,
                   doctors.specialization
               FROM doctors
               JOIN users
                   ON doctors.user_id=users.user_id
               JOIN appointments
                   ON appointments.doctor_id=doctors.doctor_id
               WHERE appointments.patient_id='$patient_id'
               ORDER BY users.name ASC";
$resultDoctors = mysqli_query($conn, $sqlDoctors);


// These values describe the selected conversation.
$doctor = null;
$has_appointment_with_selected = false;

// Load the selected doctor and conversation.
if ($doctor_id != "") {
    $sqlApptChk = "SELECT appointment_id
                   FROM appointments
                   WHERE patient_id='$patient_id'
                   AND doctor_id='$doctor_id'
                   LIMIT 1";
    $has_appointment_with_selected = mysqli_num_rows(mysqli_query($conn, $sqlApptChk)) > 0;

    $sqlDoctor = "SELECT
                      doctors.doctor_id,
                      doctors.user_id,
                      users.name,
                      users.email,
                      doctors.specialization
                  FROM doctors
                  JOIN users
                      ON doctors.user_id=users.user_id
                  WHERE doctors.doctor_id='$doctor_id'";
    $doctor = mysqli_fetch_assoc(mysqli_query($conn, $sqlDoctor));

    if ($doctor && $has_appointment_with_selected) {
        $doctor_user_id = $doctor['user_id'];

        $sqlMessages = "SELECT *
                        FROM messages
                        WHERE (
                            sender_id='$patient_id'
                            AND receiver_id='$doctor_user_id'
                        )
                        OR (
                            sender_id='$doctor_user_id'
                            AND receiver_id='$patient_id'
                        )
                        ORDER BY timestamp ASC";
        $resultMessages = mysqli_query($conn, $sqlMessages);
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ההודעות שלי</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ההודעות שלי</h1>
    <p class="section-subtitle">שליחת הודעות לרופאים שקבעת איתם תור</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>
</div>

<div class="chat-layout">

    <div class="chat-sidebar">
        <h3 style="margin-bottom:16px;">הרופאים שלי</h3>

        <?php if (mysqli_num_rows($resultDoctors) > 0) { ?>
            <?php while ($rowDoctor = mysqli_fetch_assoc($resultDoctors)) { ?>
                <?php
                // Highlight the doctor whose conversation is open.
                $active_class = '';

                if ($doctor_id == $rowDoctor['doctor_id']) {
                    $active_class = 'active';
                }
                ?>
                <a class="doctor-item <?php echo $active_class; ?>"
                   href="/HealthGuard/patient/my_messages.php?doctor_id=<?php echo $rowDoctor['doctor_id']; ?>">
                    <strong>Dr. <?php echo $rowDoctor['name']; ?></strong><br>
                    <small><?php echo HebrewText($rowDoctor['specialization']); ?></small><br>
                    <small><?php echo $rowDoctor['email']; ?></small>
                </a>
            <?php } ?>
        <?php } else { ?>
            <p style="color:#64748b; font-size:14px;">עדיין אין תורים. יש להזמין תור כדי להתחיל להתכתב עם רופא.</p>
        <?php } ?>
    </div>

    <div class="chat-main">
        <?php if (!empty($message)) { ?>
            <div class="message" style="margin-bottom:15px;"><?php echo $message; ?></div>
        <?php } ?>

        <?php if ($doctor && $has_appointment_with_selected) { ?>
            <h3 style="margin-bottom:8px;">שיחה עם ד״ר <?php echo $doctor['name']; ?></h3>
            <p style="margin-bottom:16px; color:#666;">
                <?php echo HebrewText($doctor['specialization']); ?> — <?php echo $doctor['email']; ?>
            </p>

            <div class="chat-box" id="chatBox">
                <?php if (isset($resultMessages) && mysqli_num_rows($resultMessages) > 0) { ?>
                    <?php while ($rowMsg = mysqli_fetch_assoc($resultMessages)) { ?>
                        <?php
                        // Use a different bubble colour for each sender.
                        if ($rowMsg['sender_id'] == $patient_id) {
                            $message_class = 'msg-patient';
                        } else {
                            $message_class = 'msg-doctor';
                        }
                        ?>
                        <div class="msg <?php echo $message_class; ?>">
                            <?php echo nl2br($rowMsg['content']); ?>
                            <span class="msg-time"><?php echo $rowMsg['timestamp']; ?></span>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>עדיין אין הודעות. אפשר להתחיל בשיחה.</p>
                <?php } ?>
            </div>

            <form class="chat-form" method="POST">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                <textarea name="content" placeholder="כתיבת הודעה..."></textarea>
                <button type="submit" class="primary-btn">שליחת הודעה</button>
            </form>
            <script>
            const chatBox = document.getElementById("chatBox");

            // Reload the conversation and keep the latest message visible.
            function refreshChat() {
                fetch("/HealthGuard/chat_messages.php?other_user_id=<?php echo $doctor['user_id']; ?>")
                    .then(response => response.text())
                    .then(html => {
                        chatBox.innerHTML = html;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    });
            }

            // Check for new messages every three seconds.
            setInterval(refreshChat, 3000);
            </script>

        <?php } elseif ($doctor && !$has_appointment_with_selected) { ?>
            <div class="card">
                <h3>נדרש תור</h3>
                <p>יש להזמין תור אצל ד״ר <?php echo $doctor['name']; ?> לפני שאפשר לשלוח הודעות.</p>
                <div class="hero-actions" style="justify-content:flex-start; margin-top:14px;">
                    <a href="/HealthGuard/patient/book_appointment.php?doctor_id=<?php echo $doctor['doctor_id']; ?>" class="primary-btn">הזמנת תור</a>
                </div>
            </div>
        <?php } else { ?>
            <div class="card">
                <h3>בחירת רופא</h3>
                <p>בחר רופא מהרשימה כדי להתחיל שיחה. אפשר לפנות רק לרופא שקבעת איתו תור.</p>
            </div>
        <?php } ?>
    </div>

</div>

</body>
</html>
<?php
// Close the database after the messaging page has been printed.
CloseCon($conn);
?>
