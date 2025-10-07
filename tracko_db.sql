-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 08:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

CREATE DATABASE IF NOT EXISTS `tracko_db`;
USE `tracko_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tracko_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `evaluation_submission_id` int(11) NOT NULL,
  `evaluation_supervisor_id` int(11) NOT NULL,
  `evaluation_score` decimal(5,2) DEFAULT NULL,
  `evaluation_eval_date` date DEFAULT NULL,
  `evaluation_feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`evaluation_id`, `evaluation_submission_id`, `evaluation_supervisor_id`, `evaluation_score`, `evaluation_eval_date`, `evaluation_feedback`) VALUES
(1, 1, 1, 85.50, '2025-09-16', 'Good initial proposal'),
(2, 2, 1, 78.00, '2025-10-18', 'Needs improvement on slides'),
(5, 3, 1, 92.00, '2025-11-12', 'Strong mid2 presentation'),
(6, 4, 1, 25.00, '2025-12-16', 'Not structured well.'),
(7, 5, 2, 80.00, '2025-10-16', 'Good initial proposal, needs minor revisions'),
(8, 6, 2, 75.00, '2025-11-16', 'Mid presentation slightly late, improve clarity'),
(9, 7, 1, 88.00, '2025-11-12', 'Well-prepared submission, solid work'),
(10, 8, 1, 25.00, '2025-12-16', 'Final presentation excellent, very detailed'),
(11, 11, 2, 98.00, '2025-09-30', 'Superb presentation'),
(12, 15, 2, 88.00, '2025-09-22', 'Well done'),
(13, 17, 2, 58.00, '2025-09-22', 'Good work'),
(14, 18, 2, 25.00, '2025-09-22', 'not good'),
(15, 21, 1, 20.00, '2025-10-07', 'asdghk'),
(16, 22, 1, 50.00, '2025-10-07', '');

-- --------------------------------------------------------

--
-- Table structure for table `milestones`
--

CREATE TABLE `milestones` (
  `milestone_id` int(11) NOT NULL,
  `milestone_title` varchar(200) NOT NULL,
  `milestone_description` text DEFAULT NULL,
  `milestone_due_date` date NOT NULL,
  `milestone_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `milestones`
--

INSERT INTO `milestones` (`milestone_id`, `milestone_title`, `milestone_description`, `milestone_due_date`, `milestone_created_at`) VALUES
(1, 'Initial Proposal Submission', 'Submit project proposal document', '2025-09-15', '2025-09-21 22:53:01'),
(2, 'Mid 1st Presentation', 'First mid presentation', '2025-09-20', '2025-09-21 22:53:01'),
(3, 'Mid 2nd Presentation', 'Second mid presentation', '2025-11-15', '2025-09-21 22:53:01'),
(4, 'Final Presentation', 'Final project presentation', '2025-12-15', '2025-09-21 22:53:01'),
(5, 'Final Viva', 'Final Viva - induvidual with complete project demonstration', '2025-12-31', '2025-10-07 19:38:42');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_student_id` int(11) NOT NULL,
  `project_supervisor_id` int(11) NOT NULL,
  `project_title` varchar(200) NOT NULL,
  `project_description` text DEFAULT NULL,
  `project_status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `project_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `project_student_id`, `project_supervisor_id`, `project_title`, `project_description`, `project_status`, `project_created_at`) VALUES
