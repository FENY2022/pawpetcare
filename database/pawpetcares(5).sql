-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 04:10 PM
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
-- Database: `pawpetcares`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `reg_date` date NOT NULL,
  `valid_until` date NOT NULL,
  `client_lname` varchar(100) NOT NULL,
  `client_fname` varchar(100) NOT NULL,
  `client_mname` varchar(100) DEFAULT NULL,
  `client_sex` enum('Male','Female') NOT NULL,
  `client_bday` date NOT NULL,
  `client_contact` varchar(20) DEFAULT NULL,
  `client_email` varchar(150) DEFAULT NULL,
  `addr_purok` varchar(100) DEFAULT NULL,
  `addr_brgy` varchar(100) DEFAULT NULL,
  `addr_mun` varchar(100) DEFAULT NULL,
  `addr_prov` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `reg_date`, `valid_until`, `client_lname`, `client_fname`, `client_mname`, `client_sex`, `client_bday`, `client_contact`, `client_email`, `addr_purok`, `addr_brgy`, `addr_mun`, `addr_prov`, `created_at`) VALUES
(4, '2025-11-08', '2026-01-30', 'LAGANGA', 'JAKE', 'MUBAS', 'Male', '2007-11-01', '09478984921', 'venzonanthonie@gmail.com', 'P-1', 'Doña Rosario', 'Cantilan', 'Surigao del Sur', '2025-11-08 13:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 35, 'New Vaccine Request', 'New vaccination request for Shawi (Status: Pending).', 'admin_vaccinations.php?pet_id=4&vaccine_id=9', 1, '2025-11-08 13:29:01'),
(2, 35, 'New Vaccine Request', 'New vaccination request for Shawi (Status: Pending).', 'dashboard.php?action=admin_vaccinations&pet_id=4&vaccine_id=10', 1, '2025-11-08 13:32:48');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `pet_origin` varchar(100) DEFAULT NULL,
  `pet_origin_other` varchar(100) DEFAULT NULL,
  `pet_ownership` enum('Household','Community') DEFAULT NULL,
  `pet_habitat` varchar(100) DEFAULT NULL,
  `pet_species` enum('Dog','Cat') DEFAULT NULL,
  `pet_name` varchar(100) DEFAULT NULL,
  `pet_breed` varchar(100) DEFAULT NULL,
  `pet_bday` date DEFAULT NULL,
  `pet_color` varchar(50) DEFAULT NULL,
  `pet_sex` enum('Male','Female') DEFAULT NULL,
  `pet_is_pregnant` tinyint(1) DEFAULT 0,
  `pet_is_lactating` tinyint(1) DEFAULT 0,
  `pet_puppies` int(11) DEFAULT NULL,
  `pet_weight` decimal(5,2) DEFAULT NULL,
  `pet_tag_no` varchar(50) DEFAULT NULL,
  `tag_type_collar` tinyint(1) DEFAULT 0,
  `tag_type_other` tinyint(1) DEFAULT 0,
  `tag_type_other_specify` varchar(100) DEFAULT NULL,
  `pet_contact` enum('Frequent','Seldom','Never') DEFAULT NULL,
  `pet_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`pet_id`, `client_id`, `pet_origin`, `pet_origin_other`, `pet_ownership`, `pet_habitat`, `pet_species`, `pet_name`, `pet_breed`, `pet_bday`, `pet_color`, `pet_sex`, `pet_is_pregnant`, `pet_is_lactating`, `pet_puppies`, `pet_weight`, `pet_tag_no`, `tag_type_collar`, `tag_type_other`, `tag_type_other_specify`, `pet_contact`, `pet_image_path`, `created_at`) VALUES
(4, 35, 'Local', '', 'Household', 'Caged', 'Dog', 'Shawi', 'Hatskey', '2025-11-08', 'White', 'Male', 1, 0, NULL, 20.00, '9478', 1, 0, '', 'Frequent', 'uploads/pet_images/pet_690f4016c6c194.49600007.jpg', '2025-11-08 13:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_rules` int(5) NOT NULL,
  `profile_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `email`, `password`, `street`, `barangay`, `city`, `province`, `postal_code`, `country`, `verification_code`, `reset_token`, `reset_token_expiry`, `is_verified`, `created_at`, `user_rules`, `profile_image`) VALUES
(35, 'JAKE', 'MUBAS', 'LAGANGA', '09478984921', 'venzonanthonie@gmail.com', '$2y$10$o0S1Z3BEaDXGXN11d/urOu73rFg18BPB3LF3C0.VerwZZRT0uPP2u', 'Purok 1', 'Dona Rosario', 'Tubay', 'Agusan del Norte', '8605', 'Philippines', NULL, NULL, NULL, 1, '2025-10-31 13:27:06', 1, 'user_35_6904c7fbbdcd9.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `status` enum('Pending','Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `vaccine_name` varchar(100) NOT NULL,
  `date_given` date DEFAULT NULL,
  `next_due` date DEFAULT NULL,
  `batch_no` varchar(100) DEFAULT NULL,
  `administered_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccinations`
--

INSERT INTO `vaccinations` (`id`, `pet_id`, `status`, `vaccine_name`, `date_given`, `next_due`, `batch_no`, `administered_by`, `notes`) VALUES
(6, 4, 'Scheduled', 'Anti-Rabies', NULL, '2025-11-08', NULL, 'JAKE ', 'sdgfdgdf\n\nAdmin Note: asdsadsfsdfsdf'),
(7, 4, 'Pending', 'Anti-Rabies', NULL, NULL, NULL, NULL, 'sdgfdgdf'),
(8, 4, 'Pending', 'Anti-Rabies', NULL, NULL, NULL, NULL, 'dsfsdfdsf'),
(9, 4, 'Pending', 'Anti-Rabies', NULL, NULL, NULL, NULL, 'dfsdfsdfd'),
(10, 4, 'Scheduled', 'Anti-Rabies', NULL, '2025-11-28', NULL, 'JAKE ', 'sdfsdfdsf\n\nAdmin Note: xvgfdg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `pets_ibfk_1` (`client_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pet_id` (`pet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `fk_vaccinations_pets` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
