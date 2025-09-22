<?php
// Supervisor Projects Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

// Get projects assigned to this supervisor
$query = "SELECT
    p.project_id,
    p.project_title,
    p.project_description,
    p.project_status,
    p.project_created_at,

    -- Supervisor details
    sup.user_id   AS supervisor_id,
    sup.user_name AS supervisor_name,
    sup.user_email AS supervisor_email,

    -- Student details
    stu.user_id   AS student_id,
    stu.user_name AS student_name,
    stu.user_email AS student_email

FROM projects p
JOIN users sup ON p.project_supervisor_id = sup.user_id
JOIN users stu ON p.project_student_id = stu.user_id
WHERE sup.user_role = 'supervisor'
AND sup.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>My Supervised Projects</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($projects)): ?>
                    <div class="projects-list">
                        <?php foreach ($projects as $project): ?>
                            <div class="project-card-full">
                                <div class="project-header-section">
                                    <div class="project-title-section">
                                        <h4 class="project-title"><?php echo htmlspecialchars($project['project_title']); ?></h4>
                                        <div class="project-status-section">
                                            <span class="badge badge-<?php
                                            $status = $project['project_status'];
                                            echo $status === 'in_progress' ? 'in-progress' : ($status === 'completed' ? 'completed' : 'pending');
                                            ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                            <span class="project-date">
                                                <i data-lucide="calendar" class="icon-xs"></i>
                                                Created <?php echo date('M j, Y', strtotime($project['project_created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="project-actions-section">
                                        <button class="btn btn-primary btn-sm" onclick="viewProject(<?php echo $project['project_id']; ?>)">
                                            <i data-lucide="eye" class="icon-sm"></i>
                                            View Project
                                        </button>
                                    </div>
                                </div>

                                <div class="project-description-section">
                                    <p class="project-description"><?php echo htmlspecialchars($project['project_description'] ?? 'No description available.'); ?></p>
                                </div>

                                <div class="project-people-section">
                                    <div class="people-grid">
                                        <div class="person-card">
                                            <div class="person-avatar">
                                                <i data-lucide="user" class="person-icon"></i>
                                            </div>
                                            <div class="person-details">
                                                <div class="person-role">Student</div>
                                                <div class="person-name"><?php echo htmlspecialchars($project['student_name']); ?></div>
                                                <div class="person-email"><?php echo htmlspecialchars($project['student_email']); ?></div>
                                            </div>
                                        </div>
                                        <div class="person-card">
                                            <div class="person-avatar supervisor-avatar">
                                                <i data-lucide="user-check" class="person-icon"></i>
                                            </div>
                                            <div class="person-details">
                                                <div class="person-role">Supervisor</div>
                                                <div class="person-name"><?php echo htmlspecialchars($project['supervisor_name']); ?></div>
                                                <div class="person-email"><?php echo htmlspecialchars($project['supervisor_email']); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-projects">
                        <div class="no-projects-icon">
                            <i data-lucide="folder-x" class="icon-lg"></i>
                        </div>
                        <h4>No Projects Assigned</h4>
                        <p>You haven't been assigned any projects to supervise yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function viewProject(projectId) {
        // For now, just show an alert. This can be expanded to show project details modal or navigate to project details page
        alert('View project details for project ID: ' + projectId);
        // TODO: Implement project details view
    }
</script>

<?php $conn->close(); ?>
