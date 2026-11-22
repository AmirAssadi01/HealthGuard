<?php
// Start the session only when another page has not started it already.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// These values control what the navigation bar should display.
$logged_in = isset($_SESSION['user_id'], $_SESSION['role']);
$notification_count = 0;
$site_name = "HealthGuard";

if ($logged_in) {
    // Load shared functions needed by the navigation bar.
    include_once(__DIR__ . "/db_connection.php");
    include_once(__DIR__ . "/app_helpers.php");

    // Open one short database connection for navigation information.
    $nav_conn = OpenCon();

    // Create any reminders that are due for this user.
    GenerateAutomaticNotifications($nav_conn, $_SESSION['user_id'], $_SESSION['role']);

    // Count unread notifications for the red sidebar badge.
    $nav_user_id = (int)$_SESSION['user_id'];
    $count_sql = "SELECT COUNT(*) AS total
                  FROM notifications
                  WHERE user_id='$nav_user_id'
                  AND is_read=0";
    $count_result = mysqli_query($nav_conn, $count_sql);
    $count_row = null;

    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
    }

    $notification_count = (int)($count_row['total'] ?? 0);

    // Load the site name saved by the administrator.
    $site_sql = "SELECT setting_value
                 FROM system_settings
                 WHERE setting_name='site_name'";
    $site_result = mysqli_query($nav_conn, $site_sql);
    $site_row = null;

    if ($site_result) {
        $site_row = mysqli_fetch_assoc($site_result);
    }

    $site_name = $site_row['setting_value'] ?? $site_name;

    // The navigation bar no longer needs the database connection.
    CloseCon($nav_conn);
}

// Each role receives only the links that belong to that role.
$menus = [
    'admin' => [
        '/HealthGuard/admin/admin_dashboard.php' => 'לוח ראשי',
        '/HealthGuard/admin/manage_users.php' => 'משתמשים',
        '/HealthGuard/admin/manage_doctors.php' => 'רופאים',
        '/HealthGuard/admin/manage_patients.php' => 'מטופלים',
        '/HealthGuard/admin/manage_secretaries.php' => 'מזכירות',
        '/HealthGuard/admin/manage_illnesses.php' => 'מחלות',
        '/HealthGuard/admin/manage_drugs.php' => 'תרופות',
        '/HealthGuard/admin/add_admin.php' => 'הוספת מנהל',
        '/HealthGuard/admin/charts.php' => 'תרשימים',
        '/HealthGuard/admin/settings.php' => 'הגדרות',
    ],
    'doctor' => [
        '/HealthGuard/doctor/doctor_dashboard.php' => 'לוח ראשי',
        '/HealthGuard/doctor/my_profile.php' => 'הפרופיל שלי',
        '/HealthGuard/doctor/my_patients.php' => 'המטופלים שלי',
        '/HealthGuard/doctor/my_appointments.php' => 'תורים',
        '/HealthGuard/doctor/my_reports.php' => 'דוחות',
        '/HealthGuard/doctor/my_messages.php' => 'הודעות',
        '/HealthGuard/doctor/my_schedule.php' => 'לוח הזמנים שלי',
        '/HealthGuard/notifications.php' => 'התראות',
    ],
    'patient' => [
        '/HealthGuard/patient/patient_dashboard.php' => 'לוח ראשי',
        '/HealthGuard/patient/find_doctor.php' => 'חיפוש רופא',
        '/HealthGuard/patient/my_appointments.php' => 'תורים',
        '/HealthGuard/patient/my_messages.php' => 'הודעות',
        '/HealthGuard/patient/my_doctors.php' => 'הרופאים שלי',
        '/HealthGuard/patient/my_reports.php' => 'דוחות',
        '/HealthGuard/patient/my_medications.php' => 'תרופות',
        '/HealthGuard/patient/my_allergies.php' => 'אלרגיות',
        '/HealthGuard/patient/my_nutrition_plan.php' => 'תוכנית תזונה',
        '/HealthGuard/patient/medical_history.php' => 'היסטוריה רפואית',
        '/HealthGuard/patient/progress.php' => 'ההתקדמות שלי',
        '/HealthGuard/patient/upload_family_report.php' => 'העלאת הפניה',
        '/HealthGuard/patient/my_profile.php' => 'הפרופיל שלי',
        '/HealthGuard/notifications.php' => 'התראות',
    ],
    'secretary' => [
        '/HealthGuard/secretary/secretary_dashboard.php' => 'לוח ראשי',
    ],
];

