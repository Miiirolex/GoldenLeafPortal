-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2025 at 09:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `staff_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','completed','rejected') NOT NULL,
  `activity_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `description`, `status`, `activity_date`) VALUES
(1, 1, 'payslip', 'January 2025 Payslip Generated', 'completed', '2025-03-03 08:08:10'),
(2, 1, 'leave', 'Leave Application (Mar 15-20, 2025)', 'pending', '2025-03-03 08:08:10'),
(3, 1, 'profile', 'Profile Updated', 'completed', '2025-03-03 08:08:10'),
(4, 1, 'payslip', 'December 2024 Payslip Generated', 'completed', '2025-03-03 08:08:10');

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `leave_application_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$p0djkugxadgYvZXBxTYf2.nTFJm2EU8kmsNJJ2PqML4RuAYM/2BK6', 'System Administrator', 'admin@example.com', 'super_admin', '2025-03-06 09:02:03', '2025-03-10 05:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `education_history`
--

CREATE TABLE `education_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `degree` varchar(100) NOT NULL,
  `field_of_study` varchar(150) NOT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `honors` text DEFAULT NULL,
  `additional_activities` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_details`
--

CREATE TABLE `employment_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `employment_type` varchar(50) NOT NULL,
  `hire_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','personal','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_comments` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `created_at`, `updated_at`, `admin_comments`, `reviewed_by`, `reviewed_at`) VALUES
(4, 1, 'annual', '2025-03-10', '2025-03-12', 2, 'emergency', 'approved', '2025-03-10 06:01:30', '2025-03-10 06:04:40', 'enjoy your holiday', 1, '2025-03-10 06:04:40'),
(6, 1, 'annual', '2025-03-13', '2025-03-14', 1, 'holiday', 'approved', '2025-03-12 06:26:21', '2025-03-12 06:28:09', '', 1, '2025-03-12 06:28:09'),
(10, 1, 'sick', '2025-03-19', '2025-03-20', 1, 'demam', 'rejected', '2025-03-12 06:33:10', '2025-03-12 06:40:12', 'tkleh\r\n', 1, '2025-03-12 06:40:12'),
(11, 1, 'annual', '2025-03-14', '2025-03-15', 1, 'cuba', 'rejected', '2025-03-12 06:39:51', '2025-03-12 06:40:56', 'kl', 1, '2025-03-12 06:40:56'),
(12, 1, 'annual', '2025-03-14', '2025-03-15', 1, 'cuba', 'rejected', '2025-03-12 06:40:31', '2025-03-12 06:41:00', 'dasd', 1, '2025-03-12 06:41:00'),
(16, 1, 'annual', '2025-03-13', '2025-03-17', 4, 'djsaidjpa', 'rejected', '2025-03-12 06:45:43', '2025-03-12 06:45:58', 'dsad', 1, '2025-03-12 06:45:58'),
(17, 1, 'annual', '2025-03-13', '2025-03-17', 4, 'djsaidjpa', 'rejected', '2025-03-12 06:46:04', '2025-03-12 06:46:51', 'kfdo;s', 1, '2025-03-12 06:46:51'),
(18, 1, 'annual', '2025-03-14', '2025-03-15', 1, '1', 'approved', '2025-03-12 06:55:46', '2025-03-12 06:56:05', 'sas', 1, '2025-03-12 06:56:05'),
(19, 1, 'annual', '2025-03-12', '2025-03-14', 2, 'joidasjodsa', 'approved', '2025-03-12 06:59:17', '2025-03-12 06:59:41', 'ds jakdnakdn', 1, '2025-03-12 06:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `leave_history`
--

CREATE TABLE `leave_history` (
  `id` int(11) NOT NULL,
  `leave_application_id` int(11) NOT NULL,
  `status_from` varchar(20) NOT NULL,
  `status_to` varchar(20) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_history`
--

INSERT INTO `leave_history` (`id`, `leave_application_id`, `status_from`, `status_to`, `changed_by`, `comments`, `created_at`) VALUES
(1, 4, 'pending', 'approved', 1, 'enjoy your holiday', '2025-03-10 06:04:40'),
(2, 6, 'pending', 'approved', 1, '', '2025-03-12 06:28:09'),
(6, 10, 'pending', 'rejected', 1, 'tkleh\r\n', '2025-03-12 06:40:12'),
(7, 11, 'pending', 'rejected', 1, 'kl', '2025-03-12 06:40:56'),
(8, 12, 'pending', 'rejected', 1, 'dasd', '2025-03-12 06:41:00'),
(9, 16, 'pending', 'rejected', 1, 'dsad', '2025-03-12 06:45:58'),
(10, 17, 'pending', 'rejected', 1, 'kfdo;s', '2025-03-12 06:46:51'),
(11, 18, 'pending', 'approved', 1, 'sas', '2025-03-12 06:56:05'),
(12, 19, 'pending', 'approved', 1, 'ds jakdnakdn', '2025-03-12 06:59:41');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pay_period` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `total_earnings` decimal(10,2) NOT NULL,
  `income_tax` decimal(10,2) NOT NULL,
  `health_insurance` decimal(10,2) DEFAULT 0.00,
  `retirement_plan` decimal(10,2) DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL,
  `net_pay` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `annual_leave_balance` int(11) DEFAULT 20,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `email`, `position`, `department`, `employee_id`, `annual_leave_balance`, `created_at`, `profile_picture`, `phone`, `address`) VALUES
(1, 'Miiiirolex', '$2y$10$IlhRFUsHTZsr3uLN3Ps6MusWZ3c8orGrJALwvUmjWOBAuBwgMlF1W', 'Mirul', 'Farihin', 'amirulfarihin10@gmail.com', 'Software Developer', 'Engineering', 'EMP-001234', 4, '2025-03-03 08:08:10', NULL, '+60137120029', 'Kuala Lumpur'),
(2, 'johndoe', '$2y$10$Ra1Tcw0orXpUMBLWddimrer.HjtUMx490L5vkSWBCK4sG00rGAgFq', 'John', 'Doe', 'john@techcorp.com', 'Systems Analyst', 'IT', 'EMP-001235', 18, '2025-03-03 08:08:10', NULL, NULL, NULL),
(3, 'janedoe', '$2y$10$M2jKKgFjhHeRNiZxhq1dcuim7NDkooNeoHccUclzj17N76/TSgNT.', 'Jane', 'Doe', 'jane@techcorp.com', 'UX Designer', 'Design', 'EMP-001236', 12, '2025-03-03 08:08:10', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `work_history`
--

CREATE TABLE `work_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company` varchar(255) NOT NULL,
  `position` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `is_current_job` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_application_id` (`leave_application_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `education_history`
--
ALTER TABLE `education_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_application_id` (`leave_application_id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `work_history`
--
ALTER TABLE `work_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `education_history`
--
ALTER TABLE `education_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_details`
--
ALTER TABLE `employment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `leave_history`
--
ALTER TABLE `leave_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `work_history`
--
ALTER TABLE `work_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`leave_application_id`) REFERENCES `leave_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `education_history`
--
ALTER TABLE `education_history`
  ADD CONSTRAINT `education_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD CONSTRAINT `employment_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `leave_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_history`
--
ALTER TABLE `leave_history`
  ADD CONSTRAINT `leave_history_ibfk_1` FOREIGN KEY (`leave_application_id`) REFERENCES `leave_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_history`
--
ALTER TABLE `work_history`
  ADD CONSTRAINT `work_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
