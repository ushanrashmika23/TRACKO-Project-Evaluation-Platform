<?php
// Student Dashboard Content
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h3>Project Overview</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="folder" class="stat-icon-svg"></i>
                            </div>
                            <div class="stat-content">
                                <h4 class="stat-number">1</h4>
                                <p class="stat-label">Active Projects</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="check-circle" class="stat-icon-svg"></i>
                            </div>
                            <div class="stat-content">
                                <h4 class="stat-number">2</h4>
                                <p class="stat-label">Completed Milestones</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i data-lucide="clock" class="stat-icon-svg"></i>
                            </div>
                            <div class="stat-content">
                                <h4 class="stat-number">1</h4>
                                <p class="stat-label">Pending Submissions</p>
                            </div>
                        </div>
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
                <h3>Recent Activity</h3>
            </div>
            <div class="card-body">
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i data-lucide="file-text" class="activity-icon-svg"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">Submitted proposal for AI Project</p>
                            <span class="activity-time">2 days ago</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i data-lucide="star" class="activity-icon-svg"></i>
                        </div>
                        <div class="activity-content">
                            <p class="activity-text">Received feedback on mid-term review</p>
                            <span class="activity-time">1 week ago</span>
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
