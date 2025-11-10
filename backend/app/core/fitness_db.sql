-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 10:58 AM
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
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('user','admin') DEFAULT 'user',
  `Age` int(11) DEFAULT NULL,
  `Height` decimal(5,2) DEFAULT NULL,
  `Weight` decimal(5,2) DEFAULT NULL,
  `Gender` enum('male','female','other') DEFAULT NULL,
  `Fitness_Level` enum('beginner','intermediate','advanced') DEFAULT NULL,
  `Profile_Image` varchar(255) DEFAULT NULL,
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

INSERT INTO `user` (`User_ID`, `FirstName`, `LastName`, `Email`, `Password`, `Role`, `Age`, `Height`, `Weight`, `Gender`, `Fitness_Level`, `Profile_Image`, `Is_Verified`, `Verification_Token`, `Reset_Token`, `Reset_Expires`, `Created_At`, `Updated_At`) VALUES
(1, 'alshaik', 'hassan', 'alshaik7813@gmail.com', '$2y$10$3o5lSZO59xtyhWiH3Kfj.uNQOMgpHmhuahFj1Xif/0WcNzWkXIbVK', 'user', 22, 168.00, 58.00, 'male', 'beginner', NULL, 0, '62d26d566c4db6bb24d4d3c45c701800', NULL, NULL, '2025-11-10 16:22:04', '2025-11-10 16:22:04');

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
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
