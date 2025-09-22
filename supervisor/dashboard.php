<?php
// Supervisor Dashboard Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Get dashboard statistics
$supervisor_id = $_SESSION['user_id'];

// Total projects supervised
$projects_query = "SELECT COUNT(*) as total_projects FROM projects WHERE project_supervisor_id = ?";
$projects_stmt = $conn->prepare($projects_query);
$projects_stmt->bind_param("i", $supervisor_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();
$total_projects = $projects_result->fetch_assoc()['total_projects'];
$projects_stmt->close();

// Total students supervised
$students_query = "SELECT COUNT(DISTINCT project_student_id) as total_students FROM projects WHERE project_supervisor_id = ?";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $supervisor_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$total_students = $students_result->fetch_assoc()['total_students'];
$students_stmt->close();

// Pending evaluations
$pending_query = "SELECT COUNT(*) as pending_evaluations FROM submissions s
                  JOIN projects p ON s.submission_project_id = p.project_id
                  LEFT JOIN evaluations e ON s.submission_id = e.evaluation_submission_id
                  WHERE p.project_supervisor_id = ? AND e.evaluation_id IS NULL";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("i", $supervisor_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_evaluations = $pending_result->fetch_assoc()['pending_evaluations'];
$pending_stmt->close();

// Completed evaluations
$completed_query = "SELECT COUNT(*) as completed_evaluations FROM evaluations e
                    JOIN submissions s ON e.evaluation_submission_id = s.submission_id
                    JOIN projects p ON s.submission_project_id = p.project_id
                    WHERE p.project_supervisor_id = ?";
$completed_stmt = $conn->prepare($completed_query);
$completed_stmt->bind_param("i", $supervisor_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();
$completed_evaluations = $completed_result->fetch_assoc()['completed_evaluations'];
$completed_stmt->close();

// Recent submissions (last 5)
$recent_query = "SELECT s.submission_id, s.submission_upload_date, p.project_title, u.user_name as student_name, u.user_email as student_email
                 FROM submissions s
                 JOIN projects p ON s.submission_project_id = p.project_id
                 JOIN users u ON s.submission_student_id = u.user_id
                 WHERE p.project_supervisor_id = ?
                 ORDER BY s.submission_upload_date DESC
                 LIMIT 5";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $supervisor_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_submissions = [];
while ($row = $recent_result->fetch_assoc()) {
    $recent_submissions[] = $row;
}
$recent_stmt->close();

// Recent evaluations (last 5)
$recent_eval_query = "SELECT e.evaluation_eval_date, p.project_title, u.user_name as student_name, e.evaluation_score
                      FROM evaluations e
                      JOIN submissions s ON e.evaluation_submission_id = s.submission_id
                      JOIN projects p ON s.submission_project_id = p.project_id
                      JOIN users u ON s.submission_student_id = u.user_id
                      WHERE p.project_supervisor_id = ?
                      ORDER BY e.evaluation_eval_date DESC
                      LIMIT 5";
$recent_eval_stmt = $conn->prepare($recent_eval_query);
$recent_eval_stmt->bind_param("i", $supervisor_id);
$recent_eval_stmt->execute();
$recent_eval_result = $recent_eval_stmt->get_result();
$recent_evaluations = [];
while ($row = $recent_eval_result->fetch_assoc()) {
    $recent_evaluations[] = $row;
}
$recent_eval_stmt->close();

$conn->close();
?>

<div class="row">
    <div class="col">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>Here's an overview of your supervision activities.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
            <!-- <div class="col-md-3"> -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i data-lucide="folder-open" class="stat-icon-svg"></i>
                    </div>
                    <div class="stat-content">
                        <h4><?php echo $total_projects; ?></h4>
                        <p>Total Projects</p>
                    </div>
                </div>
            <!-- </div> -->
            <!-- <div class="col-md-3"> -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="users" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $total_students; ?></h4>
                    <p>Total Students</p>
                </div>
            </div>
            <!-- </div> -->
            <!-- <div class="col-md-3"> -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="clipboard-x" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $pending_evaluations; ?></h4>
                    <p>Pending Evaluations</p>
                </div>
            </div>
            <!-- </div> -->
            <!-- <div class="col-md-3"> -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="clipboard-check" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $completed_evaluations; ?></h4>
                    <p>Completed Evaluations</p>
                </div>
            </div>
            <!-- </div> -->
        </div>

        <!-- Recent Activity -->
        <div class="row dashboard-stats-wrapper">
            <div class="col-md-6">
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h5>Recent Submissions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_submissions)): ?>
                            <div class="activity-list">
                                <?php foreach ($recent_submissions as $submission): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i data-lucide="file-text" class="activity-icon-svg"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <strong><?php echo htmlspecialchars($submission['student_name']); ?></strong>
                                                submitted work for
                                                <strong><?php echo htmlspecialchars($submission['project_title']); ?></strong>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($submission['submission_upload_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No recent submissions</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h5>Recent Evaluations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_evaluations)): ?>
                            <div class="activity-list">
                                <?php foreach ($recent_evaluations as $evaluation): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i data-lucide="clipboard-check" class="activity-icon-svg"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                Evaluated
                                                <strong><?php echo htmlspecialchars($evaluation['student_name']); ?></strong>'s
                                                submission for
                                                <strong><?php echo htmlspecialchars($evaluation['project_title']); ?></strong> -
                                                Score:
                                                <strong><?php echo htmlspecialchars($evaluation['evaluation_score']); ?>/100</strong>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo date('M j, Y', strtotime($evaluation['evaluation_eval_date'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No recent evaluations</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>