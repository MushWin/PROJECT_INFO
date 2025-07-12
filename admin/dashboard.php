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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Portfolio CMS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Portfolio CMS</h2>
            </div>
            <ul class="sidebar-menu">
                <li class="active"><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href="edit_portfolio.php">
                    <i class="fa fa-user-circle"></i> <?php echo $hasPortfolio ? 'Edit Portfolio' : 'Create Portfolio'; ?>
                </a></li>
                <li><a href="../index.php"><i class="fa fa-eye"></i> View Site</a></li>
                <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
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
                    <i class="fa fa-file-text"></i>
                    <div class="overview-content">
                        <h3>Portfolio Status</h3>
                        <?php if ($hasPortfolio): ?>
                            <p class="success">Your portfolio is active</p>
                            <a href="edit_portfolio.php" class="btn btn-small">Edit Portfolio</a>
                        <?php else: ?>
                            <p class="warning">No portfolio setup yet</p>
                            <a href="edit_portfolio.php" class="btn btn-small">Create Portfolio</a>
                        <?php endif; ?>
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
            
        });
    </script>
</body>
</html>
