<?php
// Student Profile Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Get student's project information
$sql = "SELECT
    p.project_id AS project_id,
    p.project_title AS project_title,
    p.project_description AS project_description,
    p.project_status AS project_status,
    p.project_created_at AS project_created_at,
    stu.user_id AS student_id,
    stu.user_name AS student_name,
    stu.user_email AS student_email,
    sup.user_id AS supervisor_id,
    sup.user_name AS supervisor_name,
    sup.user_email AS supervisor_email
FROM
    projects p
JOIN users stu ON
    p.project_student_id = stu.user_id
JOIN users sup ON
    p.project_supervisor_id = sup.user_id
WHERE
    stu.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>My Profile</h3>
            </div>
            <div class="card-body">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <i data-lucide="user" class="profile-avatar-icon"></i>
                    </div>
                    <div class="profile-details">
                        <h4><?php echo htmlspecialchars($_SESSION['user_name']); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        <span class="badge badge-in-progress">Student</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php



?>


<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>Project Information</h3>
            </div>
            <div class="card-body">
                <div class="project-info">
                    <div class="info-item">
                        <label>Project Title:</label>
                        <span><?php echo htmlspecialchars($project['project_title'] ?? 'No project assigned'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Supervisor:</label>
                        <span><?php echo htmlspecialchars($project['supervisor_name'] ?? 'Not assigned'); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Status:</label>
                        <span class="badge badge-<?php
                        $status = $project['project_status'] ?? 'pending';
                        echo $status === 'in_progress' ? 'in-progress' : ($status === 'completed' ? 'completed' : 'pending');
                        ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Start Date:</label>
                        <span><?php echo htmlspecialchars(date('F j, Y', strtotime($project['project_created_at'] ?? 'now'))); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Deadline:</label>
                        <span><?php
                        // Get the latest milestone due date as deadline
                        // if ($project) {
                        //     $milestone_query = "SELECT MAX(milestones_due_date) as deadline FROM milestones WHERE milestones_project_id = ?";
                        //     $stmt = $conn->prepare($milestone_query);
                        //     $stmt->bind_param("i", $project['project_id']);
                        //     $stmt->execute();
                        //     $milestone_result = $stmt->get_result();
                        //     $milestone = $milestone_result->fetch_assoc();
                        //     echo htmlspecialchars(date('F j, Y', strtotime($milestone['deadline'] ?? 'now')));
                        //     $stmt->close();
                        // } else {
                        //     echo 'N/A';
                        // }
                        echo 'N/A TODO: add deadline from nearest milestone';
                        ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>Account Settings</h3>
            </div>
            <div class="card-body">
                <form class="settings-form">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>
                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-md">
                        <i data-lucide="save" class="icon-sm"></i>
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
<?php $conn->close(); ?>