-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2024 at 04:49 PM
-- Server version: 10.1.36-MariaDB
-- PHP Version: 7.0.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fundlift_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `campaigns_tbl`
--

CREATE TABLE `campaigns_tbl` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `goal_amount` decimal(10,2) DEFAULT NULL,
  `amount_raised` decimal(10,2) DEFAULT '0.00',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `status` enum('completed','active','archived') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `campaigns_tbl`
--

INSERT INTO `campaigns_tbl` (`id`, `user_id`, `title`, `description`, `goal_amount`, `amount_raised`, `start_date`, `end_date`, `created_at`, `updated_at`, `status`) VALUES
(7, 11, 'for public livrary', 'spread education', '30000.00', '400.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(8, 11, 'for laptop', 'need money', '10000.00', '1170.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(9, 16, 'college', 'financial support', '50000.00', '0.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(10, 16, 'building a hospital', 'help build a hospital for the public', '100000.00', '0.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(11, 29, 'modeng fund', 'need money', '1000.00', '1600.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(12, 29, 'panggala ko', 'need money', '1000.00', '1600.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(13, 29, 'Save met', 'need money', '1000.00', '1800.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active'),
(14, 29, 'for glasses', 'cant see anymore', '30000.00', '1600.00', '2024-11-01', '2025-01-01', '2024-12-07 10:29:42', '2024-12-07 10:29:42', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `pledges_tbl`
--

CREATE TABLE `pledges_tbl` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `message` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `refund_reason` text,
  `refund_status` enum('not_requested','pending','refunded','denied') DEFAULT 'not_requested',
  `payment_status` enum('pending','paid','unsuccessful') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pledges_tbl`
--

INSERT INTO `pledges_tbl` (`id`, `campaign_id`, `user_id`, `amount`, `message`, `created_at`, `refund_reason`, `refund_status`, `payment_status`) VALUES
(14, 7, 11, '200.00', 'education!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(30, 7, 14, '200.00', 'meow!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(33, 8, 29, '200.00', 'get that laptop!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(34, 8, 29, '400.00', 'hope it helps!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(35, 8, 29, '20.00', 'mwa!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(36, 8, 29, '30.00', 'yaa!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(42, 8, 29, '80.00', 'eyy!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(45, 8, 29, '80.00', 'eyy!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(46, 8, 29, '80.00', 'eyy!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(47, 8, 29, '80.00', 'eyy!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(48, 8, 29, '100.00', 'hehe!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(49, 8, 29, '100.00', 'heheyah', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending'),
(50, 8, 29, '200.00', 'extra', '2024-12-07 10:30:08', 'cant see anymore', 'refunded', 'pending'),
(52, 13, 29, '200.00', 'education!', '2024-12-07 10:30:08', NULL, 'not_requested', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `user_tbl`
--

CREATE TABLE `user_tbl` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_tbl`
--

INSERT INTO `user_tbl` (`id`, `username`, `password`, `token`, `role`) VALUES
(25, 'xel', '$2y$10$MTQ0NTQzODdmN2IyODZmNOOBKbupCAfPR4t5iyrTsmo2GaKmXHz6u', 'ZWM4M2FhOGIzOGMzMjUzM2YxMDg4M2UxYzRkMTFkYmY2MmM3ZGJmMGVhYjNjZTcyN2I2NDhmZmY1MzAwZDNkZA==', 'campaign_owner'),
(26, 'sunoo', '$2y$10$Y2E0NjA5MGMxMDY0ZjRkZ.hg4CWfPaz1FRYxeLV0BeQVSzJ4SnduO', '', 'campaign_owner'),
(27, 'harua', '$2y$10$MDRjMTRhZDI1NmM1NGU0Nun8txhG/7CTdfhlVQ5ehiAkqXqRyVFrK', '', 'user'),
(28, 'ricky', '$2y$10$NDEwNDA3NjEwYTEwMjkwYu3jLbwtsTtEYvn9oX63HHfhAI1/SELdG', '', 'campaign_owner'),
(29, 'zhanghao', '$2y$10$OWU2YWE2ZmIzZTVhZjk0NeS4vXksNBv8rxuEvnGkA9guIrHtDU40K', 'MWExZWIwYTA1ZGExOWZhZjU3ZGY4YzY4MzUwOTdiOGRjMWFjZDg3NjAwNzgzMzhkNGIyNjU3ODgwOTlkYWZkZg==', 'admin'),
(30, 'jungwoo', '$2y$10$ZWI0YWFlZTQ4YjRlMGUxZOr.a1revp.Ox002M5N/6xqtcAT.ELPl2', 'ZWVjZGEyMDg3YWI4OTE4NTA2M2M5N2RlMTAwNDg3MDE4ODkzOGNmZjJmZjRlNWI1NDE2MTk2NzEwOGU0ZjRmZQ==', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns_tbl`
--
ALTER TABLE `campaigns_tbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pledges_tbl`
--
ALTER TABLE `pledges_tbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_tbl`
--
ALTER TABLE `user_tbl`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `campaigns_tbl`
--
ALTER TABLE `campaigns_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `pledges_tbl`
--
ALTER TABLE `pledges_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `user_tbl`
--
ALTER TABLE `user_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaigns_tbl`
--
ALTER TABLE `campaigns_tbl`
  ADD CONSTRAINT `campaigns_tbl_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_tbl` (`id`);

--
-- Constraints for table `pledges_tbl`
--
ALTER TABLE `pledges_tbl`
  ADD CONSTRAINT `pledges_tbl_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns_tbl` (`id`),
  ADD CONSTRAINT `pledges_tbl_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user_tbl` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
