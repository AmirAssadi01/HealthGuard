<?php

// Start the session and load the database and distance functions.
session_start();


include("../db_connection.php");
include("../app_helpers.php");

// Only a patient may view doctor search results.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != "patient") {
    header("Location: /HealthGuard/login.php");
    exit();
}


// Open the database and prepare the search values.
$conn = OpenCon();


$illness_id = (int)($_POST['illness_id'] ?? 0);
$specialization = "";
$illness_name = "";
$search_radius = 50;
$doctors = [];

// Load the signed-in patient's city.
$user_id = (int)$_SESSION['user_id'];
$city_sql = "SELECT city
             FROM users
             WHERE user_id='$user_id'";
$city_result = mysqli_query($conn, $city_sql);
$patient_city = mysqli_fetch_assoc($city_result)['city'] ?? '';


// Load the illness and the doctor specialization that treats it.
$sqlIllness = "SELECT
                   illness_name,
                   specialization
               FROM illnesses
               WHERE illness_id='$illness_id'";
$resultIllness = mysqli_query($conn, $sqlIllness);
$illness = mysqli_fetch_assoc($resultIllness);


// Copy the illness values when a matching illness was found.
if ($illness) {
    $specialization = $illness['specialization'];
    $illness_name = $illness['illness_name'];
}


// Load matching doctors and keep only doctors inside the search radius.
if ($specialization && $patient_city) {
    $doctors_sql = "SELECT
                        doctors.doctor_id,
                        users.name,
                        users.email,
                        users.city,
                        doctors.specialization
                    FROM doctors
                    JOIN users
                        ON doctors.user_id=users.user_id
                    WHERE doctors.specialization='$specialization'
                    AND users.city IS NOT NULL
                    AND users.city<>''";
    $doctor_result = mysqli_query($conn, $doctors_sql);

    // Measure every doctor's distance from the patient's city.
    while ($doctor = mysqli_fetch_assoc($doctor_result)) {
        $distance = CityDistanceKm($patient_city, $doctor['city']);
        if ($distance !== null && $distance <= $search_radius) {
            $doctor['distance'] = $distance;
            $doctors[] = $doctor;
        }
    }

    // Show the nearest doctor first.
    usort(
        $doctors,
        function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        }
    );
}

// Use a helpful fallback when no patient city is available.
$patient_city_text = 'your city';

if ($patient_city) {
    $patient_city_text = $patient_city;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>רופאים</title>
    <link rel="stylesheet" href="/HealthGuard/style.css">
</head>
<body class="patient-page">

<?php include("../navbar.php"); ?>

<div class="section">
    <h1 class="section-title">רופאים זמינים</h1>
    <p class="section-subtitle">
        רופאים עבור <?php echo $illness_name; ?> בטווח של <?php echo $search_radius; ?> ק״מ מהעיר
        <?php echo $patient_city_text; ?>
    </p>

    <div class="hero-actions" style="justify-content:center; margin-bottom:25px;">
        <a href="/HealthGuard/patient/patient_dashboard.php" class="secondary-btn">חזרה ללוח הראשי</a>
        <a href="/HealthGuard/patient/find_doctor.php" class="primary-btn">חיפוש מחדש</a>
    </div>

    <div class="cards">

        <?php if ($doctors) { ?>
            <?php foreach ($doctors as $row) { ?>
                <div class="card">
                    <h3>Dr. <?php echo $row['name']; ?></h3>
                    <p><strong>התמחות:</strong> <?php echo HebrewText($row['specialization']); ?></p>
                    <p><strong>עיר:</strong> <?php echo $row['city']; ?></p>
                    <p><strong>מרחק:</strong> <?php echo number_format($row['distance'], 1); ?> km</p>
                    <p><strong>אימייל:</strong> <?php echo $row['email']; ?></p>

                    <div class="hero-actions" style="justify-content:center; margin-top:15px;">
                        <a href="/HealthGuard/patient/book_appointment.php?doctor_id=<?php echo $row['doctor_id']; ?>" class="primary-btn">הזמנת תור</a>
                        <a href="/HealthGuard/patient/my_messages.php?doctor_id=<?php echo $row['doctor_id']; ?>" class="secondary-btn">שליחת הודעה לרופא</a>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="card">
                <h3>לא נמצא רופא קרוב</h3>
                <p>לא נמצא רופא מתאים בטווח של <?php echo $search_radius; ?> ק״מ. יש לוודא שהעיר מוגדרת בפרופיל שלי.</p>
            </div>
        <?php } ?>

    </div>
</div>

</body>
</html>
<?php
// Close the database after the results page has been printed.
CloseCon($conn);
?>
