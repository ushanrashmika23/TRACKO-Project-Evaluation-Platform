<?php
// Admin Dashboard Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Get dashboard statistics

// Total users by role
$users_query = "SELECT
    COUNT(CASE WHEN user_role = 'admin' THEN 1 END) as total_admins,
    COUNT(CASE WHEN user_role = 'supervisor' THEN 1 END) as total_supervisors,
    COUNT(CASE WHEN user_role = 'student' THEN 1 END) as total_students,
    COUNT(*) as total_users
    FROM users";
$users_result = $conn->query($users_query);
$users_stats = $users_result->fetch_assoc();

// Total projects
$projects_query = "SELECT COUNT(*) as total_projects FROM projects";
$projects_result = $conn->query($projects_query);
$total_projects = $projects_result->fetch_assoc()['total_projects'];

// Total milestones
$milestones_query = "SELECT COUNT(*) as total_milestones FROM milestones";
$milestones_result = $conn->query($milestones_query);
$total_milestones = $milestones_result->fetch_assoc()['total_milestones'];

// Total submissions
$submissions_query = "SELECT COUNT(*) as total_submissions FROM submissions";
$submissions_result = $conn->query($submissions_query);
$total_submissions = $submissions_result->fetch_assoc()['total_submissions'];

// Total evaluations
$evaluations_query = "SELECT COUNT(*) as total_evaluations FROM evaluations";
$evaluations_result = $conn->query($evaluations_query);
$total_evaluations = $evaluations_result->fetch_assoc()['total_evaluations'];

// Recent user registrations (last 5)
$recent_users_query = "SELECT user_id, user_name, user_email, user_role
                       FROM users
                       ORDER BY user_id DESC
                       LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);
$recent_users = [];
while ($row = $recent_users_result->fetch_assoc()) {
    $recent_users[] = $row;
}

// Recent submissions (last 5)
$recent_submissions_query = "SELECT s.submission_id, s.submission_upload_date, p.project_title,
                                    u.user_name as student_name, m.milestone_title
                             FROM submissions s
                             LEFT JOIN projects p ON s.submission_project_id = p.project_id
                             LEFT JOIN users u ON s.submission_uploaded_by = u.user_id
                             LEFT JOIN milestones m ON s.submission_milestone_id = m.milestone_id
                             ORDER BY s.submission_upload_date DESC
                             LIMIT 5";
$recent_submissions_result = $conn->query($recent_submissions_query);
$recent_submissions = [];
while ($row = $recent_submissions_result->fetch_assoc()) {
    $recent_submissions[] = $row;
}

// Recent evaluations (last 5)
$recent_evaluations_query = "SELECT e.evaluation_eval_date, e.evaluation_score,
                                    p.project_title, u.user_name as student_name,
                                    sup.user_name as supervisor_name
                             FROM evaluations e
                             JOIN submissions s ON e.evaluation_submission_id = s.submission_id
                             LEFT JOIN projects p ON s.submission_project_id = p.project_id
                             LEFT JOIN users u ON s.submission_uploaded_by = u.user_id
                             LEFT JOIN users sup ON e.evaluation_supervisor_id = sup.user_id
                             ORDER BY e.evaluation_eval_date DESC
                             LIMIT 5";
$recent_evaluations_result = $conn->query($recent_evaluations_query);
$recent_evaluations = [];
while ($row = $recent_evaluations_result->fetch_assoc()) {
    $recent_evaluations[] = $row;
}

$conn->close();
?>

<div class="row">
    <div class="col">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>Here's an overview of your system administration activities.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row dashboard-stats-wrapper" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="users" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $users_stats['total_users']; ?></h4>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="folder-open" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $total_projects; ?></h4>
                    <p>Total Projects</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="target" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $total_milestones; ?></h4>
                    <p>Total Milestones</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="file-text" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $total_submissions; ?></h4>
                    <p>Total Submissions</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i data-lucide="clipboard-check" class="stat-icon-svg"></i>
                </div>
                <div class="stat-content">
                    <h4><?php echo $total_evaluations; ?></h4>
                    <p>Total Evaluations</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row dashboard-stats-wrapper activity-row justify-content-between">
            <div class="col">
                <div class="card activity-card">
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
                                                <strong><?php echo htmlspecialchars($evaluation['supervisor_name']); ?></strong>
                                                evaluated
                                                <strong><?php echo htmlspecialchars($evaluation['student_name']); ?></strong>
                                                <br><small><?php echo htmlspecialchars($evaluation['project_title'] ?? 'Unknown Project'); ?>
                                                    - Score:
                                                    <?php echo htmlspecialchars($evaluation['evaluation_score']); ?>/100</small>
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

            <div class="col">
                <div class="card activity-card">
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
                                                submitted for
                                                <strong><?php echo htmlspecialchars($submission['milestone_title'] ?? 'Unknown Milestone'); ?></strong>
                                                <br><small><?php echo htmlspecialchars($submission['project_title'] ?? 'Unknown Project'); ?></small>
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

            <div class="col">
                <div class="card activity-card">
                    <div class="card-header">
                        <h5>Recent User Registrations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_users)): ?>
                            <div class="activity-list">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i data-lucide="user-plus" class="activity-icon-svg"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <strong><?php echo htmlspecialchars($user['user_name']); ?></strong>
                                                registered as
                                                <strong><?php echo htmlspecialchars($user['user_role']); ?></strong>
                                            </div>
                                            <div class="activity-time">
                                                Recently registered
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No recent registrations</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="row dashboard-stats-wrapper">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5>System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="status-grid">
                            <div class="status-item">
                                <div class="status-icon">
                                    <i data-lucide="database" class="status-icon-svg"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Database</h6>
                                    <span class="status-badge status-active">Active</span>
                                </div>
                            </div>

                            <div class="status-item">
                                <div class="status-icon">
                                    <i data-lucide="server" class="status-icon-svg"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Web Server</h6>
                                    <span class="status-badge status-active">Running</span>
                                </div>
                            </div>

                            <div class="status-item">
                                <div class="status-icon">
                                    <i data-lucide="upload" class="status-icon-svg"></i>
                                </div>
                                <div class="status-content">
                                    <h6>File Uploads</h6>
                                    <span class="status-badge status-active">Enabled</span>
                                </div>
                            </div>

                            <div class="status-item">
                                <div class="status-icon">
                                    <i data-lucide="shield" class="status-icon-svg"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Security</h6>
                                    <span class="status-badge status-active">Protected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>