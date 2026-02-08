-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 07:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mahapurush_live`
--

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `contributor_name` varchar(100) DEFAULT NULL,
  `contribution_type_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contributions`
--

INSERT INTO `contributions` (`id`, `receipt_id`, `added_by`, `contributor_name`, `contribution_type_id`, `title`, `quantity`, `unit`, `description`, `status`, `created_at`) VALUES
(7, 31, 8, '0', 2, '0', 0.20, 'bags', 'For Pooja.', 'pending', '2026-02-06 04:12:45'),
(8, 34, 8, '0', 7, '0', 3.00, 'kg', 'gfh', 'pending', '2026-02-06 04:39:31');

-- --------------------------------------------------------

--
-- Table structure for table `contribution_type`
--

CREATE TABLE `contribution_type` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contribution_type`
--

INSERT INTO `contribution_type` (`id`, `type`, `description`, `created_at`) VALUES
(1, 'Food', NULL, '2026-02-01 13:34:15'),
(2, 'Pooja Material', NULL, '2026-02-01 13:34:15'),
(3, 'Maintenance & Repair', NULL, '2026-02-01 13:34:40'),
(4, 'Furniture & Assets', NULL, '2026-02-01 13:34:40'),
(5, 'Daily Essentials', NULL, '2026-02-01 13:34:58'),
(6, 'Festival Supplies', NULL, '2026-02-01 13:34:58'),
(7, 'Books & Scriptures', NULL, '2026-02-01 13:35:11'),
(8, 'Volunteer Service', NULL, '2026-02-01 13:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `donation_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donation_type`
--

CREATE TABLE `donation_type` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `conduct_on` date DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `duration`, `conduct_on`, `max_participants`, `created_at`) VALUES
(1, 'Vardhapan', '2', '2026-02-08', 0, '2026-02-01 15:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

CREATE TABLE `event_participants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `gallery_category_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_category`
--

CREATE TABLE `gallery_category` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_category`
--

INSERT INTO `gallery_category` (`id`, `type`, `created_at`) VALUES
(1, 'Temple', '2026-01-31 10:37:16'),
(2, 'Events', '2026-01-31 10:37:16'),
(3, 'Festivals', '2026-01-31 10:37:30'),
(4, 'Rituals', '2026-01-31 10:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `note` text DEFAULT NULL,
  `payment_method` enum('upi','cash','card','netbanking') NOT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `receipt_id`, `user_id`, `donor_name`, `amount`, `note`, `payment_method`, `transaction_ref`, `status`, `created_at`) VALUES
(14, 23, 6, 'Yojana Gawade', 121.00, '', 'cash', NULL, 'success', '2026-02-05 15:27:07'),
(15, 24, 6, 'Yojana Gawade', 1.00, '', 'cash', NULL, 'success', '2026-02-05 15:30:03'),
(16, 25, 6, 'Yojana Gawade', 1.00, '', 'cash', NULL, 'success', '2026-02-05 15:32:31'),
(17, 26, 7, 'tanu salunke', 5000.00, '...', 'cash', NULL, 'success', '2026-02-06 04:05:24'),
(18, 27, 8, 'Raj Sawant', 1000000.00, 'For Development purpose.', 'cash', NULL, 'success', '2026-02-06 04:06:01'),
(19, 28, 7, 'tanu salunke', 99999999.99, '', 'cash', NULL, 'success', '2026-02-06 04:06:30'),
(20, 29, 8, 'Raj Sawant', 99999999.99, 'bdjh', 'cash', NULL, 'success', '2026-02-06 04:06:56'),
(21, 30, 7, 'tanu salunke', 99999999.99, '', 'cash', NULL, 'success', '2026-02-06 04:08:06'),
(22, 32, 8, 'Raj Sawant', 99999999.99, 'oughui', 'cash', NULL, 'success', '2026-02-06 04:17:43'),
(23, 33, 8, 'Raj Sawant', 99999999.99, '', 'cash', NULL, 'success', '2026-02-06 04:18:00'),
(24, 35, 9, 'atharva dhuri', 10000.00, '', 'cash', NULL, 'success', '2026-02-06 04:50:02');

-- --------------------------------------------------------

--
-- Table structure for table `pooja`
--

CREATE TABLE `pooja` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pooja_type_id` int(11) NOT NULL,
  `pooja_date` date NOT NULL,
  `time_slot` enum('morning','afternoon','evening') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `status` enum('pending','paid','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pooja`
--

INSERT INTO `pooja` (`id`, `user_id`, `pooja_type_id`, `pooja_date`, `time_slot`, `description`, `fee`, `payment_id`, `status`, `created_at`) VALUES
(8, 6, 2, '2026-02-18', 'evening', '', 200.00, NULL, 'pending', '2026-02-06 05:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `pooja_type`
--

CREATE TABLE `pooja_type` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pooja_type`
--

INSERT INTO `pooja_type` (`id`, `type`, `fee`, `created_at`) VALUES
(1, 'Archana', 100.00, '2026-01-31 16:16:59'),
(2, 'Abhishek', 200.00, '2026-01-31 16:16:59'),
(3, 'Ganesh', 250.00, '2026-01-31 16:17:29'),
(4, 'Satyanarayan', 501.00, '2026-01-31 16:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `receipt`
--

CREATE TABLE `receipt` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `purpose` enum('donation','pooja','contribution') NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `source_table` varchar(50) NOT NULL,
  `issued_on` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `receipt`
--

INSERT INTO `receipt` (`id`, `user_id`, `receipt_no`, `purpose`, `amount`, `source_table`, `issued_on`, `created_at`) VALUES
(23, 6, 'DON/2026/0205162707/284', 'donation', 121.00, 'donations', '2026-02-05 20:57:07', '2026-02-05 15:27:07'),
(24, 6, 'DON/2026/0205163003/215', 'donation', 1.00, 'donations', '2026-02-05 21:00:03', '2026-02-05 15:30:03'),
(25, 6, 'DON/2026/0205163231/753', 'donation', 1.00, 'donations', '2026-02-05 21:02:31', '2026-02-05 15:32:31'),
(26, 7, 'DON/2026/0206050524/592', 'donation', 5000.00, 'donations', '2026-02-06 09:35:24', '2026-02-06 04:05:24'),
(27, 8, 'DON/2026/0206050601/655', 'donation', 1000000.00, 'donations', '2026-02-06 09:36:01', '2026-02-06 04:06:01'),
(28, 7, 'DON/2026/0206050630/138', 'donation', 99999999.99, 'donations', '2026-02-06 09:36:30', '2026-02-06 04:06:30'),
(29, 8, 'DON/2026/0206050657/828', 'donation', 99999999.99, 'donations', '2026-02-06 09:36:57', '2026-02-06 04:06:57'),
(30, 7, 'DON/2026/0206050806/499', 'donation', 99999999.99, 'donations', '2026-02-06 09:38:06', '2026-02-06 04:08:06'),
(31, 8, 'CON/2026/0206051245/287', 'contribution', 0.00, 'contributions', '2026-02-06 09:42:45', '2026-02-06 04:12:45'),
(32, 8, 'DON/2026/0206051743/697', 'donation', 99999999.99, 'donations', '2026-02-06 09:47:43', '2026-02-06 04:17:43'),
(33, 8, 'DON/2026/0206051800/794', 'donation', 99999999.99, 'donations', '2026-02-06 09:48:00', '2026-02-06 04:18:00'),
(34, 8, 'CON/2026/0206053931/959', 'contribution', 0.00, 'contributions', '2026-02-06 10:09:31', '2026-02-06 04:39:31'),
(35, 9, 'DON/2026/0206055002/840', 'donation', 10000.00, 'donations', '2026-02-06 10:20:02', '2026-02-06 04:50:02');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'customer', 'Customer', '2026-01-28 10:27:01'),
(2, 'member', 'Member', '2026-01-27 16:06:09'),
(3, 'secretary', 'Secretary', '2026-01-27 16:05:53'),
(4, 'treasurer', 'Treasurer', '2026-01-27 16:05:53'),
(98, 'vice chairman', 'Vice Chairman', '2026-01-27 16:04:11'),
(99, 'chairman', 'Chairman', '2026-01-27 16:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `temple_info`
--

CREATE TABLE `temple_info` (
  `id` int(11) NOT NULL,
  `temple_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `time` varchar(100) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `temple_info`
--

INSERT INTO `temple_info` (`id`, `temple_name`, `description`, `photo`, `contact`, `time`, `address`, `location`) VALUES
(1, 'Mahapurush Temple', 'A sacred temple dedicated to Lord Shiva, offering peace and spiritual harmony.', '', '+91 9823369562', '8:00 AM - 8:00 PM', 'A/P: Budruk, Oros', '4P76+HF3, Sindhu Durg, Oros, Maharashtra 416812');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `phone`, `photo`, `email`, `password`, `created_at`) VALUES
(5, 'Abhishek', 'Bhat', '9823369562', 'user_697a0bf54cbcb4.54961703.jpeg', 'abhishekbhat014@gmail.com', '$2y$10$KfLlAZCa/BSCHnji1zULoO5A0ovagpR5mQX8OoRc3/t4uTakdAt/S', '2026-01-28 13:15:33'),
(6, 'Yojana', 'Gawade', '8999057576', '', 'gawadeyojana010@gmail.com', '$2y$10$W.keHS.KpSPHCSz.1iToY.u5pvApEjUaGTl4HI4uqLBLJaT6owbDu', '2026-01-29 06:50:15'),
(7, 'tanu', 'salunke', '9785641425', 'user_6985684e0f6df3.19499536.jpg', 'tanus@gmail.com', '$2y$10$vw3Qfp0EcTRUUwQh0oajB.S5nhHhwn83AySh0B0R1l5u0d8kVDSR6', '2026-02-06 04:04:30'),
(8, 'Raj', 'Sawant', '9422911029', 'user_698568516419c0.43455690.jpeg', 'sawanty2764@gmail.com', '$2y$10$qgR7vN9rkaa5gJpjpY/SZO3RHBEdx08RpeCPfoHEQAZJTCG25q8uy', '2026-02-06 04:04:33'),
(9, 'atharva', 'dhuri', '3366554411', 'user_698572e5b48d93.47410126.jpeg', 'ad@gmail.com', '$2y$10$L.EdBP9SJIIc0zQYY39M9OgayVwWNQQOjMzO0vl7Klipga48X7CMu', '2026-02-06 04:49:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`, `created_at`) VALUES
(5, 2, '2026-01-28 13:15:33'),
(6, 99, '2026-02-05 16:30:13'),
(7, 1, '2026-02-06 04:04:30'),
(8, 1, '2026-02-06 04:04:33'),
(9, 1, '2026-02-06 04:49:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contribution_type_id` (`contribution_type_id`),
  ADD KEY `fk_contributions_receipt` (`receipt_id`),
  ADD KEY `fk_contributions_added_by` (`added_by`);

--
-- Indexes for table `contribution_type`
--
ALTER TABLE `contribution_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `donation_type_id` (`donation_type_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `donation_type`
--
ALTER TABLE `donation_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gallery_category_id` (`gallery_category_id`);

--
-- Indexes for table `gallery_category`
--
ALTER TABLE `gallery_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_payments_receipt` (`receipt_id`);

--
-- Indexes for table `pooja`
--
ALTER TABLE `pooja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `pooja_type_id` (`pooja_type_id`);

--
-- Indexes for table `pooja_type`
--
ALTER TABLE `pooja_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipt`
--
ALTER TABLE `receipt`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `idx_receipt_user` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temple_info`
--
ALTER TABLE `temple_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contributions`
--
ALTER TABLE `contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contribution_type`
--
ALTER TABLE `contribution_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `donation_type`
--
ALTER TABLE `donation_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `event_participants`
--
ALTER TABLE `event_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gallery_category`
--
ALTER TABLE `gallery_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `pooja`
--
ALTER TABLE `pooja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pooja_type`
--
ALTER TABLE `pooja_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `receipt`
--
ALTER TABLE `receipt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `temple_info`
--
ALTER TABLE `temple_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`contribution_type_id`) REFERENCES `contribution_type` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_contributions_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_contributions_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `receipt` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`donation_type_id`) REFERENCES `donation_type` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `feedbacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`gallery_category_id`) REFERENCES `gallery_category` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `receipt` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pooja`
--
ALTER TABLE `pooja`
  ADD CONSTRAINT `pooja_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pooja_ibfk_2` FOREIGN KEY (`pooja_type_id`) REFERENCES `pooja_type` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
