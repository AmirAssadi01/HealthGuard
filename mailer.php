<?php
// Load the PHPMailer class used to send messages through Gmail.
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/vendor/autoload.php";

// Gmail account used by HealthGuard for automatic emails.
$gmail = "mohammad.bakri311@gmail.com";
$password = "vnkikbajzzkyesoj";

// Send one plain-text email and return true when it succeeds.
function SendEmailMessage($to, $subject_text, $body)
{
    global $gmail, $password;

    try {
        // Create a new mail message.
        $mail = new PHPMailer(true);

        // Connect PHPMailer to Gmail's SMTP server.
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = $gmail;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        // Set the sender, recipient, subject, and message body.
        $mail->setFrom($gmail, "HealthGuard");
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject = $subject_text;
        $mail->Body = $body;
        // Send the completed message.
        return $mail->send();
    } catch (Throwable $e) {
        // Return false so the page can continue when email sending fails.
        return false;
    }
}

// Build and send an email about a new chat message.
function SendMessageNotification($to, $name, $sender, $message)
{
    $body = "שלום $name,\r\n\r\n$sender שלח לך הודעה:\r\n\r\n$message";
    return SendEmailMessage($to, "הודעה חדשה מאת $sender", $body);
}

// Build and send the email that contains a password reset link.
function SendPasswordResetEmail($to, $name, $reset_url)
{
    $body = "שלום $name,\r\n\r\nבקישור הבא אפשר לבחור סיסמה חדשה:\r\n$reset_url\r\n\r\n";
    $body .= "הקישור תקף ל־30 דקות וניתן לשימוש פעם אחת.";
    return SendEmailMessage($to, "איפוס סיסמת HealthGuard", $body);
}