(1, 3, 1, 'AI Chatbot System', 'Final year AI chatbot project', 'in_progress', '2025-09-21 22:53:01'),
(2, 4, 2, 'University Management System', 'Full-stack university project management', 'pending', '2025-09-21 22:53:01'),
(3, 5, 1, 'E-commerce Website', 'Online shopping platform', 'completed', '2025-09-21 22:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `submission_milestone_id` int(11) NOT NULL,
  `submission_project_id` int(11) NOT NULL,
  `submission_student_id` int(11) NOT NULL,
  `submission_uploaded_by` int(11) NOT NULL,
  `submission_file_path` varchar(255) DEFAULT NULL,
  `submission_notes` text DEFAULT NULL,
  `submission_upload_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `submission_milestone_id`, `submission_project_id`, `submission_student_id`, `submission_uploaded_by`, `submission_file_path`, `submission_notes`, `submission_upload_date`) VALUES
(1, 1, 1, 3, 3, '/submissions/ai_proposal.pdf', 'On time', '2025-09-14'),
(2, 2, 1, 3, 3, '/submissions/ai_mid1.ppt', 'Overdue', '2025-10-17'),
(3, 3, 1, 3, 3, '/submissions/ai_mid2.ppt', 'Pending', '0000-00-00'),
(4, 1, 2, 4, 4, '/submissions/ums_proposal.pdf', 'On time', '2025-09-15'),
(5, 1, 3, 5, 5, '/submissions/ecom_proposal.pdf', 'On time', '2025-09-10'),
(6, 2, 3, 5, 5, '/submissions/ecom_mid1.ppt', 'On time', '2025-10-12'),
(7, 3, 3, 5, 5, '/submissions/ecom_mid2.ppt', 'On time', '2025-11-10'),
(8, 4, 3, 5, 5, '/submissions/ecom_final.ppt', 'On time', '2025-12-12'),
(11, 4, 3, 5, 5, '../uploads/submissions/FinalPresentation_11_ClaraJensen.docx', '', '2025-09-22'),
(15, 1, 2, 4, 4, '../uploads/submissions/InitialProposalSubmission_15_SofiaDimitrova.docx', '', '2025-09-22'),
(17, 2, 2, 4, 4, '../uploads/submissions/Mid1stPresentation_17_SofiaDimitrova.jpg', '', '2025-09-22'),
(18, 3, 2, 4, 4, '../uploads/submissions/Mid2ndPresentation_18_SofiaDimitrova.jpg', '', '2025-09-22'),
(19, 3, 2, 4, 4, '../uploads/submissions/Mid2ndPresentation_19_SofiaDimitrova.jpg', '', '2025-09-22'),
(21, 4, 1, 3, 3, '../uploads/submissions/FinalPresentation_21_LukasNovak.png', '', '2025-10-07'),
(22, 4, 1, 3, 3, '../uploads/submissions/FinalPresentation_22_LukasNovak.png', '', '2025-10-07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_role` enum('admin','student','supervisor') NOT NULL,
  `user_created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_password`, `user_role`, `user_created_at`) VALUES
(1, 'Dr. Markus Schneider', 'markus@example.com', '$2y$10$dIEOYMUjZpybU0rOWAGDuO6S9shPN1nCy9FetKYOjm/E2MQRvDpVS', 'supervisor', '2025-09-21 22:53:01'),
(2, 'Prof. Elena Rossi', 'elena@example.com', '$2y$10$dIEOYMUjZpybU0rOWAGDuO6S9shPN1nCy9FetKYOjm/E2MQRvDpVS', 'supervisor', '2025-09-21 22:53:01'),
(3, 'Lukas Novak', 'lukas@example.com', '$2y$10$dIEOYMUjZpybU0rOWAGDuO6S9shPN1nCy9FetKYOjm/E2MQRvDpVS', 'student', '2025-09-21 22:53:01'),
(4, 'Sofia Dimitrova', 'sofia@example.com', '$2y$10$7V8jD5ud.wwRNpCvdJkdkuEAT2SDKq6hqegAbvbJy3/c.qkJ/8fsi', 'student', '2025-09-21 22:53:01'),
(5, 'Clara Jensen', 'clara@example.com', '$2y$10$dIEOYMUjZpybU0rOWAGDuO6S9shPN1nCy9FetKYOjm/E2MQRvDpVS', 'student', '2025-09-21 22:53:01'),
(6, 'System Admin', 'admin@tracko.com', '$2y$10$AfsjgOBwF0QHKNFsNKtlzulnpw.4g957C/pVjAgJF8hVmloEoVo.O', 'admin', '2025-09-22 15:30:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `evaluation_submission_id` (`evaluation_submission_id`),
  ADD KEY `evaluation_supervisor_id` (`evaluation_supervisor_id`);

--
-- Indexes for table `milestones`
--
ALTER TABLE `milestones`
  ADD PRIMARY KEY (`milestone_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `project_student_id` (`project_student_id`),
  ADD KEY `project_supervisor_id` (`project_supervisor_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `submission_milestone_id` (`submission_milestone_id`),
  ADD KEY `submission_project_id` (`submission_project_id`),
  ADD KEY `submission_student_id` (`submission_student_id`),
  ADD KEY `submission_uploaded_by` (`submission_uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `milestones`
--
ALTER TABLE `milestones`
  MODIFY `milestone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`evaluation_submission_id`) REFERENCES `submissions` (`submission_id`),
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`evaluation_supervisor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`project_student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`project_supervisor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`submission_milestone_id`) REFERENCES `milestones` (`milestone_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`submission_project_id`) REFERENCES `projects` (`project_id`),
  ADD CONSTRAINT `submissions_ibfk_3` FOREIGN KEY (`submission_student_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `submissions_ibfk_4` FOREIGN KEY (`submission_uploaded_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
