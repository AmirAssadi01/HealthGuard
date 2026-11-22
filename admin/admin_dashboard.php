<?php

// Start the session so the dashboard can read the signed-in account.
session_start();

// Only an administrator may open the admin dashboard.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "admin") {
    header("Location: /HealthGuard/login.php");
    exit();
}

// Use the saved name in the welcome message and avatar.
$admin_name = $_SESSION['name'];
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>לוח מנהל - HealthGuard</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="hg-patient-dashboard">

    <section class="hg-patient-header">
        <div class="hg-patient-header-text">
            <h1>ברוך הבא מנהל, <?php echo $admin_name; ?></h1>
            <p>
                נהל ביעילות את מערכת HealthGuard: משתמשים, רופאים, מטופלים,
                מחלות ותרופות מלוח עבודה אחד ונוח.
            </p>
        </div>

        <div class="hg-patient-user-box">
            <div class="hg-patient-avatar">
                <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
            </div>
            <div class="hg-patient-user-info">
                <strong>לוח מנהל</strong>
                <span><?php echo $admin_name; ?></span>
            </div>
        </div>
    </section>

    <div class="hg-patient-layout">

        <main class="hg-patient-main">

            <section class="hg-panel">
                <div class="hg-panel-head">
                    <h2>ניהול המערכת</h2>
                    <p>גישה מהירה לכלי ניהול המערכת</p>
                </div>

                <div class="hg-service-grid">

                    <a href="/HealthGuard/admin/manage_users.php" class="hg-service-card">
                        <div class="hg-service-icon">👥</div>
                        <span>משתמשים</span>
                        <p class="hg-service-desc">צפייה וניהול של משתמשי המערכת</p>
                    </a>

                    <a href="/HealthGuard/admin/manage_doctors.php" class="hg-service-card">
                        <div class="hg-service-icon">👨‍⚕️</div>
                        <span>רופאים</span>
                        <p class="hg-service-desc">הוספה, צפייה והסרה של רופאים</p>
                    </a>

                    <a href="/HealthGuard/admin/manage_patients.php" class="hg-service-card">
                        <div class="hg-service-icon">🧑</div>
                        <span>מטופלים</span>
                        <p class="hg-service-desc">הוספה, צפייה והסרה של מטופלים</p>
                    </a>

                    <a href="/HealthGuard/admin/manage_illnesses.php" class="hg-service-card">
                        <div class="hg-service-icon">🦠</div>
                        <span>מחלות</span>
                        <p class="hg-service-desc">הוספה וארגון של קטגוריות מחלה</p>
                    </a>

                    <a href="/HealthGuard/admin/manage_drugs.php" class="hg-service-card">
                        <div class="hg-service-icon">💊</div>
                        <span>תרופות</span>
                        <p class="hg-service-desc">ניהול תרופות ומרשמים</p>
                    </a>

                    <a href="/HealthGuard/admin/add_admin.php" class="hg-service-card">
                        <div class="hg-service-icon">➕</div>
                        <span>הוספת מנהל</span>
                        <p class="hg-service-desc">יצירת חשבונות מנהל חדשים</p>
                    </a>

                    <a href="/HealthGuard/admin/charts.php" class="hg-service-card">
                        <div class="hg-service-icon">📊</div>
                        <span>תרשימים</span>
                        <p class="hg-service-desc">צפייה בסטטיסטיקות ובתרשימים</p>
                    </a>

                    <a href="/HealthGuard/admin/settings.php" class="hg-service-card">
                        <div class="hg-service-icon">⚙️</div>
                        <span>הגדרות</span>
                        <p class="hg-service-desc">שינוי הגדרות המערכת</p>
                    </a>

                </div>
            </section>

        </main>

    </div>
</div>

</body>
</html>