// Read the current page path so the matching sidebar link can be highlighted.
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Detail pages highlight the main page that opened them.
$active_aliases = [
    '/HealthGuard/patient/show_doctors.php' => '/HealthGuard/patient/find_doctor.php',
    '/HealthGuard/patient/book_appointment.php' => '/HealthGuard/patient/find_doctor.php',
    '/HealthGuard/patient/edit_appointment.php' => '/HealthGuard/patient/my_appointments.php',
    '/HealthGuard/patient/delete_appointment.php' => '/HealthGuard/patient/my_appointments.php',
    '/HealthGuard/doctor/manage_appointment.php' => '/HealthGuard/doctor/my_appointments.php',
    '/HealthGuard/doctor/manage_nutrition.php' => '/HealthGuard/doctor/my_patients.php',
    '/HealthGuard/secretary/verify_upload.php' => '/HealthGuard/secretary/secretary_dashboard.php',
];
$active_path = $active_aliases[$current_path] ?? $current_path;

// Dashboard pages keep their card layout and do not need the second sidebar.
$dashboards = [
    '/HealthGuard/admin/admin_dashboard.php',
    '/HealthGuard/doctor/doctor_dashboard.php',
    '/HealthGuard/patient/patient_dashboard.php',
    '/HealthGuard/secretary/secretary_dashboard.php',
];
$show_sidebar = $logged_in && !in_array($current_path, $dashboards);

// These names are used in the sidebar heading.
$role_labels = [
    'admin' => 'מנהל',
    'doctor' => 'רופא',
    'patient' => 'מטופל',
    'secretary' => 'מזכירות',
];
?>

<nav class="navbar">
    <div class="nav-left">
        <?php if ($show_sidebar) { ?>
            <button
                class="sidebar-toggle"
                type="button"
                aria-label="פתיחת תפריט"
                onclick="toggleSidebar()"
            >
                &#9776;
            </button>
        <?php } ?>
        <a href="/HealthGuard/index.php" class="logo"><?php echo $site_name; ?></a>
    </div>

    <div class="nav-right">
        <?php if ($logged_in) { ?>
            <span class="nav-user"><?php echo $_SESSION['name'] ?? ucfirst($_SESSION['role']); ?></span>
            <a href="/HealthGuard/logout.php" class="nav-logout">יציאה</a>
        <?php } else { ?>
            <a href="/HealthGuard/index.php">בית</a>
            <a href="/HealthGuard/signup.php">הרשמה</a>
        <?php } ?>
    </div>
</nav>

<?php if ($show_sidebar) { ?>
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-heading">
            תפריט <?php echo $role_labels[$_SESSION['role']]; ?>
        </div>
        <div class="sidebar-links">
            <?php foreach ($menus[$_SESSION['role']] ?? [] as $link => $label) { ?>
                <?php
                // Add the active class only to the link for the current page.
                $active_class = '';

                if ($active_path === $link) {
                    $active_class = ' active';
                }
                ?>
                <a
                    href="<?php echo $link; ?>"
                    class="sidebar-link<?php echo $active_class; ?>"
                >
                    <span><?php echo $label; ?></span>
                    <?php if ($label === 'התראות' && $notification_count > 0) { ?>
                        <span class="sidebar-badge"><?php echo $notification_count; ?></span>
                    <?php } ?>
                </a>
            <?php } ?>
        </div>
        <a href="/HealthGuard/logout.php" class="sidebar-logout">יציאה</a>
    </aside>
    <button
        class="sidebar-overlay"
        id="sidebarOverlay"
        type="button"
        aria-label="סגירת תפריט"
        onclick="toggleSidebar(false)"
    ></button>
    <script>
        // This class gives the page enough space for the sidebar.
        document.body.classList.add('has-app-sidebar');

        // Open or close the sidebar on small screens.
        function toggleSidebar(open) {
            var shouldOpen;

            if (typeof open === 'boolean') {
                shouldOpen = open;
            } else {
                shouldOpen = !document.body.classList.contains('sidebar-open');
            }

            document.body.classList.toggle('sidebar-open', shouldOpen);
        }
    </script>
<?php } ?>
