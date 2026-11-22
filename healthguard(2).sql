-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 07:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `healthguard`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(3, 3, 6, '2026-05-04', '09:45:00', 'pending', '2026-05-04 20:30:26'),
(4, 3, 8, '2026-05-11', '12:15:00', 'completed', '2026-05-05 00:44:15'),
(5, 3, 8, '2026-05-13', '00:12:00', 'pending', '2026-05-05 00:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `consultation_fee` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialization`, `consultation_fee`) VALUES
(1, 5, 'cardiologist', NULL),
(2, 6, 'dermatologist', NULL),
(3, 7, 'ophthalmologist', NULL),
(4, 8, 'orthopedic', NULL),
(5, 9, 'dentist', NULL),
(6, 10, 'cardiologist', NULL),
(7, 16, 'dentist', NULL),
(8, 19, 'family_doctor', 123.00);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `schedule_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_schedules`
--

INSERT INTO `doctor_schedules` (`schedule_id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`) VALUES
(15, 6, 'Sunday', '09:00:00', '17:00:00', 30),
(16, 6, 'Monday', '09:00:00', '17:00:00', 15),
(17, 6, 'Tuesday', '09:00:00', '17:00:00', 15),
(18, 6, 'Wednesday', '09:00:00', '17:00:00', 15),
(19, 6, 'Thursday', '09:00:00', '17:00:00', 15),
(20, 6, 'Friday', '09:00:00', '17:00:00', 15),
(21, 6, 'Saturday', '09:00:00', '17:00:00', 15),
(22, 8, 'Sunday', '09:00:00', '17:00:00', 15),
(23, 8, 'Monday', '09:00:00', '17:00:00', 15),
(24, 8, 'Tuesday', '09:00:00', '17:00:00', 15),
(25, 8, 'Wednesday', '09:00:00', '17:00:00', 15),
(26, 8, 'Thursday', '09:00:00', '17:00:00', 15),
(27, 8, 'Friday', '09:00:00', '17:00:00', 15),
(28, 8, 'Saturday', '09:00:00', '17:00:00', 15);

-- --------------------------------------------------------

--
-- Table structure for table `drugs`
--

CREATE TABLE `drugs` (
  `drug_id` int(11) NOT NULL,
  `drug_name` varchar(150) NOT NULL,
  `allergy_group` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drugs`
--

INSERT INTO `drugs` (`drug_id`, `drug_name`, `allergy_group`, `notes`, `created_at`) VALUES
(1, 'Amoxicillin', 'Penicillin', 'Antibiotic', '2026-04-03 07:04:27'),
(2, 'Azithromycin', 'Macrolide', 'Antibiotic', '2026-04-03 07:04:27'),
(3, 'Ibuprofen', 'NSAID', 'Pain reliever', '2026-04-03 07:04:27'),
(4, 'Paracetamol', 'Paracetamol', 'Pain reliever / fever', '2026-04-03 07:04:27'),
(5, 'Diclofenac', 'NSAID', 'Pain reliever', '2026-04-03 07:04:27'),
(6, 'Cetirizine', 'Antihistamine', 'Allergy medicine', '2026-04-03 07:04:27'),
(7, 'Metformin', 'Metformin', 'Diabetes medicine', '2026-04-03 07:04:27'),
(8, 'Omeprazole', 'PPI', 'Stomach acid medicine', '2026-04-03 07:04:27');

-- --------------------------------------------------------

--
-- Table structure for table `family_doctor_uploads`
--

CREATE TABLE `family_doctor_uploads` (
  `upload_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `secretary_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `secretary_note` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `linked_appointment_id` int(11) DEFAULT NULL,
  `consumed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `family_doctor_uploads`
--

INSERT INTO `family_doctor_uploads` (`upload_id`, `patient_id`, `file_path`, `original_filename`, `uploaded_at`, `secretary_status`, `secretary_note`, `verified_by`, `verified_at`, `linked_appointment_id`, `consumed_at`) VALUES
(1, 3, 'uploads/family_reports/a839a9c1f3ae58b64a770b6eae87a0c5.png', 'Screenshot 2025-04-30 200018.png', '2026-05-05 00:37:24', 'rejected', 'approved', 20, '2026-05-05 00:59:53', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `illnesses`
--

CREATE TABLE `illnesses` (
  `illness_id` int(11) NOT NULL,
  `illness_name` varchar(100) NOT NULL,
  `specialization` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `illnesses`
--

INSERT INTO `illnesses` (`illness_id`, `illness_name`, `specialization`) VALUES
(1, 'Heart Problem', 'cardiologist'),
(2, 'Skin Problem', 'dermatologist'),
(3, 'Eye Problem', 'ophthalmologist'),
(4, 'Bone Problem', 'orthopedic'),
(5, 'Teeth Problem', 'dentist'),
(6, 'Dietary Problem', 'dietitian'),
(7, 'General Health', 'family_doctor');

-- --------------------------------------------------------

--
-- Table structure for table `medical_reports`
--

CREATE TABLE `medical_reports` (
  `report_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `diagnosis` text NOT NULL,
  `examination` text DEFAULT NULL,
  `treatment_done` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `follow_up_needed` enum('no','yes') DEFAULT 'no',
  `follow_up_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_family_doctor_report` tinyint(1) NOT NULL DEFAULT 0,
  `secretary_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `secretary_note` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medical_reports`
--

INSERT INTO `medical_reports` (`report_id`, `appointment_id`, `patient_id`, `doctor_id`, `diagnosis`, `examination`, `treatment_done`, `doctor_notes`, `follow_up_needed`, `follow_up_note`, `created_at`, `updated_at`, `is_family_doctor_report`, `secretary_status`, `secretary_note`, `verified_by`, `verified_at`) VALUES
(2, 4, 3, 8, 'bones', 'a', 'bv', 'cv', 'yes', 'asd', '2026-05-05 00:46:59', '2026-05-05 01:09:00', 1, 'rejected', '', 20, '2026-05-05 01:09:00');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `content`, `timestamp`) VALUES
(1, 3, 10, 'hello doctor', '2026-03-29 22:43:12'),
(2, 10, 3, 'hello how can i help you', '2026-03-29 22:44:13'),
(3, 10, 3, 'hi', '2026-04-06 03:20:38'),
(4, 3, 10, 'yes', '2026-04-06 03:30:57'),
(5, 10, 3, 'ok', '2026-04-06 03:31:25'),
(6, 10, 3, 'okey', '2026-04-06 05:28:22'),
(7, 3, 10, 'asd', '2026-05-04 17:26:22'),
(8, 3, 19, 'asd', '2026-05-04 21:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_plans`
--

CREATE TABLE `nutrition_plans` (
  `plan_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `breakfast` text DEFAULT NULL,
  `lunch` text DEFAULT NULL,
  `dinner` text DEFAULT NULL,
  `snack` text DEFAULT NULL,
  `calories_goal` int(11) DEFAULT NULL,
  `illness_advice` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nutrition_plans`
--

INSERT INTO `nutrition_plans` (`plan_id`, `patient_id`, `doctor_id`, `week_start`, `day_name`, `breakfast`, `lunch`, `dinner`, `snack`, `calories_goal`, `illness_advice`, `created_at`, `updated_at`) VALUES
(8, 3, 6, '2030-05-27', 'Sunday', 'egg', 'sandwich', 'steak', 'chips', 2500, 'none', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(9, 3, 6, '2030-05-27', 'Monday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(10, 3, 6, '2030-05-27', 'Tuesday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(11, 3, 6, '2030-05-27', 'Wednesday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(12, 3, 6, '2030-05-27', 'Thursday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(13, 3, 6, '2030-05-27', 'Friday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(14, 3, 6, '2030-05-27', 'Saturday', '', '', '', '', NULL, '', '2026-04-27 10:37:51', '2026-04-27 10:37:51'),
(29, 3, 6, '2026-04-27', 'Sunday', 'egg', 'sandwich', 'steak', 'chips', 2500, 'none', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(30, 3, 6, '2026-04-27', 'Monday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(31, 3, 6, '2026-04-27', 'Tuesday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(32, 3, 6, '2026-04-27', 'Wednesday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(33, 3, 6, '2026-04-27', 'Thursday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(34, 3, 6, '2026-04-27', 'Friday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(35, 3, 6, '2026-04-27', 'Saturday', '', '', '', '', NULL, '', '2026-04-27 10:39:54', '2026-04-27 10:39:54'),
(43, 3, 6, '2026-05-14', 'Sunday', 'asd', '', '', '', 123, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(44, 3, 6, '2026-05-14', 'Monday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(45, 3, 6, '2026-05-14', 'Tuesday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(46, 3, 6, '2026-05-14', 'Wednesday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(47, 3, 6, '2026-05-14', 'Thursday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(48, 3, 6, '2026-05-14', 'Friday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08'),
(49, 3, 6, '2026-05-14', 'Saturday', '', '', '', '', NULL, '', '2026-05-04 20:49:08', '2026-05-04 20:49:08');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `medical_notes` text DEFAULT NULL,
  `current_weight` decimal(5,2) DEFAULT NULL,
  `waist_cm` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `age`, `gender`, `medical_notes`, `current_weight`, `waist_cm`) VALUES
(1, 3, 20, 'male', NULL, 123.00, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `patient_allergies`
--

CREATE TABLE `patient_allergies` (
  `allergy_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `allergy_name` varchar(150) NOT NULL,
  `reaction` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_allergies`
--

INSERT INTO `patient_allergies` (`allergy_id`, `patient_id`, `allergy_name`, `reaction`, `created_at`) VALUES
(2, 3, 'Penicillin', 'swelling', '2026-04-03 07:31:20');

-- --------------------------------------------------------

--
-- Table structure for table `report_drugs`
--

CREATE TABLE `report_drugs` (
  `report_drug_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `drug_id` int(11) NOT NULL,
  `dosage` varchar(100) NOT NULL,
  `frequency` varchar(100) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_drugs`
--

INSERT INTO `report_drugs` (`report_drug_id`, `report_id`, `drug_id`, `dosage`, `frequency`, `duration`, `instructions`, `created_at`) VALUES
(3, 2, 3, '100mg', '2 times a day', '3 days', 'before sleep', '2026-05-05 00:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `secretaries`
--

CREATE TABLE `secretaries` (
  `secretary_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `secretaries`
--

INSERT INTO `secretaries` (`secretary_id`, `user_id`) VALUES
(1, 20);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` enum('patient','doctor','admin','secretary') NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `city` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `role`, `email`, `password`, `created_at`) VALUES
(3, 'amir', 'patient', 'amir@example.com', '$2y$10$YwJI25D9Pu0nOKQqEqgBs.rc2dnqyE0BDyh5fTu392SNCVoCP6Pjq', '2026-03-10 18:24:26'),
(4, 'Admin User', 'admin', 'admin@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(5, 'Dr. Ahmad', 'doctor', 'doctor1@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(6, 'Dr. Sara', 'doctor', 'doctor2@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(7, 'Dr. Ali', 'doctor', 'doctor3@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(8, 'Dr. Lina', 'doctor', 'doctor4@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(9, 'Dr. Omar', 'doctor', 'doctor5@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 22:41:45'),
(10, 'david', 'doctor', 'david@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-03-22 23:47:31'),
(16, 'mike', 'doctor', 'mike@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-04-19 18:12:35'),
(18, 'cizar', 'admin', 'cizar@gmail.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-04-19 18:20:36'),
(19, 'asd', 'doctor', 'blabla@example.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-05-04 17:17:53'),
(20, 'sec', 'secretary', 'sec@example.com', '$2y$10$bMeyMo4IJFgCCjT8wCgfpOqkoCugXiKWGnPjyUxHs17bRA.Fw8yje', '2026-05-04 17:25:50');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_nutrition_reports`
--

CREATE TABLE `weekly_nutrition_reports` (
  `report_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `start_weight` decimal(5,2) DEFAULT NULL,
  `end_weight` decimal(5,2) DEFAULT NULL,
  `weight_change` decimal(5,2) DEFAULT NULL,
  `waist_cm` decimal(5,2) DEFAULT NULL,
  `total_calories` int(11) DEFAULT 0,
  `report_text` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `fk_appointments_patient_user` (`patient_id`),
  ADD KEY `fk_appointments_doctors` (`doctor_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_doctors_illnesses_specialization` (`specialization`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `uq_doctor_day` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `drugs`
--
ALTER TABLE `drugs`
  ADD PRIMARY KEY (`drug_id`),
  ADD UNIQUE KEY `drug_name` (`drug_name`);

--
-- Indexes for table `family_doctor_uploads`
--
ALTER TABLE `family_doctor_uploads`
  ADD PRIMARY KEY (`upload_id`),
  ADD KEY `idx_patient_status` (`patient_id`,`secretary_status`);

--
-- Indexes for table `illnesses`
--
ALTER TABLE `illnesses`
  ADD PRIMARY KEY (`illness_id`),
  ADD UNIQUE KEY `specialization` (`specialization`);

--
-- Indexes for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `fk_medical_reports_patient_user` (`patient_id`),
  ADD KEY `fk_medical_reports_doctor` (`doctor_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_messages_sender` (`sender_id`),
  ADD KEY `fk_messages_receiver` (`receiver_id`);

--
-- Indexes for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `idx_nutrition_patient_id` (`patient_id`),
  ADD KEY `idx_nutrition_doctor_id` (`doctor_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD PRIMARY KEY (`allergy_id`),
  ADD KEY `fk_patient_allergies_user` (`patient_id`);

--
-- Indexes for table `report_drugs`
--
ALTER TABLE `report_drugs`
  ADD PRIMARY KEY (`report_drug_id`),
  ADD KEY `fk_report_drugs_report` (`report_id`),
  ADD KEY `fk_report_drugs_drug` (`drug_id`);

--
-- Indexes for table `secretaries`
--
ALTER TABLE `secretaries`
  ADD PRIMARY KEY (`secretary_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_nutrition_reports`
--
ALTER TABLE `weekly_nutrition_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `drugs`
--
ALTER TABLE `drugs`
  MODIFY `drug_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `family_doctor_uploads`
--
ALTER TABLE `family_doctor_uploads`
  MODIFY `upload_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `illnesses`
--
ALTER TABLE `illnesses`
  MODIFY `illness_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `medical_reports`
--
ALTER TABLE `medical_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  MODIFY `allergy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `report_drugs`
--
ALTER TABLE `report_drugs`
  MODIFY `report_drug_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `secretaries`
--
ALTER TABLE `secretaries`
  MODIFY `secretary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `weekly_nutrition_reports`
--
ALTER TABLE `weekly_nutrition_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_doctors` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appointments_patient_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `fk_doctors_illnesses_specialization` FOREIGN KEY (`specialization`) REFERENCES `illnesses` (`specialization`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_doctors_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `fk_schedules_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `family_doctor_uploads`
--
ALTER TABLE `family_doctor_uploads`
  ADD CONSTRAINT `fk_uploads_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `medical_reports`
--
ALTER TABLE `medical_reports`
  ADD CONSTRAINT `fk_medical_reports_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medical_reports_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medical_reports_patient_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nutrition_plans`
--
ALTER TABLE `nutrition_plans`
  ADD CONSTRAINT `fk_nutrition_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nutrition_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD CONSTRAINT `fk_patient_allergies_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `report_drugs`
--
ALTER TABLE `report_drugs`
  ADD CONSTRAINT `fk_report_drugs_drug` FOREIGN KEY (`drug_id`) REFERENCES `drugs` (`drug_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_drugs_report` FOREIGN KEY (`report_id`) REFERENCES `medical_reports` (`report_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `secretaries`
--
ALTER TABLE `secretaries`
  ADD CONSTRAINT `fk_secretaries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

-- Extra HealthGuard proposal features
CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(40) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `reference_key` varchar(120) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `unique_user_reference` (`user_id`,`reference_key`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `medication_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `report_drug_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `taken_date` date NOT NULL,
  `taken_time` time DEFAULT NULL,
  `status` enum('taken','skipped') NOT NULL DEFAULT 'taken',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_medication_prescription` (`report_drug_id`),
  KEY `idx_medication_patient` (`patient_id`),
  CONSTRAINT `fk_log_prescription` FOREIGN KEY (`report_drug_id`) REFERENCES `report_drugs` (`report_drug_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `password_reset_tokens` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `unique_password_reset_hash` (`token_hash`),
  KEY `idx_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `diary_entries` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `entry_date` date NOT NULL,
  `title` varchar(120) NOT NULL,
  `notes` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`entry_id`),
  CONSTRAINT `fk_diary_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `system_settings` (
  `setting_name` varchar(80) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `system_settings` VALUES
('site_name','HealthGuard'),('appointment_reminder','1 day before');

ALTER TABLE `users`
  ADD `phone` varchar(30) DEFAULT NULL,
  ADD `national_id` varchar(20) DEFAULT NULL;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
