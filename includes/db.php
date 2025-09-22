<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tracko_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$failMark=35;

// // Create database
// $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
// if ($conn->query($sql) === TRUE) {
//     echo "Database created successfully<br>";
// } else {
//     echo "Error creating database: " . $conn->error . "<br>";
// }

// // Select database
// $conn->select_db($dbname);

// // Drop tables if exist to avoid conflicts
// $tables = ['evaluations', 'submissions', 'milestones', 'projects', 'users'];
// foreach ($tables as $table) {
//     $conn->query("DROP TABLE IF EXISTS $table");
// }

// // Create tables
// // Users table
// $sql = "CREATE TABLE users (
//     users_id INT AUTO_INCREMENT PRIMARY KEY,
//     users_name VARCHAR(255) NOT NULL,
//     users_email VARCHAR(255) UNIQUE NOT NULL,
//     users_password VARCHAR(255) NOT NULL,
//     users_role ENUM('admin','student','supervisor') NOT NULL,
//     users_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// )";
// $conn->query($sql);

// // Projects table
// $sql = "CREATE TABLE projects (
//     projects_id INT AUTO_INCREMENT PRIMARY KEY,
//     projects_student_id INT NOT NULL,
//     projects_supervisor_id INT DEFAULT NULL,
//     projects_title VARCHAR(255) NOT NULL,
//     projects_description TEXT,
//     projects_status ENUM('pending','in_progress','completed') DEFAULT 'pending',
//     projects_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (projects_student_id) REFERENCES users(users_id),
//     FOREIGN KEY (projects_supervisor_id) REFERENCES users(users_id)
// )";
// $conn->query($sql);

// // Milestones table
// $sql = "CREATE TABLE milestones (
//     milestones_id INT AUTO_INCREMENT PRIMARY KEY,
//     milestones_project_id INT NOT NULL,
//     milestones_title VARCHAR(255) NOT NULL,
//     milestones_description TEXT,
//     milestones_due_date DATE NOT NULL,
//     milestones_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (milestones_project_id) REFERENCES projects(projects_id) ON DELETE CASCADE
// )";
// $conn->query($sql);

// // Submissions table
// $sql = "CREATE TABLE submissions (
//     submissions_id INT AUTO_INCREMENT PRIMARY KEY,
//     submissions_milestone_id INT NOT NULL,
//     submissions_project_id INT NOT NULL,
//     submissions_uploaded_by INT NOT NULL,
//     submissions_file_path VARCHAR(255) NOT NULL,
//     submissions_notes TEXT,
//     submissions_upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (submissions_milestone_id) REFERENCES milestones(milestones_id),
//     FOREIGN KEY (submissions_project_id) REFERENCES projects(projects_id),
//     FOREIGN KEY (submissions_uploaded_by) REFERENCES users(users_id)
// )";
// $conn->query($sql);

// // Evaluations table
// $sql = "CREATE TABLE evaluations (
//     evaluations_id INT AUTO_INCREMENT PRIMARY KEY,
//     evaluations_submission_id INT NOT NULL,
//     evaluations_supervisor_id INT NOT NULL,
//     evaluations_score DECIMAL(5,2),
//     evaluations_feedback TEXT,
//     evaluations_eval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//     FOREIGN KEY (evaluations_submission_id) REFERENCES submissions(submissions_id),
//     FOREIGN KEY (evaluations_supervisor_id) REFERENCES users(users_id)
// )";
// $conn->query($sql);

// // Insert sample data
// $hashed = password_hash('abc123', PASSWORD_DEFAULT);

// $sql = "INSERT INTO users (users_name, users_email, users_password, users_role) VALUES
// ('Admin User', 'admin@tracko.com', '$hashed', 'admin'),
// ('Supervisor One', 'sup1@tracko.com', '$hashed', 'supervisor'),
// ('Supervisor Two', 'sup2@tracko.com', '$hashed', 'supervisor'),
// ('Student One', 'stu1@tracko.com', '$hashed', 'student'),
// ('Student Two', 'stu2@tracko.com', '$hashed', 'student'),
// ('Student Three', 'stu3@tracko.com', '$hashed', 'student')";
// $conn->query($sql);

// $sql = "INSERT INTO projects (projects_student_id, projects_supervisor_id, projects_title, projects_description, projects_status) VALUES
// (4, 2, 'AI Project', 'Developing an AI system for data analysis', 'in_progress'),
// (5, 3, 'Web App', 'Building a responsive web application', 'pending'),
// (6, 2, 'Mobile App', 'Creating a cross-platform mobile app', 'completed')";
// $conn->query($sql);

// $sql = "INSERT INTO milestones (milestones_project_id, milestones_title, milestones_description, milestones_due_date) VALUES
// (1, 'Proposal Submission', 'Submit detailed project proposal', '2025-10-01'),
// (1, 'Mid-term Review', 'Present mid-term progress', '2025-11-15'),
// (1, 'Final Submission', 'Submit final project deliverables', '2025-12-20'),
// (2, 'Initial Design', 'Submit initial design documents', '2025-10-10'),
// (2, 'Development Phase', 'Complete core development', '2025-11-20'),
// (3, 'Prototype Demo', 'Demonstrate working prototype', '2025-09-30'),
// (3, 'Testing Phase', 'Complete testing and bug fixes', '2025-10-15')";
// $conn->query($sql);

// $sql = "INSERT INTO submissions (submissions_milestone_id, submissions_project_id, submissions_uploaded_by, submissions_file_path, submissions_notes) VALUES
// (1, 1, 4, 'uploads/proposal.pdf', 'Initial project proposal document'),
// (2, 1, 4, 'uploads/mid_review.pdf', 'Mid-term review presentation'),
// (3, 1, 4, 'uploads/final.pdf', 'Final project deliverables'),
// (4, 2, 5, 'uploads/design.pdf', 'Initial design mockups'),
// (5, 2, 5, 'uploads/dev_code.zip', 'Development code and documentation'),
// (6, 3, 6, 'uploads/prototype.mp4', 'Prototype demo video'),
// (7, 3, 6, 'uploads/test_report.pdf', 'Testing report and fixes')";
// $conn->query($sql);

// $sql = "INSERT INTO evaluations (evaluations_submission_id, evaluations_supervisor_id, evaluations_score, evaluations_feedback) VALUES
// (1, 2, 85.00, 'Good proposal, needs more detail on methodology'),
// (2, 2, 90.00, 'Excellent progress, well-structured presentation'),
// (3, 2, 95.00, 'Outstanding final submission, well done'),
// (4, 3, 88.00, 'Solid design work, consider user feedback'),
// (5, 3, 92.00, 'Great development work, clean code'),
// (6, 2, 87.00, 'Good prototype, needs some refinements'),
// (7, 2, 93.00, 'Comprehensive testing, project completed successfully')";
// $conn->query($sql);

// $conn->close();
// echo "Database setup complete.";
?>