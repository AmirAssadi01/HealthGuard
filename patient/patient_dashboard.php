<?php

// Start the session so the dashboard knows which patient is signed in.
session_start();

// Keep this page available only to patient accounts.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}
// Show the signed-in patient's name in the welcome area.
$patient_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>לוח מטופל - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="hg-patient-dashboard">

    <section class="hg-patient-header">
        <div class="hg-patient-header-text">
            <h1>ברוך שובך, <?php echo $patient_name; ?></h1>
            <p>
                HealthGuard מרכזת את ניהול הבריאות שלך במקום אחד.
                אפשר לחפש רופא מתאים, להזמין תורים, לשלוח הודעות,
                לצפות בדוחות ולעקוב אחר תוכנית התזונה שהרופא הכין.
            </p>
        </div>

        <div class="hg-patient-user-box">
            <div class="hg-patient-avatar">
                <?php echo strtoupper(substr($patient_name, 0, 1)); ?>
            </div>
            <div class="hg-patient-user-info">
                <strong>לוח מטופל</strong>
                <span><?php echo $patient_name; ?></span>
            </div>
        </div>
    </section>

    <div class="hg-patient-layout">

        <main class="hg-patient-main">

            <section class="hg-panel">
                <div class="hg-panel-head">
                    <h2>שירותים מהירים</h2>
                    <p>גישה מהירה לפעולות המרכזיות בחשבון</p>
                </div>

                <div class="hg-service-grid">
                    <a href="/HealthGuard/patient/find_doctor.php" class="hg-service-card">
                        <div class="hg-service-icon">👨‍⚕️</div>
                        <span>חיפוש רופא</span>
                    </a>

                    <a href="/HealthGuard/patient/my_appointments.php" class="hg-service-card">
                        <div class="hg-service-icon">📅</div>
                        <span>תורים</span>
                    </a>

                    <a href="/HealthGuard/patient/my_messages.php" class="hg-service-card">
                        <div class="hg-service-icon">💬</div>
                        <span>הודעות</span>
                    </a>

                    <a href="/HealthGuard/patient/my_doctors.php" class="hg-service-card">
                        <div class="hg-service-icon">🩺</div>
                        <span>הרופאים שלי</span>
                    </a>

                    <a href="/HealthGuard/patient/my_reports.php" class="hg-service-card">
                        <div class="hg-service-icon">📄</div>
                        <span>דוחות</span>
                    </a>
                    <a href="/HealthGuard/patient/my_medications.php" class="hg-service-card">
                        <div class="hg-service-icon">💊</div>
                        <span>תרופות</span>
                    </a>

                    <a href="/HealthGuard/patient/my_allergies.php" class="hg-service-card">
                        <div class="hg-service-icon">⚠️</div>
                        <span>אלרגיות</span>
                    </a>

                    <a href="/HealthGuard/patient/my_nutrition_plan.php" class="hg-service-card">
                        <div class="hg-service-icon">🥗</div>
                        <span>תוכנית תזונה</span>
                    </a>

                    <a href="/HealthGuard/patient/upload_family_report.php" class="hg-service-card">
                        <div class="hg-service-icon">💊</div>
                        <span>העלאת הפניה</span>
                    </a>

                    <a href="/HealthGuard/patient/medical_history.php" class="hg-service-card">
                        <div class="hg-service-icon">📔</div>
                        <span>היסטוריה רפואית</span>
                    </a>

                    <a href="/HealthGuard/patient/progress.php" class="hg-service-card">
                        <div class="hg-service-icon">📈</div>
                        <span>ההתקדמות שלי</span>
                    </a>

                    <a href="/HealthGuard/notifications.php" class="hg-service-card">
                        <div class="hg-service-icon">🔔</div>
                        <span>התראות</span>
                    </a>
                </div>
            </section>

            <section class="hg-panel">
                <div class="hg-tabs">
                    <span class="hg-tab active">אודות HealthGuard</span>
                </div>

                <div class="hg-panel-content">
                    <h3>אודות האתר</h3>
                    <p>
                        HealthGuard היא מערכת לניהול בריאות שעוזרת למטופלים להגיע לרופא המתאים
                        לפי המחלה והתמחות הרופא. המטופל בוחר מחלה והמערכת מציגה רופאים מתאימים
                        בצורה פשוטה ומסודרת.
                    </p>

                    <p>
                        אפשר גם להזמין או לבטל תורים, לשלוח הודעות לרופאים, לצפות בפרטים אישיים
                        ובדוחות ולעקוב אחר תוכנית תזונה שבועית—הכול מלוח עבודה אחד.
                    </p>

                    <div class="hg-info-grid">
                        <div class="hg-info-box">
                            <h4>התאמת רופא</h4>
                            <p>מציאת רופאים לפי מחלה והתמחות.</p>
                        </div>

                        <div class="hg-info-box">
                            <h4>הזמנת תורים</h4>
                            <p>בחירת תאריך ושעה מועדפים.</p>
                        </div>

                        <div class="hg-info-box">
                            <h4>הודעות</h4>
                            <p>שליחת הודעות לרופאים וקבלת תשובות ישירות.</p>
                        </div>

                        <div class="hg-info-box">
                            <h4>תוכנית תזונה</h4>
                            <p>צפייה בתוכנית התזונה השבועית שהרופא הכין.</p>
                        </div>

                        <div class="hg-info-box">
                            <h4>התראות</h4>
                            <p>קבלת התראות על תורים, הודעות, דוחות ועדכונים.</p>
                        </div>

                        <div class="hg-info-box">
                            <h4>גישה אישית</h4>
                            <p>צפייה בפרופיל, ברופאים, בתורים ובדוחות במקום אחד.</p>
                        </div>
                    </div>
                </div>
            </section>

        </main>

    </div>
</div>

</body>
</html>
