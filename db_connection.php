<?php

// Open and return one connection to the HealthGuard database.
function OpenCon()
{
    // These values tell PHP where the database is and how to connect to it.
    $dbhost = "localhost";
    $dbuser = "root";
    $dbpass = "";
    $db = "healthguard";

    // Try to connect to MySQL with the values above.
    $conn = mysqli_connect(
        $dbhost,
        $dbuser,
        $dbpass,
        $db
    );

    // Stop the page and show the database error when the connection fails.
    if (!$conn) {
        die("Connect failed: " . mysqli_connect_error());
    }

    // Give the open connection back to the page that called this function.
    return $conn;
}

// Close a database connection after the page finishes using it.
function CloseCon($conn)
{
    mysqli_close($conn);
}

// Translate saved English database values to the Hebrew text shown on pages.
function HebrewText($value)
{
    // Each database value points to the text that the user should see.
    $translations = [
        'admin' => 'מנהל',
        'doctor' => 'רופא',
        'patient' => 'מטופל',
        'secretary' => 'מזכירות',
        'pending' => 'ממתין',
        'accepted' => 'אושר',
        'completed' => 'הושלם',
        'cancelled' => 'בוטל',
        'verified' => 'אושר',
        'rejected' => 'נדחה',
        'taken' => 'נלקחה',
        'skipped' => 'דולגה',
        'appointment' => 'תור',
        'medicine' => 'תרופה',
        'message' => 'הודעה',
        'report' => 'דוח',
        'nutrition' => 'תזונה',
        'cardiologist' => 'קרדיולוג',
        'dermatologist' => 'רופא עור',
        'ophthalmologist' => 'רופא עיניים',
        'orthopedic' => 'אורתופד',
        'dentist' => 'רופא שיניים',
        'dietitian' => 'דיאטן',
        'family_doctor' => 'רופא משפחה',
        'Sunday' => 'ראשון',
        'Monday' => 'שני',
        'Tuesday' => 'שלישי',
        'Wednesday' => 'רביעי',
        'Thursday' => 'חמישי',
        'Friday' => 'שישי',
        'Saturday' => 'שבת',
        'male' => 'זכר',
        'female' => 'נקבה',
        'other' => 'אחר',
        'Patients' => 'מטופלים',
        'Doctors' => 'רופאים',
        'Appointments' => 'תורים',
        'Reports' => 'דוחות',
    ];

    // Return the original value when no translation exists.
    return $translations[$value] ?? $value;
}

?>
