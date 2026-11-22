<?php

// Start the session and load the shared database, email, and notification functions.
session_start();


include("../db_connection.php");


include("../mailer.php");
include("../app_helpers.php");

// Only a doctor may open the doctor messaging page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "doctor") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and identify the signed-in doctor.
$conn = OpenCon();


$doctor_user_id = $_SESSION['user_id'];


// This message shows the result of sending a reply.
$message = "";


// No patient is selected on the first visit.
$patient_id = "";

// Read the selected patient from the page address.
if (isset($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
}

// Send a reply after the message form is submitted.
if (isset($_POST['patient_id']) && isset($_POST['content'])) {
    $patient_id = (int)$_POST['patient_id'];

    $content_raw = $_POST['content'];
    $content = $content_raw;

    // A blank message cannot be sent.
    if ($content_raw == "") {
        $message = "נא לכתוב הודעה";
    } else {
        // Save the new message in the conversation.
        $sqlInsert = "INSERT INTO messages (
                          sender_id,
                          receiver_id,
                          content,
                          timestamp
                      )
                      VALUES (
                          '$doctor_user_id',
                          '$patient_id',
                          '$content',
                          NOW()
                      )";

        if (mysqli_query($conn, $sqlInsert)) {
            // Create an in-app notification for the patient.
            CreateNotification(
                $conn,
                $patient_id,
                "message",
                "תשובה חדשה מד״ר " . $_SESSION['name'],
                "/HealthGuard/patient/my_messages.php",
                "message_" . mysqli_insert_id($conn)
            );

            // Load the patient's contact information for the email alert.
            $sqlPatientContact = "SELECT
                                      name,
                                      email
                                  FROM users
                                  WHERE user_id='$patient_id'
                                  AND role='patient'";
            $patientContact = mysqli_fetch_assoc(mysqli_query($conn, $sqlPatientContact));

            // Try to send the same alert by email.
            if ($patientContact) {
                $mail_sent = SendMessageNotification(
                    $patientContact['email'],
                    $patientContact['name'],
                    "Dr. " . $_SESSION['name'],
                    $content_raw
                );
            } else {
                $mail_sent = false;
            }

            if ($mail_sent) {
                $message = "התשובה נשלחה וגם נשלחה התראת אימייל.";
            } else {
                $message = "התשובה נשלחה, אך שליחת האימייל נכשלה.";
            }
        } else {
            $message = "שגיאה: " . mysqli_error($conn);
        }
    }
}

// Load patients who already have messages with this doctor.
$sqlPatients = "SELECT DISTINCT
                    users.user_id,
                    users.name,
                    users.email
                FROM messages
                JOIN users
                    ON (
                    (
                        messages.sender_id=users.user_id
                        AND messages.receiver_id='$doctor_user_id'
                    )
                    OR
                    (
                        messages.receiver_id=users.user_id
                        AND messages.sender_id='$doctor_user_id'
                    )
                )
                WHERE users.role='patient'
                ORDER BY users.name ASC";
$resultPatients = mysqli_query($conn, $sqlPatients);


// This value holds the selected patient's details.
$patient = null;

