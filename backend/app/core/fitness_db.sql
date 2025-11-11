-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 09:42 PM
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
-- Table structure for table `api_logs`
--

CREATE TABLE `api_logs` (
  `Log_ID` int(11) NOT NULL,
  `User_ID` int(11) DEFAULT NULL,
  `API_Name` varchar(100) NOT NULL,
  `API_Type` varchar(50) DEFAULT NULL,
  `Status_Code` int(11) DEFAULT NULL,
  `Response_Status` varchar(50) DEFAULT NULL,
  `Request_Body` text DEFAULT NULL,
  `Response_Body` text DEFAULT NULL,
  `Error_Message` text DEFAULT NULL,
  `Request_Time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_workout`
--

CREATE TABLE `generated_workout` (
  `Generate_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Goal` varchar(100) DEFAULT NULL,
  `Target_Muscle` varchar(100) DEFAULT NULL,
  `Workout_Place` varchar(50) DEFAULT NULL,
  `Workout_Days` int(11) DEFAULT NULL,
  `Duration_Min` int(11) DEFAULT NULL,
  `Equipment` varchar(255) DEFAULT NULL,
  `Health_Condition` varchar(255) DEFAULT NULL,
  `Allergies` varchar(255) DEFAULT NULL,
  `BMI` decimal(5,2) DEFAULT NULL,
  `BMR` decimal(10,2) DEFAULT NULL,
  `TDEE` decimal(10,2) DEFAULT NULL,
  `Workout_Result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Workout_Result`)),
  `Meal_Result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Meal_Result`)),
  `Body_Condition_Result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Body_Condition_Result`)),
  `Raw_AI_Response` longtext DEFAULT NULL,
  `Created_At` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `Updated_At` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Activity_Level` varchar(20) NOT NULL DEFAULT 'moderate'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `FirstName`, `LastName`, `Email`, `Password`, `Role`, `Age`, `Height`, `Weight`, `Gender`, `Fitness_Level`, `Profile_Image`, `Is_Verified`, `Verification_Token`, `Reset_Token`, `Reset_Expires`, `Created_At`, `Updated_At`, `Activity_Level`) VALUES
(1, 'alshaik', 'hassan', 'alshaik7813@gmail.com', '$2y$10$3o5lSZO59xtyhWiH3Kfj.uNQOMgpHmhuahFj1Xif/0WcNzWkXIbVK', 'user', 22, 168.00, 58.00, 'male', 'beginner', NULL, 0, '62d26d566c4db6bb24d4d3c45c701800', NULL, NULL, '2025-11-10 16:22:04', '2025-11-10 16:22:04', 'moderate'),
(2, 'alshaik', 'hassan', 'alshaik78@gmail.com', '$2y$10$k2bJUalnu2FwgddDGqbKk.lER6dbmzeTgdsrjSUMl8lu7cOwSX.bu', 'user', 22, 168.00, 69.00, 'male', 'beginner', NULL, 0, 'e9a4677174c8ebdc7ada3c2ba0205ed0', NULL, NULL, '2025-11-12 04:22:17', '2025-11-12 04:22:17', 'moderate');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`Log_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `generated_workout`
--
ALTER TABLE `generated_workout`
  ADD PRIMARY KEY (`Generate_ID`),
  ADD KEY `User_ID` (`User_ID`);

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
-- AUTO_INCREMENT for table `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `Log_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_workout`
--
ALTER TABLE `generated_workout`
  MODIFY `Generate_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE SET NULL;

--
-- Constraints for table `generated_workout`
--
ALTER TABLE `generated_workout`
  ADD CONSTRAINT `generated_workout_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
