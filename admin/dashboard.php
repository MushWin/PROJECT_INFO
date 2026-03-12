<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT username, first_name, last_name, email FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$portfolioStmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ?");
$portfolioStmt->bind_param("i", $userId);
$portfolioStmt->execute();
$portfolioResult = $portfolioStmt->get_result();
$hasPortfolio = $portfolioResult->num_rows > 0;
$portfolio = $hasPortfolio ? $portfolioResult->fetch_assoc() : null;
$portfolioStmt->close();

$activityStmt = $conn->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$activityStmt->bind_param("i", $userId);
$activityStmt->execute();
$activityResult = $activityStmt->get_result();
$activityStmt->close();

// Project count
$conn->query("CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT,
    `tech_stack` VARCHAR(500),
    `project_url` VARCHAR(500),
    `github_url` VARCHAR(500),
    `image` VARCHAR(500),
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$projectCountStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM projects WHERE user_id = ?");
$projectCountStmt->bind_param("i", $userId);
$projectCountStmt->execute();
$projectCount = $projectCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$projectCountStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portfolio CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="sidebar-logo">
                    <span class="logo-geo">//</span>
                    <span class="logo-text">Portfolio CMS</span>
                </a>
            </div>
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="dashboard.php"><i class="fa fa-dashboard"></i><span>Dashboard</span></a>
                </li>
                <li>
                    <a href="edit_portfolio.php"><i class="fa fa-user-circle"></i><span><?php echo $hasPortfolio ? 'Edit Portfolio' : 'Create Portfolio'; ?></span></a>
                </li>
                <li>
                    <a href="projects.php"><i class="fa fa-code"></i><span>Projects</span></a>
                </li>
                <li>
                    <a href="../index.php" target="_blank"><i class="fa fa-eye"></i><span>View Site</span></a>
                </li>
                <li class="sidebar-divider"></li>
                <li class="logout-item">
                    <a href="../logout.php"><i class="fa fa-sign-out"></i><span>Logout</span></a>
                </li>
            </ul>
        </aside>
        
        <main class="content">
            <header class="content-header">
                <h1>Welcome, <?php echo htmlspecialchars($userData['first_name']); ?>!</h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($userData['email']); ?></span>
                </div>
            </header>
            
            <div class="dashboard-overview">
                <div class="overview-card">
                    <div class="card-icon"><i class="fa fa-file-text"></i></div>
                    <div class="overview-content">
                        <h3>Portfolio Status</h3>
                        <?php if ($hasPortfolio): ?>
                            <p class="success" style="margin-bottom:10px;"><i class="fa fa-check-circle"></i> Active</p>
                            <a href="edit_portfolio.php" class="btn-small">Edit Portfolio</a>
                        <?php else: ?>
                            <p class="warning" style="margin-bottom:10px;"><i class="fa fa-exclamation-circle"></i> Not set up yet</p>
                            <a href="edit_portfolio.php" class="btn-small">Create Portfolio</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="overview-card">
                    <div class="card-icon"><i class="fa fa-code"></i></div>
                    <div class="overview-content">
                        <h3>Projects</h3>
                        <div class="stat-number"><?php echo $projectCount; ?></div>
                        <a href="projects.php" class="btn-small">Manage Projects</a>
                    </div>
                </div>
            </div>
            
            <?php if ($hasPortfolio): ?>
            <div class="dashboard-section">
                <h2>Portfolio Summary</h2>
                <div class="portfolio-summary">
                    <div class="summary-item">
                        <h3>Personal Info</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($portfolio['name']); ?></p>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($portfolio['title']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($portfolio['email']); ?></p>
                    </div>
                    
                    <div class="summary-item">
                        <h3>Content Sections</h3>
                        <ul class="content-checklist">
                            <li class="<?php echo !empty($portfolio['about_me']) ? 'complete' : 'incomplete'; ?>">
                                About Me
                            </li>
                            <li class="<?php echo !empty($portfolio['education']) ? 'complete' : 'incomplete'; ?>">
                                Education
                            </li>
                            <li class="<?php echo !empty($portfolio['experience']) ? 'complete' : 'incomplete'; ?>">
                                Experience
                            </li>
                            <li class="<?php echo !empty($portfolio['skills']) ? 'complete' : 'incomplete'; ?>">
                                Skills
                            </li>
                            <li class="<?php echo !empty($portfolio['certifications']) ? 'complete' : 'incomplete'; ?>">
                                Certifications
                            </li>
                        </ul>
                    </div>
                    
                    <div class="summary-item">
                        <h3>Media & Links</h3>
                        <p class="<?php echo !empty($portfolio['profile_image']) ? 'complete' : 'incomplete'; ?>">
                            <i class="fa fa-<?php echo !empty($portfolio['profile_image']) ? 'check' : 'times'; ?>"></i> Profile Image
                        </p>
                        <p class="<?php echo !empty($portfolio['linkedin']) ? 'complete' : 'incomplete'; ?>">
                            <i class="fa fa-<?php echo !empty($portfolio['linkedin']) ? 'check' : 'times'; ?>"></i> LinkedIn
                        </p>
                        <p class="<?php echo !empty($portfolio['github']) ? 'complete' : 'incomplete'; ?>">
                            <i class="fa fa-<?php echo !empty($portfolio['github']) ? 'check' : 'times'; ?>"></i> GitHub
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-section">
                <h2>Recent Activity</h2>
                <div class="activity-log">
                    <?php if ($activityResult->num_rows > 0): ?>
                        <ul>
                            <?php while ($log = $activityResult->fetch_assoc()): ?>
                                <li>
                                    <span class="activity-time"><?php echo date('M d, H:i', strtotime($log['timestamp'])); ?></span>
                                    <span class="activity-action"><?php echo htmlspecialchars($log['action']); ?></span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent activity.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /* Stagger card animations */
            document.querySelectorAll('.overview-card, .dashboard-section').forEach((el, i) => {
                el.style.animationDelay = (i * 0.08) + 's';
            });
        });
    </script>
</body>
</html>