// Load the selected patient and the full conversation.
if ($patient_id != "") {
    $sqlPatient = "SELECT
                       user_id,
                       name,
                       email
                   FROM users
                   WHERE user_id='$patient_id'
                   AND role='patient'";
    $resultPatient = mysqli_query($conn, $sqlPatient);
    $patient = mysqli_fetch_assoc($resultPatient);

    if ($patient) {
        $sqlMessages = "SELECT *
                        FROM messages
                        WHERE (
                            sender_id='$doctor_user_id'
                            AND receiver_id='$patient_id'
                        )
                        OR (
                            sender_id='$patient_id'
                            AND receiver_id='$doctor_user_id'
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
    <style>
        .chat-layout{
            max-width:1200px;
            margin:30px auto;
            display:grid;
            grid-template-columns:320px 1fr;
            gap:24px;
            padding:0 20px;
        }
        .chat-sidebar,.chat-main{
            background:#fff;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:20px;
        }
        .patient-item{
            display:block;
            text-decoration:none;
            color:#222;
            border:1px solid #eee;
            border-radius:16px;
            padding:14px;
            margin-bottom:12px;
            transition:0.2s;
        }
        .patient-item:hover{
            transform:translateY(-2px);
        }
        .patient-item.active{
            border:2px solid #222;
        }
        .chat-box{
            height:420px;
            overflow-y:auto;
            border:1px solid #eee;
            border-radius:16px;
            padding:16px;
            background:#fafafa;
            margin-bottom:16px;
        }
        .msg{
            max-width:70%;
            padding:12px 14px;
            border-radius:16px;
            margin-bottom:12px;
            word-wrap:break-word;
        }
        .msg-doctor{
            margin-left:auto;
            background:#d9f4ff;
        }
        .msg-patient{
            margin-right:auto;
            background:#eeeeee;
        }
        .msg-time{
            display:block;
            font-size:12px;
            margin-top:6px;
            color:#666;
        }
        .chat-form textarea{
            width:100%;
            min-height:100px;
            border:1px solid #ddd;
            border-radius:14px;
            padding:12px;
            resize:vertical;
            margin-bottom:12px;
            font-family:inherit;
        }
    </style>
</head>
<body>

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">ההודעות שלי</h1>
    <p class="section-subtitle">מענה להודעות מטופלים</p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/doctor/doctor_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
    </div>
</div>

<div class="chat-layout">

    <div class="chat-sidebar">
        <h3 style="margin-bottom:16px;">מטופלים</h3>

        <?php if (mysqli_num_rows($resultPatients) > 0) { ?>
            <?php while ($rowPatient = mysqli_fetch_assoc($resultPatients)) { ?>
                <?php
                // Highlight the patient whose conversation is open.
                $active_class = '';

                if ($patient_id == $rowPatient['user_id']) {
                    $active_class = 'active';
                }
                ?>
                <a class="patient-item <?php echo $active_class; ?>"
                   href="/HealthGuard/doctor/my_messages.php?patient_id=<?php echo $rowPatient['user_id']; ?>">
                    <strong><?php echo $rowPatient['name']; ?></strong><br>
                    <small><?php echo $rowPatient['email']; ?></small>
                </a>
            <?php } ?>
        <?php } else { ?>
            <p>עדיין אין הודעות ממטופלים.</p>
        <?php } ?>
    </div>

    <div class="chat-main">
        <?php if (!empty($message)) { ?>
            <div class="message" style="margin-bottom:15px;"><?php echo $message; ?></div>
        <?php } ?>

        <?php if ($patient) { ?>
            <h3 style="margin-bottom:8px;">שיחה עם <?php echo $patient['name']; ?></h3>
            <p style="margin-bottom:16px; color:#666;"><?php echo $patient['email']; ?></p>

            <div class="chat-box" id="chatBox">
                <?php if (mysqli_num_rows($resultMessages) > 0) { ?>
                    <?php while ($rowMsg = mysqli_fetch_assoc($resultMessages)) { ?>
                        <?php
                        // Use a different bubble colour for each sender.
                        if ($rowMsg['sender_id'] == $doctor_user_id) {
                            $message_class = 'msg-doctor';
                        } else {
                            $message_class = 'msg-patient';
                        }
                        ?>
                        <div class="msg <?php echo $message_class; ?>">
                            <?php echo nl2br($rowMsg['content']); ?>
                            <span class="msg-time"><?php echo $rowMsg['timestamp']; ?></span>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <p>עדיין אין הודעות.</p>
                <?php } ?>
            </div>

            <form class="chat-form" method="POST">
                <input type="hidden" name="patient_id" value="<?php echo $patient['user_id']; ?>">
                <textarea name="content" placeholder="כתיבת תשובה..."></textarea>
                <button type="submit" class="primary-btn">שליחת תשובה</button>
            </form>
            <script>
            const chatBox = document.getElementById("chatBox");

            // Reload the conversation and keep the latest message visible.
            function refreshChat() {
                fetch("/HealthGuard/chat_messages.php?other_user_id=<?php echo $patient['user_id']; ?>")
                    .then(response => response.text())
                    .then(html => {
                        chatBox.innerHTML = html;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    });
            }

            // Check for new messages every three seconds.
            setInterval(refreshChat, 3000);
            </script>
        <?php } else { ?>
            <div class="card">
                <h3>בחירת מטופל</h3>
                <p>בחר מטופל מהרשימה כדי לפתוח את השיחה.</p>
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
