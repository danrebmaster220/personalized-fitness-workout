-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 06, 2025 at 12:22 PM
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
-- Database: `fitness_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Full_Name` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Role` enum('user','admin') DEFAULT 'user',
  `Is_Verified` tinyint(1) DEFAULT 0,
  `Verification_Token` varchar(255) DEFAULT NULL,
  `Reset_Token` varchar(255) DEFAULT NULL,
  `Reset_Expires` datetime DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp(),
  `Updated_At` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Email`, `Full_Name`, `Password`, `Role`, `Is_Verified`, `Verification_Token`, `Reset_Token`, `Reset_Expires`, `Created_At`, `Updated_At`) VALUES
(1, 'dian@gmail.com', NULL, '$2y$10$2dWKp4Nuz/ezZqIWvlt8muvUQ5PoNkPrdIB.cKnvffHc8k0l73/KS', 'user', 0, '1a1caf27b9e7da7e124d6deb88bada32', NULL, NULL, '2025-11-06 09:38:43', '2025-11-06 09:38:43'),
(2, 'spam120500@gmail.com', NULL, '$2y$10$8zXKdhEqoZ.kIs6Q1H0Vye1/3sF7m6tQ80EwjdBnuCyeI6PkAif1m', 'user', 0, '09665f579a05ff3d98bd70656aa2833e', NULL, NULL, '2025-11-06 09:39:56', '2025-11-06 09:39:56'),
(3, 'ericc@gmail.com', NULL, '$2y$10$4dC.W9kItScIfTxkhdSWzusqVaP4p0pWgihiv7gVbM6Xo01BV0.Q6', 'user', 0, '8367955345f5688470917a864a4c00be', NULL, NULL, '2025-11-06 18:50:55', '2025-11-06 18:50:55'),
(4, 'abdu@gmail.com', NULL, '$2y$10$1/bLVi/mbmGIkaGNi5IT5.9QjwBM2bFqM0x48eP1QhDke8AGyPirW', 'user', 0, '2c68a3cfa71e7121cc942be925b60de3', NULL, NULL, '2025-11-06 18:51:38', '2025-11-06 18:51:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
