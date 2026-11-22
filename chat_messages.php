<?php

// Start the session and load the database functions.
session_start();
include("db_connection.php");

// Return no messages when there is no signed-in user.
if (!isset($_SESSION['user_id'])) {
    exit();
}

// Open the database and read both people in the conversation.
$conn = OpenCon();
$user_id = (int)$_SESSION['user_id'];
$other_user_id = (int)($_GET['other_user_id'] ?? 0);
$allowed = false;

// A patient may chat only with a doctor from one of their appointments.
if ($_SESSION['role'] == "patient") {
    $permission_query = "SELECT doctors.doctor_id
                         FROM doctors
                         JOIN appointments ON appointments.doctor_id=doctors.doctor_id
                         WHERE doctors.user_id='$other_user_id'
                         AND appointments.patient_id='$user_id'
                         LIMIT 1";

    $allowed = mysqli_num_rows(mysqli_query($conn, $permission_query)) > 0;
}

// A doctor may chat only with one of their appointment patients.
if ($_SESSION['role'] == "doctor") {
    $permission_query = "SELECT appointments.appointment_id
                         FROM appointments
                         JOIN doctors ON appointments.doctor_id=doctors.doctor_id
                         WHERE doctors.user_id='$user_id'
                         AND appointments.patient_id='$other_user_id'
                         LIMIT 1";

    $allowed = mysqli_num_rows(mysqli_query($conn, $permission_query)) > 0;
}

// Stop when the two users do not have permission to chat.
if (!$allowed) {
    CloseCon($conn);
    exit();
}

// Load every message between the two users in time order.
$messages_sql = "SELECT *
                 FROM messages
                 WHERE (
                     sender_id='$user_id'
                     AND receiver_id='$other_user_id'
                 )
                 OR (
                     sender_id='$other_user_id'
                     AND receiver_id='$user_id'
                 )
                 ORDER BY timestamp";
$messages = mysqli_query($conn, $messages_sql);

// Print every message with the correct colour for its sender.
while ($row = mysqli_fetch_assoc($messages)) {
    $is_my_message = $row['sender_id'] == $user_id;

    if ($_SESSION['role'] == "doctor") {
        if ($is_my_message) {
            $message_class = "msg-doctor";
        } else {
            $message_class = "msg-patient";
        }
    } else {
        if ($is_my_message) {
            $message_class = "msg-patient";
        } else {
            $message_class = "msg-doctor";
        }
    }

    echo '<div class="msg ' . $message_class . '">';
    echo nl2br($row['content']);
    echo '<span class="msg-time">';
    echo $row['timestamp'];
    echo '</span></div>';
}

// Close the database after all messages have been printed.
CloseCon($conn);
