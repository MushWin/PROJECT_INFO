<?php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Auto-create table
$conn->query("CREATE TABLE IF NOT EXISTS `projects` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT NOT NULL,
    `title`       VARCHAR(200) NOT NULL,
    `description` TEXT,
    `tech_stack`  VARCHAR(500),
    `project_url` VARCHAR(500),
    `github_url`  VARCHAR(500),
    `image`       VARCHAR(500),
    `sort_order`  INT DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$defaultProjects = [
    [
        'title'       => 'Pestcozam',
        'description' => 'A web-based pest management information system developed for a local government unit. The system provides pest identification, treatment recommendations, and reporting tools to help agricultural communities manage pest-related issues efficiently.',
        'tech_stack'  => 'PHP, MySQL, HTML, CSS, JavaScript, XAMPP',
        'project_url' => '',
        'github_url'  => '',
        'sort_order'  => 1,
    ],
    [
        'title'       => 'FocusMate',
        'description' => 'A mobile productivity application designed to help users manage their focus sessions and tasks. Features include a Pomodoro-style timer, task tracking, and session history to improve study and work habits.',
        'tech_stack'  => 'Flutter, Dart, Firebase',
        'project_url' => '',
        'github_url'  => '',
        'sort_order'  => 2,
    ],
];

$inserted = 0;
$skipped  = 0;

foreach ($defaultProjects as $proj) {
    // Check if project with same title already exists for this user
    $check = $conn->prepare("SELECT id FROM projects WHERE user_id = ? AND title = ?");
    $check->bind_param("is", $userId, $proj['title']);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $skipped++;
        continue;
    }

    $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, tech_stack, project_url, github_url, image, sort_order) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("issssssi",
        $userId,
        $proj['title'],
        $proj['description'],
        $proj['tech_stack'],
        $proj['project_url'],
        $proj['github_url'],
        $dummy = '',
        $proj['sort_order']
    );
    $stmt->execute();
    $stmt->close();
    $inserted++;
}

$conn->close();

// Redirect to projects page
header("Location: projects.php?seeded=$inserted&skipped=$skipped");
exit;
?>
