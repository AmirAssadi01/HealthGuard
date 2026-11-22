<?php

// Save one notification for one user.
function CreateNotification(
    $conn,
    $user_id,
    $type,
    $message,
    $link = "",
    $reference = null
) {
    // Make sure the user ID is stored as a number.
    $user_id = (int)$user_id;

    // Use SQL NULL when this notification does not have a reference key.
    if ($reference === null) {
        $reference_sql = "NULL";
    } else {
        $reference_sql = "'$reference'";
    }

    // Add the notification. INSERT IGNORE avoids a repeated reference key.
    $sql = "INSERT IGNORE INTO notifications (
                user_id,
                type,
                message,
                link,
                reference_key,
                created_at
            )
            VALUES (
                '$user_id',
                '$type',
                '$message',
                '$link',
                $reference_sql,
                NOW()
            )";

    // Tell the caller whether the notification was saved.
    return mysqli_query($conn, $sql);
}

// Create appointment and medicine reminders for a patient.
function GenerateAutomaticNotifications($conn, $user_id, $role)
{
    // Other roles do not need patient health reminders.
    if ($role != "patient") {
        return;
    }

    // Make sure the user ID is a number before using it in queries.
    $user_id = (int)$user_id;

    // Read the appointment reminder time chosen in system settings.
    $setting_query = "SELECT setting_value
                      FROM system_settings
                      WHERE setting_name='appointment_reminder'";
    $setting_result = mysqli_query($conn, $setting_query);
    $setting = mysqli_fetch_assoc($setting_result);
    $setting_value = $setting['setting_value'] ?? "1 day before";

    // The normal reminder is one day before the appointment.
    $reminder_days = 1;

    // Change the number of reminder days when the setting says so.
    if (str_starts_with($setting_value, "Same")) {
        $reminder_days = 0;
    } elseif (str_starts_with($setting_value, "2")) {
        $reminder_days = 2;
    }

    // Load active appointments inside the selected reminder period.
    $appointments_query = "SELECT
                               appointment_id,
                               appointment_date,
                               appointment_time
                           FROM appointments
                           WHERE patient_id='$user_id'
                           AND status IN ('pending', 'accepted')
                           AND appointment_date BETWEEN CURDATE()
                           AND DATE_ADD(
                               CURDATE(),
                               INTERVAL $reminder_days DAY
                           )";
    $appointments = mysqli_query($conn, $appointments_query);

    // Create one notification for every appointment that was found.
    while ($appointment = mysqli_fetch_assoc($appointments)) {
        $appointment_id = $appointment['appointment_id'];
        $appointment_date = $appointment['appointment_date'];
        $appointment_time = substr($appointment['appointment_time'], 0, 5);

        CreateNotification(
            $conn,
            $user_id,
            "appointment",
            "תזכורת לתור: $appointment_date בשעה $appointment_time",
            "/HealthGuard/patient/my_appointments.php",
            "appointment_$appointment_id"
        );
    }

    // Load medicines prescribed during the last 30 days.
    $medications_query = "SELECT
                              report_drugs.report_drug_id,
                              drugs.drug_name,
                              report_drugs.dosage
                          FROM report_drugs
                          JOIN drugs
                              ON report_drugs.drug_id=drugs.drug_id
                          JOIN medical_reports
                              ON report_drugs.report_id=medical_reports.report_id
                          WHERE medical_reports.patient_id='$user_id'
                          AND report_drugs.created_at >= DATE_SUB(
                              CURDATE(),
                              INTERVAL 30 DAY
                          )";
    $medications = mysqli_query($conn, $medications_query);

    // Create today's reminder for every recent medicine.
    while ($medication = mysqli_fetch_assoc($medications)) {
        $report_drug_id = $medication['report_drug_id'];
        $drug_name = $medication['drug_name'];
        $dosage = $medication['dosage'];
        $today = date("Y-m-d");

        CreateNotification(
            $conn,
            $user_id,
            "medicine",
            "תזכורת לתרופה: $drug_name $dosage",
            "/HealthGuard/patient/my_reports.php",
            "medicine_{$report_drug_id}_{$today}"
        );
    }
}

// Return the supported cities and the coordinates used for distance checks.
function HealthGuardCities()
{
    return [
        "Acre" => [32.928, 35.083],
        "Afula" => [32.610, 35.289],
        "Ashdod" => [31.801, 34.643],
        "Ashkelon" => [31.668, 34.574],
        "Beersheba" => [31.252, 34.791],
        "Carmiel" => [32.919, 35.305],
        "Eilat" => [29.558, 34.948],
        "Haifa" => [32.794, 34.990],
        "Hadera" => [32.435, 34.920],
        "Herzliya" => [32.166, 34.843],
        "Jerusalem" => [31.768, 35.214],
        "Kiryat Ata" => [32.811, 35.113],
        "Kiryat Bialik" => [32.827, 35.085],
        "Kiryat Motzkin" => [32.836, 35.076],
        "Kiryat Yam" => [32.850, 35.070],
        "Nahariya" => [33.008, 35.098],
        "Nazareth" => [32.700, 35.304],
        "Nesher" => [32.766, 35.050],
        "Netanya" => [32.321, 34.853],
        "Petah Tikva" => [32.087, 34.887],
        "Rishon LeZion" => [31.973, 34.792],
        "Safed" => [32.965, 35.498],
        "Tel Aviv" => [32.085, 34.782],
        "Tiberias" => [32.794, 35.532],
        "Yokneam" => [32.660, 35.110],
    ];
}

// Calculate the straight-line distance between two supported cities.
function CityDistanceKm($from, $to)
{
    // The distance is zero when both city names are the same.
    if (strcasecmp($from, $to) === 0) {
        return 0.0;
    }

    // Load the city coordinates and stop when a city is not supported.
    $cities = HealthGuardCities();

    if (!isset($cities[$from], $cities[$to])) {
        return null;
    }

    // Read both coordinate pairs.
    [$lat1, $lon1] = $cities[$from];
    [$lat2, $lon2] = $cities[$to];

    // Convert the coordinate differences from degrees to radians.
    $lat_delta = deg2rad($lat2 - $lat1);
    $lon_delta = deg2rad($lon2 - $lon1);

    // Use the Haversine formula to measure the distance on Earth.
    $a = sin($lat_delta / 2) ** 2
        + cos(deg2rad($lat1))
        * cos(deg2rad($lat2))
        * sin($lon_delta / 2) ** 2;

    // Earth has an average radius of 6,371 kilometres.
    return 6371 * 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );
}
