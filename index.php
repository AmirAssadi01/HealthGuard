<?php

// Start a session so the navigation bar knows whether the user is signed in.
session_start();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body>

<?php
// Add the shared navigation bar at the top of the home page.
include("navbar.php");
?>

<div class="hero">
    <div class="hero-box">
        <h1>ברוכים הבאים ל־HealthGuard</h1>

        <p>
            HealthGuard היא מערכת בריאות חכמה שעוזרת למטופלים למצוא את הרופא המתאים
            בהתאם למחלה. אפשר ליצור חשבון, להתחבר, לחפש רופאים
            ולהשתמש בשירותי הבריאות הזמינים במערכת.
        </p>

        <div class="hero-actions">
            <a href="/HealthGuard/patient/find_doctor.php" class="primary-btn">חיפוש רופא</a>
            <a href="/HealthGuard/signup.php" class="secondary-btn">יצירת חשבון</a>
        </div>
    </div>
</div>

</body>
</html>
