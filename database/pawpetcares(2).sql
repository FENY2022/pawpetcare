-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2025 at 11:18 AM
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
(1, '2025-11-01', '2025-11-28', 'LAGANGA', 'JAKE', 'MUBAS', 'Male', '2025-11-13', '09478984921', 'venzonanthonie@gmail.com', 'P-1 ', 'Doña Rosario', 'Cantilan', 'Surigao del Sur', '2025-11-01 08:46:36'),
(2, '2025-11-01', '2025-11-28', 'LAGANGA', 'JAKE', 'MUBAS', 'Male', '2025-11-13', '09478984921', 'venzonanthonie@gmail.com', 'P-1 ', 'Doña Rosario', 'Cantilan', 'Surigao del Sur', '2025-11-01 09:00:26'),
(3, '2025-11-01', '2025-11-26', 'LAGANGA', 'JAKE', 'MUBAS', 'Male', '2007-11-01', '09478984921', 'venzonanthonie@gmail.com', 'P-1', 'Doña Rosario', 'Cantilan', 'Surigao del Sur', '2025-11-01 09:09:26');

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
(3, 3, 'Local', '', 'Household', 'Caged', 'Dog', 'Shawi', 'Hatskey', '2025-11-01', 'white', 'Male', 1, 0, 2, 1.00, '2', 1, 0, '', 'Seldom', 'uploads/pet_images/pet_6905ce464b1a44.37150434.jpg', '2025-11-01 09:09:26');

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
(35, 'JAKE', 'MUBAS', 'LAGANGA', '09478984921', 'venzonanthonie@gmail.com', '$2y$10$gdMA3PwndRGmtXsDvuF2Mec1beokiZGdkVjO7jpgoJd3YRWOV4kIS', 'Purok 1', 'Dona Rosario', 'Tubay', 'Agusan del Norte', '8605', 'Philippines', NULL, NULL, NULL, 1, '2025-10-31 13:27:06', 0, 'user_35_6904c7fbbdcd9.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`pet_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `pet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
