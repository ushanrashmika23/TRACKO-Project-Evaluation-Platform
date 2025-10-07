<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get current page for active menu highlighting
$current_page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRACKO - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>

<body>
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <h2 class="sidebar-title">TRACKO</h2>
            </div>
            <button id="sidebar-toggle" class="sidebar-toggle">
                <i data-lucide="chevron-left" class="sidebar-toggle-icon"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item">
                    <a href="#" data-page="dashboard"
                        class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="sidebar-icon"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" data-page="users"
                        class="sidebar-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <i data-lucide="users" class="sidebar-icon"></i>
                        <span class="sidebar-text">Users</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" data-page="projects"
                        class="sidebar-link <?php echo $current_page === 'projects' ? 'active' : ''; ?>">
                        <i data-lucide="folder-open" class="sidebar-icon"></i>
                        <span class="sidebar-text">Projects</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" data-page="milestones"
                        class="sidebar-link <?php echo $current_page === 'milestones' ? 'active' : ''; ?>">
                        <i data-lucide="target" class="sidebar-icon"></i>
                        <span class="sidebar-text">Milestones</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" data-page="submissions"
                        class="sidebar-link <?php echo $current_page === 'submissions' ? 'active' : ''; ?>">
                        <i data-lucide="file-text" class="sidebar-icon"></i>
                        <span class="sidebar-text">Submissions</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="#" data-page="profile"
                        class="sidebar-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                        <i data-lucide="user" class="sidebar-icon"></i>
                        <span class="sidebar-text">Profile</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../logout.php" class="sidebar-link sidebar-logout">
                    <i data-lucide="log-out" class="sidebar-icon"></i>
                    <span class="sidebar-text">Logout</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <div id="main-content" class="main-content">
        <!-- Navbar -->
        <header class="navbar">
            <div class="navbar-left">
                <button id="mobile-sidebar-toggle" class="mobile-sidebar-toggle">
                    <i data-lucide="menu" class="navbar-icon"></i>
                </button>
                <h1 class="navbar-title" id="navbar-title">
                    <?php
                    $page_titles = [
                        'dashboard' => 'Dashboard',
                        'users' => 'Users',
                        'projects' => 'Projects',
                        'milestones' => 'Milestones',
                        'submissions' => 'Submissions',
                        'profile' => 'Profile'
                    ];
                    echo $page_titles[$current_page] ?? 'Admin Portal';
                    ?>
                </h1>
            </div>
            <div class="navbar-right">
                <div class="navbar-user">
                    <span class="navbar-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <div class="navbar-user-avatar">
                        <i data-lucide="user" class="navbar-avatar-icon"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="page-content">
            <!-- Content will be loaded here based on the page -->
            <div class="content-container">
                <?php
                // Include the appropriate page content
                $allowed_pages = ['dashboard', 'users', 'projects', 'milestones', 'submissions', 'profile'];
                if (in_array($current_page, $allowed_pages)) {
                    $page_file = $current_page . '.php';
                    if (file_exists($page_file)) {
                        include $page_file;
                    } else {
                        echo '<div class="welcome-section">';
                        echo '<h2>Page Not Found</h2>';
                        echo '<p>The requested page could not be loaded.</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="welcome-section">';
                    echo '<h2>Welcome, ' . htmlspecialchars($_SESSION['user_name']) . '!</h2>';
                    echo '<p>Select an option from the sidebar to get started.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
        const mainContent = document.getElementById('main-content');
        const toggleIcon = sidebarToggle.querySelector('.sidebar-toggle-icon');

        // Function to toggle sidebar
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');

            // Update toggle icon
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.setAttribute('data-lucide', 'chevron-right');
            } else {
                toggleIcon.setAttribute('data-lucide', 'chevron-left');
            }
            lucide.createIcons();
        }

        // Desktop sidebar toggle
        sidebarToggle.addEventListener('click', toggleSidebar);

        // Mobile sidebar toggle
        mobileSidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('mobile-open');
        });

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function (event) {
            if (!sidebar.contains(event.target) && !mobileSidebarToggle.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Function to load page content
        function loadPage(page) {
            fetch(page + '.php')
                .then(response => response.text())
                .then(html => {
                    document.querySelector('.content-container').innerHTML = html;
                    // Update URL without reload
                    history.pushState({ page: page }, '', '?page=' + page);
                    // Update active link
                    document.querySelectorAll('.sidebar-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    document.querySelector(`[data-page="${page}"]`).classList.add('active');
                    // Update navbar title
                    const titles = {
                        'dashboard': 'Dashboard',
                        'users': 'Users',
                        'projects': 'Projects',
                        'milestones': 'Milestones',
                        'submissions': 'Submissions',
                        'profile': 'Profile'
                    };
                    document.getElementById('navbar-title').textContent = titles[page] || 'Admin Portal';
                    // Reinitialize Lucide icons
                    lucide.createIcons();
                })
                .catch(error => {
                    console.error('Error loading page:', error);
                    document.querySelector('.content-container').innerHTML = '<div class="welcome-section"><h2>Error</h2><p>Failed to load page content.</p></div>';
                });
        }

        // Add event listeners to sidebar links
        document.querySelectorAll('.sidebar-link[data-page]').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                loadPage(page);
            });
        });

        // Handle browser back/forward
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.page) {
                loadPage(e.state.page);
            }
        });
    </script>
</body>

</html>