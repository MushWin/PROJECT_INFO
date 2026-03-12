<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// Auto-create projects table if it doesn't exist
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $delId = (int)$_POST['project_id'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delId, $userId);
    $stmt->execute();
    $stmt->close();
    $successMsg = 'Project deleted.';
}

// ── Handle SAVE (add / edit) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $projId     = (int)($_POST['project_id'] ?? 0);
    $title      = sanitizeInput($_POST['title'] ?? '');
    $desc       = sanitizeInput($_POST['description'] ?? '');
    $techStack  = sanitizeInput($_POST['tech_stack'] ?? '');
    $projUrl    = sanitizeInput($_POST['project_url'] ?? '');
    $githubUrl  = sanitizeInput($_POST['github_url'] ?? '');
    $sortOrder  = (int)($_POST['sort_order'] ?? 0);

    if (empty($title)) {
        $errorMsg = 'Project title is required.';
    } else {
        // Handle image upload
        $imagePath = '';
        if ($projId > 0) {
            $s = $conn->prepare("SELECT image FROM projects WHERE id = ? AND user_id = ?");
            $s->bind_param("ii", $projId, $userId);
            $s->execute();
            $row = $s->get_result()->fetch_assoc();
            $s->close();
            $imagePath = $row['image'] ?? '';
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = '../uploads/projects/';
            if (!file_exists($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $filename = $userId . '_proj_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/projects/' . $filename;
                } else {
                    $errorMsg = 'Failed to upload image.';
                }
            } else {
                $errorMsg = 'Invalid image type.';
            }
        }

        if (empty($errorMsg)) {
            if ($projId > 0) {
                $stmt = $conn->prepare("UPDATE projects SET title=?, description=?, tech_stack=?, project_url=?, github_url=?, image=?, sort_order=? WHERE id=? AND user_id=?");
                $stmt->bind_param("ssssssiii", $title, $desc, $techStack, $projUrl, $githubUrl, $imagePath, $sortOrder, $projId, $userId);
            } else {
                $stmt = $conn->prepare("INSERT INTO projects (user_id, title, description, tech_stack, project_url, github_url, image, sort_order) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param("issssssi", $userId, $title, $desc, $techStack, $projUrl, $githubUrl, $imagePath, $sortOrder);
            }
            if ($stmt->execute()) {
                $successMsg = $projId > 0 ? 'Project updated.' : 'Project added.';
                logActivity($conn, $userId, $projId > 0 ? 'Updated project: ' . $title : 'Added project: ' . $title);
            } else {
                $errorMsg = 'Database error.';
            }
            $stmt->close();
        }
    }
}

// ── Load existing projects ─────────────────────────────────────────────────
$editProject = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $s = $conn->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
    $s->bind_param("ii", $editId, $userId);
    $s->execute();
    $editProject = $s->get_result()->fetch_assoc();
    $s->close();
}

$projStmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY sort_order ASC, id ASC");
$projStmt->bind_param("i", $userId);
$projStmt->execute();
$allProjects = $projStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$projStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects — Portfolio CMS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .projects-table { width:100%; border-collapse:collapse; margin-top:20px; }
        .projects-table th, .projects-table td { padding:12px 14px; text-align:left; border-bottom:1px solid #e5e7eb; font-size:0.9rem; }
        .projects-table th { background:#f9fafb; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:1px; color:#6b7280; }
        .projects-table td img { width:60px; height:40px; object-fit:cover; border:1px solid #e5e7eb; }
        .tech-pill { display:inline-block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 8px; font-size:0.72rem; font-weight:700; margin:2px; }
        .form-card { background:#fff; border:1px solid #e5e7eb; border-top:4px solid #1d4ed8; padding:28px; margin-bottom:28px; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .form-full { grid-column:1/-1; }
        .form-group label { display:block; font-size:0.82rem; font-weight:700; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; color:#374151; }
        .form-group input, .form-group textarea, .form-group select { width:100%; padding:10px 14px; border:1.5px solid #d1d5db; font-size:0.9rem; font-family:inherit; transition:border-color 0.2s; }
        .form-group input:focus, .form-group textarea:focus { border-color:#1d4ed8; outline:none; }
        .btn-sm { padding:7px 16px; font-size:0.8rem; }
        .btn-danger-sm { background:#ef4444; color:white; border:none; padding:6px 14px; cursor:pointer; font-size:0.8rem; font-weight:700; text-transform:uppercase; }
        .btn-danger-sm:hover { background:#dc2626; }
    </style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div class="sidebar-header"><h2>Portfolio CMS</h2></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="edit_portfolio.php"><i class="fa fa-user-circle"></i> Edit Portfolio</a></li>
            <li class="active"><a href="projects.php"><i class="fa fa-code"></i> Projects</a></li>
            <li><a href="../index.php"><i class="fa fa-eye"></i> View Site</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="content">
        <header class="content-header">
            <h1><?php echo $editProject ? 'Edit Project' : 'Projects'; ?></h1>
        </header>

        <?php if (isset($_GET['seeded'])): ?>
        <div style="background:#d1fae5;color:#065f46;padding:14px 18px;border-left:4px solid #10b981;margin-bottom:20px;font-weight:600;font-size:0.95rem;">
            <i class="fa fa-check-circle"></i>
            <?php if ((int)$_GET['seeded'] > 0): ?>
                <?php echo (int)$_GET['seeded']; ?> project(s) added successfully! Edit the details below to match your exact information.
            <?php else: ?>
                Projects already exist — nothing was duplicated.
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($allProjects) && !$editProject): ?>
        <div style="background:#eff6ff;border:2px solid #1d4ed8;border-left:5px solid #1d4ed8;padding:18px 22px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
            <div>
                <strong style="font-size:1rem;color:#1e3a8a;display:block;margin-bottom:4px;"><i class="fa fa-magic"></i> Quick Start</strong>
                <span style="font-size:0.88rem;color:#1d4ed8;">Click to auto-load Pestcozam &amp; FocusMate with pre-filled details, then edit as needed.</span>
            </div>
            <a href="seed_projects.php" style="background:#1d4ed8;color:white;padding:10px 22px;font-weight:800;font-size:0.82rem;text-transform:uppercase;letter-spacing:1px;text-decoration:none;border:2px solid #1d4ed8;white-space:nowrap;">
                <i class="fa fa-bolt"></i> Load My Projects
            </a>
        </div>
        <?php endif; ?>

        <?php if ($successMsg): ?>
        <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-left:4px solid #10b981;margin-bottom:20px;font-weight:600;">
            <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($successMsg); ?>
        </div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
        <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-left:4px solid #ef4444;margin-bottom:20px;font-weight:600;">
            <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMsg); ?>
        </div>
        <?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="form-card">
            <h2 style="font-size:1.1rem;margin-bottom:22px;font-weight:800;text-transform:uppercase;letter-spacing:1px;">
                <?php echo $editProject ? '<i class="fa fa-pencil"></i> Edit Project' : '<i class="fa fa-plus"></i> Add New Project'; ?>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_project" value="1">
                <input type="hidden" name="project_id" value="<?php echo $editProject ? (int)$editProject['id'] : 0; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Project Title *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>" required placeholder="e.g. Pestcozam">
                    </div>
                    <div class="form-group">
                        <label>Tech Stack (comma-separated)</label>
                        <input type="text" name="tech_stack" value="<?php echo htmlspecialchars($editProject['tech_stack'] ?? ''); ?>" placeholder="PHP, MySQL, Flutter">
                    </div>
                    <div class="form-group form-full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Describe what this project does..."><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Live Demo URL</label>
                        <input type="url" name="project_url" value="<?php echo htmlspecialchars($editProject['project_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>GitHub URL</label>
                        <input type="url" name="github_url" value="<?php echo htmlspecialchars($editProject['github_url'] ?? ''); ?>" placeholder="https://github.com/...">
                    </div>
                    <div class="form-group">
                        <label>Project Image</label>
                        <input type="file" name="image" accept="image/*">
                        <?php if (!empty($editProject['image'])): ?>
                        <div style="margin-top:8px;"><img src="../<?php echo htmlspecialchars($editProject['image']); ?>" style="height:60px;border:1px solid #e5e7eb;"></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label>Sort Order (lower = first)</label>
                        <input type="number" name="sort_order" value="<?php echo (int)($editProject['sort_order'] ?? 0); ?>" min="0">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:18px;">
                    <button type="submit" class="btn btn-sm"><i class="fa fa-save"></i> <?php echo $editProject ? 'Update' : 'Add Project'; ?></button>
                    <?php if ($editProject): ?>
                    <a href="projects.php" class="btn btn-sm" style="background:transparent;color:#1d4ed8;border:2px solid #1d4ed8;">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Projects List -->
        <?php if (!empty($allProjects)): ?>
        <div class="form-card">
            <h2 style="font-size:1.1rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:0;">
                <i class="fa fa-list"></i> All Projects (<?php echo count($allProjects); ?>)
            </h2>
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Tech Stack</th>
                        <th>Links</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProjects as $i => $proj): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td>
                            <?php if (!empty($proj['image'])): ?>
                            <img src="../<?php echo htmlspecialchars($proj['image']); ?>" alt="">
                            <?php else: ?>
                            <span style="color:#9ca3af;font-size:0.8rem;">No image</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700;"><?php echo htmlspecialchars($proj['title']); ?></td>
                        <td>
                            <?php foreach (preg_split('/,/', $proj['tech_stack'] ?? '') as $t): ?>
                                <?php $t = trim($t); if ($t): ?><span class="tech-pill"><?php echo htmlspecialchars($t); ?></span><?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if (!empty($proj['project_url'])): ?><a href="<?php echo htmlspecialchars($proj['project_url']); ?>" target="_blank"><i class="fa fa-external-link"></i> Visit Site</a> <?php endif; ?>
                            <?php if (!empty($proj['github_url'])): ?><a href="<?php echo htmlspecialchars($proj['github_url']); ?>" target="_blank"><i class="fa fa-github"></i> GitHub</a><?php endif; ?>
                        </td>
                        <td style="display:flex;gap:8px;align-items:center;">
                            <a href="projects.php?edit=<?php echo (int)$proj['id']; ?>" class="btn btn-sm" style="padding:6px 14px;font-size:0.78rem;"><i class="fa fa-pencil"></i> Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this project?');" style="display:inline;">
                                <input type="hidden" name="delete_project" value="1">
                                <input type="hidden" name="project_id" value="<?php echo (int)$proj['id']; ?>">
                                <button type="submit" class="btn-danger-sm"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="color:#6b7280;">No projects yet. Add one above.</p>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
