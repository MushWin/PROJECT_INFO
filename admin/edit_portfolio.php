<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

function isJson($string) {
    json_decode($string);
    return (json_last_error() === JSON_ERROR_NONE);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

$userStmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$portfolioStmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ?");
$portfolioStmt->bind_param("i", $userId);
$portfolioStmt->execute();
$result = $portfolioStmt->get_result();
$portfolio = $result->num_rows > 0 ? $result->fetch_assoc() : null;
$portfolioStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_portfolio'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $title = sanitizeInput($_POST['title'] ?? '');
    $shortBio = sanitizeInput($_POST['short_bio'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    
    $aboutMe = sanitizeInput($_POST['about_me'] ?? '');
    $education = sanitizeInput($_POST['education'] ?? '');
    $experience = sanitizeInput($_POST['experience'] ?? '');
    $skills = sanitizeInput($_POST['skills'] ?? '');
    $certifications = sanitizeInput($_POST['certifications'] ?? '');
    $contactSection = sanitizeInput($_POST['contact_section'] ?? '');
    
    $linkedin = sanitizeInput($_POST['linkedin'] ?? '');
    $github = sanitizeInput($_POST['github'] ?? '');
    $cvLink = sanitizeInput($_POST['cv_link'] ?? '');
    
    $profileImage = $portfolio ? $portfolio['profile_image'] : '';
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $uploadDir = '../uploads/profile/';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $errorMsg = 'Failed to create upload directory for profile images.';
            } else {
                chmod($uploadDir, 0777);
            }
        }
        
        if (empty($errorMsg)) {
            $fileExt = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $newFilename = $userId . '_profile_' . time() . '.' . $fileExt;
            $uploadFile = $uploadDir . $newFilename;
            
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile)) {
                    $profileImage = 'uploads/profile/' . $newFilename;
                } else {
                    $errorMsg = 'Failed to upload profile image.';
                }
            } else {
                $errorMsg = 'Invalid file type. Please upload JPG, PNG, or GIF.';
            }
        }
    }
    
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === 0) {
        $uploadDir = '../uploads/cv/';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $errorMsg = 'Failed to create upload directory for CV files.';
            } else {
                chmod($uploadDir, 0777);
            }
        }
        
        if (empty($errorMsg)) {
            $fileExt = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
            $newFilename = $userId . '_cv_' . time() . '.' . $fileExt;
            $uploadFile = $uploadDir . $newFilename;
            
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (in_array($_FILES['cv_file']['type'], $allowedTypes)) {
                if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $uploadFile)) {
                    $cvLink = 'uploads/cv/' . $newFilename;
                } else {
                    $errorMsg = 'Failed to upload CV file.';
                }
            } else {
                $errorMsg = 'Invalid file type. Please upload PDF or Word document.';
            }
        }
    }
    
    $certificatesData = [];
    
    if (!empty($portfolio['certifications']) && isJson($portfolio['certifications'])) {
        $certificatesData = json_decode($portfolio['certifications'], true);
    }

    $deletedCertIds = [];
    if (isset($_POST['deleted_cert_ids']) && !empty($_POST['deleted_cert_ids'])) {
        $deletedCertIds = explode(',', $_POST['deleted_cert_ids']);
        foreach ($deletedCertIds as $id) {
            if (isset($certificatesData[$id])) {
                unset($certificatesData[$id]);
            }
        }
        $certificatesData = array_values($certificatesData);
    }
    
    if (isset($_FILES['certificate_images']) && is_array($_FILES['certificate_images']['name'])) {
        $uploadDir = '../uploads/certificates/';
        
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $errorMsg = 'Failed to create upload directory for certificates.';
            } else {
                chmod($uploadDir, 0777);
            }
        }
        
        for ($i = 0; $i < count($_FILES['certificate_images']['name']); $i++) {
            if ($_FILES['certificate_images']['error'][$i] === 0) {
                $certTitle = sanitizeInput($_POST['certificate_titles'][$i] ?? 'Certificate');
                $certDescription = sanitizeInput($_POST['certificate_descriptions'][$i] ?? '');
                $certDate = sanitizeInput($_POST['certificate_dates'][$i] ?? '');
                
                $fileExt = strtolower(pathinfo($_FILES['certificate_images']['name'][$i], PATHINFO_EXTENSION));
                $newFilename = $userId . '_cert_' . time() . '_' . $i . '.' . $fileExt;
                $uploadFile = $uploadDir . $newFilename;
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                if (in_array($_FILES['certificate_images']['type'][$i], $allowedTypes)) {
                    if (move_uploaded_file($_FILES['certificate_images']['tmp_name'][$i], $uploadFile)) {
                        $certificatesData[] = [
                            'title' => $certTitle,
                            'description' => $certDescription,
                            'date' => $certDate,
                            'image' => 'uploads/certificates/' . $newFilename
                        ];
                    } else {
                        $errorMsg = 'Failed to upload one or more certificate images.';
                    }
                } else {
                    $errorMsg = 'Invalid file type for certificates. Please upload JPG, PNG, GIF, or PDF.';
                }
            }
        }
    }
    
    if (isset($_POST['existing_cert_titles'])) {
        for ($i = 0; $i < count($_POST['existing_cert_titles']); $i++) {
            $certId = $_POST['existing_cert_ids'][$i] ?? null;
            $certTitle = sanitizeInput($_POST['existing_cert_titles'][$i] ?? '');
            $certDescription = sanitizeInput($_POST['existing_cert_descriptions'][$i] ?? '');
            $certDate = sanitizeInput($_POST['existing_cert_dates'][$i] ?? '');
            $certImage = $_POST['existing_cert_images'][$i] ?? null;
            
            if (!empty($certTitle) && is_numeric($certId) && isset($certificatesData[$certId])) {
                $certificatesData[$certId]['title'] = $certTitle;
                $certificatesData[$certId]['description'] = $certDescription;
                $certificatesData[$certId]['date'] = $certDate;
            }
        }
    }
    
    $certifications = !empty($certificatesData) ? json_encode($certificatesData) : '';
    
    if (empty($errorMsg)) {
        try {
            $conn->begin_transaction();
            
            if ($portfolio) {
                $sql = "UPDATE portfolio SET 
                        name = ?, title = ?, short_bio = ?, email = ?, phone = ?, location = ?,
                        about_me = ?, education = ?, experience = ?, skills = ?, certifications = ?, 
                        contact_section = ?, linkedin = ?, github = ?, cv_link = ?, profile_image = ?
                        WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssssssssi",
                    $name, $title, $shortBio, $email, $phone, $location,
                    $aboutMe, $education, $experience, $skills, $certifications,
                    $contactSection, $linkedin, $github, $cvLink, $profileImage,
                    $userId
                );
            } else {
                $sql = "INSERT INTO portfolio (user_id, name, title, short_bio, email, phone, location,
                        about_me, education, experience, skills, certifications, contact_section,
                        linkedin, github, cv_link, profile_image) VALUES 
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "issssssssssssssss",
                    $userId, $name, $title, $shortBio, $email, $phone, $location,
                    $aboutMe, $education, $experience, $skills, $certifications,
                    $contactSection, $linkedin, $github, $cvLink, $profileImage
                );
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $conn->commit();
            $successMsg = 'Portfolio saved successfully!';
            
            $portfolioStmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ?");
            $portfolioStmt->bind_param("i", $userId);
            $portfolioStmt->execute();
            $result = $portfolioStmt->get_result();
            $portfolio = $result->fetch_assoc();
            $portfolioStmt->close();
            
        } catch (Exception $e) {
            if ($conn->connect_errno == 0) {
                $conn->rollback();
            }
            $errorMsg = 'Error saving portfolio: ' . $e->getMessage();
        } finally {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
        }
    }
}

if (!$conn || $conn->connect_errno) {
    require_once '../includes/db_connection.php';
}

// ── Projects table ────────────────────────────────────────────────────────
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

// ── Handle project DELETE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $delId = (int)$_POST['project_id'];
    $ds = $conn->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
    $ds->bind_param("ii", $delId, $userId);
    $ds->execute();
    $ds->close();
    $successMsg = 'Project deleted.';
}

// ── Handle project SAVE ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $projId  = (int)($_POST['project_id'] ?? 0);
    $pTitle  = sanitizeInput($_POST['proj_title'] ?? '');
    $pDesc   = sanitizeInput($_POST['proj_description'] ?? '');
    $pTech   = sanitizeInput($_POST['proj_tech_stack'] ?? '');
    $pUrl    = sanitizeInput($_POST['proj_url'] ?? '');
    $pGit    = sanitizeInput($_POST['proj_github'] ?? '');
    $pOrder  = (int)($_POST['proj_order'] ?? 0);
    $pImage  = '';

    if ($projId > 0) {
        $si = $conn->prepare("SELECT image FROM projects WHERE id = ? AND user_id = ?");
        $si->bind_param("ii", $projId, $userId);
        $si->execute();
        $ri = $si->get_result()->fetch_assoc();
        $si->close();
        $pImage = $ri['image'] ?? '';
    }
    if (isset($_FILES['proj_image']) && $_FILES['proj_image']['error'] === 0) {
        $upDir = '../uploads/projects/';
        if (!file_exists($upDir)) { mkdir($upDir, 0755, true); }
        $ext = strtolower(pathinfo($_FILES['proj_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fn = $userId . '_proj_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['proj_image']['tmp_name'], $upDir . $fn))
                $pImage = 'uploads/projects/' . $fn;
        }
    }
    if (!empty($pTitle)) {
        if ($projId > 0) {
            $ps = $conn->prepare("UPDATE projects SET title=?,description=?,tech_stack=?,project_url=?,github_url=?,image=?,sort_order=? WHERE id=? AND user_id=?");
            $ps->bind_param("ssssssiii", $pTitle, $pDesc, $pTech, $pUrl, $pGit, $pImage, $pOrder, $projId, $userId);
        } else {
            $ps = $conn->prepare("INSERT INTO projects (user_id,title,description,tech_stack,project_url,github_url,image,sort_order) VALUES(?,?,?,?,?,?,?,?)");
            $ps->bind_param("issssssi", $userId, $pTitle, $pDesc, $pTech, $pUrl, $pGit, $pImage, $pOrder);
        }
        $ps->execute();
        $ps->close();
        $successMsg = $projId > 0 ? 'Project updated!' : 'Project added!';
    }
}

// ── Fetch all projects ────────────────────────────────────────────────────
$pq = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY sort_order ASC, id ASC");
$pq->bind_param("i", $userId);
$pq->execute();
$allProjects = $pq->get_result()->fetch_all(MYSQLI_ASSOC);
$pq->close();

// ── Auto-seed default projects if none exist ──────────────────────────────
if (empty($allProjects)) {
    $seeds = [
        ['Pestcozam', 'A web-based pest control and management system built for local community use. Features include user authentication, pest reporting, treatment tracking, and an admin dashboard for managing pest data across different barangay areas.', 'PHP, MySQL, HTML, CSS, JavaScript, XAMPP', 1],
        ['FocusMate', 'A mobile productivity application that helps students and professionals stay on track with their tasks and study sessions. Includes a Pomodoro-style timer, daily task manager, session history, and progress tracking.', 'Flutter, Dart, Firebase', 2],
    ];
    foreach ($seeds as $sd) {
        $t  = $conn->real_escape_string($sd[0]);
        $d  = $conn->real_escape_string($sd[1]);
        $tk = $conn->real_escape_string($sd[2]);
        $o  = (int)$sd[3];
        $chk = $conn->query("SELECT id FROM projects WHERE user_id=$userId AND title='$t' LIMIT 1");
        if ($chk->num_rows === 0) {
            $conn->query("INSERT INTO projects (user_id,title,description,tech_stack,sort_order) VALUES($userId,'$t','$d','$tk',$o)");
        }
    }
    $pq2 = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY sort_order ASC, id ASC");
    $pq2->bind_param("i", $userId);
    $pq2->execute();
    $allProjects = $pq2->get_result()->fetch_all(MYSQLI_ASSOC);
    $pq2->close();
}

// ── Which project is being edited? ────────────────────────────────────────
$editProject = null;
if (isset($_GET['edit_proj'])) {
    $epId = (int)$_GET['edit_proj'];
    foreach ($allProjects as $p) {
        if ((int)$p['id'] === $epId) { $editProject = $p; break; }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Portfolio - Portfolio CMS</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        textarea {
            overflow-y: auto;
            min-height: 100px;
            max-height: 500px;
            resize: vertical;
        }

        textarea.large-content {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        textarea.large-content::-webkit-scrollbar {
            width: 8px;
        }

        textarea.large-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        textarea.large-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        textarea.large-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        textarea:not(:empty) {
            min-height: 150px;
        }
    </style>
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
                <li>
                    <a href="dashboard.php"><i class="fa fa-dashboard"></i><span>Dashboard</span></a>
                </li>
                <li class="active">
                    <a href="edit_portfolio.php"><i class="fa fa-user-circle"></i><span>Edit Portfolio</span></a>
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
                <h1>Edit Portfolio</h1>
                <p>Update your portfolio information and content</p>
            </header>
            
            <?php if (!empty($successMsg)): ?>
                <div class="alert success">
                    <p><?php echo $successMsg; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMsg)): ?>
                <div class="alert error">
                    <p><?php echo $errorMsg; ?></p>
                </div>
            <?php endif; ?>
            
            <form action="edit_portfolio.php" method="post" enctype="multipart/form-data" class="portfolio-form" novalidate>
                <div class="form-tabs">
                    <button type="button" class="tab-btn active" data-tab="basic-info">Basic Info</button>
                    <button type="button" class="tab-btn" data-tab="about-me">About Me</button>
                    <button type="button" class="tab-btn" data-tab="resume-details">Resume</button>
                    <button type="button" class="tab-btn" data-tab="certifications">Certifications</button>
                    <button type="button" class="tab-btn" data-tab="contact-details">Contact</button>
                    <button type="button" class="tab-btn" data-tab="social-media">Social Media</button>
                    <button type="button" class="tab-btn" data-tab="projects">Projects</button>
                </div>
                
                <div class="tab-content active" id="basic-info">
                    <h3>Personal Information</h3>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($portfolio['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Professional Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($portfolio['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="short_bio">Short Bio (for homepage)</label>
                        <textarea id="short_bio" name="short_bio" rows="3"><?php echo htmlspecialchars($portfolio['short_bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <?php if (!empty($portfolio['profile_image'])): ?>
                            <div class="current-image">
                                <img src="../<?php echo htmlspecialchars($portfolio['profile_image']); ?>" alt="Current profile image" width="100">
                                <p>Current Image</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="profile_image" name="profile_image" accept="image/*">
                        <p class="form-help">Recommended size: 500x500 pixels. JPG, PNG, or GIF only.</p>
                    </div>
                </div>
                
                <div class="tab-content" id="about-me">
                    <h3>About Me</h3>
                    
                    <div class="form-group">
                        <label for="about_me">Detailed Bio</label>
                        <textarea id="about_me" name="about_me" rows="10" class="large-content"><?php echo $portfolio['about_me'] ?? ''; ?></textarea>
                        <p class="form-help">Write a comprehensive bio about yourself. You can use paragraphs to organize your text.</p>
                    </div>
                </div>
                
                <div class="tab-content" id="resume-details">
                    <h3>Resume Details</h3>
                    
                    <div class="form-group">
                        <label for="education">Education</label>
                        <textarea id="education" name="education" rows="6" class="large-content"><?php echo htmlspecialchars($portfolio['education'] ?? ''); ?></textarea>
                        <p class="form-help">List your education history. Use line breaks for multiple entries.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience">Professional Experience</label>
                        <textarea id="experience" name="experience" rows="8" class="large-content"><?php echo $portfolio['experience'] ?? ''; ?></textarea>
                        <p class="form-help">Detail your work history. Use line breaks for multiple entries.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="skills">Skills</label>
                        <textarea id="skills" name="skills" rows="6" class="large-content"><?php echo $portfolio['skills'] ?? ''; ?></textarea>
                        <p class="form-help">List your technical and soft skills.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="cv_file">Upload CV/Resume</label>
                        <?php if (!empty($portfolio['cv_link'])): ?>
                            <div class="current-file">
                                <p>Current CV: <a href="../<?php echo htmlspecialchars($portfolio['cv_link']); ?>" target="_blank">View</a></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="cv_file" name="cv_file" accept=".pdf,.doc,.docx">
                        <p class="form-help">Upload your CV/Resume in PDF or Word format.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="cv_link">External CV Link (optional)</label>
                        <input type="url" id="cv_link" name="cv_link" value="<?php echo htmlspecialchars($portfolio['cv_link'] ?? ''); ?>">
                        <p class="form-help">If you have your CV hosted elsewhere, enter the URL here.</p>
                    </div>
                </div>
                
                <div class="tab-content" id="certifications">
                    <h3>Certifications & Achievements</h3>
                    
                    <div class="form-info-box">
                        <p>Upload images of your certificates as proof of your achievements.</p>
                        <p>Supported formats: JPG, PNG, GIF, PDF</p>
                    </div>
                    
                    <!-- Add hidden input to track deleted certificates -->
                    <input type="hidden" name="deleted_cert_ids" id="deleted_cert_ids" value="">
                    
                    <div id="certificates-container">
                        <?php
                        $certificatesArray = [];
                        if (!empty($portfolio['certifications'])) {
                            if (isJson($portfolio['certifications'])) {
                                $certificatesArray = json_decode($portfolio['certifications'], true);
                            } else {
                                $certificatesArray = [
                                    ['title' => 'Legacy Certifications', 
                                     'description' => $portfolio['certifications'], 
                                     'date' => '', 
                                     'image' => '']
                                ];
                            }
                        }
                        
                        if (!empty($certificatesArray)):
                            foreach ($certificatesArray as $index => $cert):
                        ?>
                        <div class="certificate-item" data-cert-id="<?php echo $index; ?>">
                            <div class="form-group">
                                <label>Certificate Title</label>
                                <input type="hidden" name="existing_cert_ids[]" value="<?php echo $index; ?>">
                                <input type="text" name="existing_cert_titles[]" value="<?php echo htmlspecialchars($cert['title'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="existing_cert_descriptions[]" rows="2"><?php echo htmlspecialchars($cert['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Issue Date</label>
                                <input type="text" name="existing_cert_dates[]" value="<?php echo htmlspecialchars($cert['date'] ?? ''); ?>" placeholder="e.g., June 2023">
                            </div>
                            
                            <?php if (!empty($cert['image'])): ?>
                            <div class="form-group">
                                <label>Certificate Image</label>
                                <div class="certificate-image-preview">
                                    <a href="../<?php echo htmlspecialchars($cert['image']); ?>" target="_blank">
                                        <?php if (pathinfo($cert['image'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                            <i class="fa fa-file-pdf-o fa-3x"></i>
                                        <?php else: ?>
                                            <img src="../<?php echo htmlspecialchars($cert['image']); ?>" alt="Certificate" width="150">
                                        <?php endif; ?>
                                    </a>
                                    <input type="hidden" name="existing_cert_images[]" value="<?php echo htmlspecialchars($cert['image']); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-small btn-danger remove-certificate" data-index="<?php echo $index; ?>">Remove</button>
                            <hr>
                        </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" id="add-certificate" class="btn btn-secondary">
                            <i class="fa fa-plus"></i> Add New Certificate
                        </button>
                    </div>
                    
                    <template id="certificate-template">
                        <div class="certificate-item new-certificate">
                            <div class="form-group">
                                <label>Certificate Title</label>
                                <input type="text" name="certificate_titles[]" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="certificate_descriptions[]" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Issue Date</label>
                                <input type="text" name="certificate_dates[]" placeholder="e.g., June 2023">
                            </div>
                            
                            <div class="form-group">
                                <label>Upload Certificate Image</label>
                                <input type="file" name="certificate_images[]" accept="image/jpeg,image/png,image/gif,application/pdf">
                                <p class="form-help">Upload proof of your certificate (image or PDF)</p>
                            </div>
                            
                            <button type="button" class="btn btn-small btn-danger remove-certificate">Remove</button>
                            <hr>
                        </div>
                    </template>
                </div>
                
                <div class="tab-content" id="contact-details">
                    <h3>Contact Information</h3>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($portfolio['email'] ?? $userData['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($portfolio['phone'] ?? $userData['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($portfolio['location'] ?? ''); ?>">
                        <p class="form-help">Example: City, Country</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_section">Custom Contact Section</label>
                        <textarea id="contact_section" name="contact_section" rows="6" class="large-content"><?php echo $portfolio['contact_section'] ?? ''; ?></textarea>
                        <p class="form-help">Add additional contact information or instructions for visitors.</p>
                    </div>
                </div>
                
                <div class="tab-content" id="social-media">
                    <h3>Social Media Links</h3>
                    
                    <div class="form-group">
                        <label for="linkedin">LinkedIn Profile</label>
                        <input type="url" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($portfolio['linkedin'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="github">GitHub Profile</label>
                        <input type="url" id="github" name="github" value="<?php echo htmlspecialchars($portfolio['github'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-actions" id="portfolioFormActions">
                    <button type="submit" name="save_portfolio" class="btn btn-primary">Save Portfolio</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <!-- ═══ PROJECTS TAB CONTENT (outside portfolio form) ═══ -->
            <div class="tab-content" id="projects" style="padding:0;">
                <?php if ($successMsg && (isset($_POST['save_project']) || isset($_POST['delete_project']))): ?>
                <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-left:4px solid #10b981;margin-bottom:20px;font-weight:600;">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($successMsg); ?>
                </div>
                <?php endif; ?>

                <!-- Add / Edit form -->
                <div style="background:white;border:1px solid #e5e7eb;border-top:4px solid #1d4ed8;padding:28px;margin-bottom:24px;">
                    <h3 style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:22px;">
                        <?php echo $editProject ? '<i class="fa fa-pencil"></i> Edit Project' : '<i class="fa fa-plus"></i> Add New Project'; ?>
                    </h3>
                    <form method="POST" enctype="multipart/form-data" action="edit_portfolio.php<?php echo $editProject ? '?edit_proj='.(int)$editProject['id'] : ''; ?>">
                        <input type="hidden" name="save_project" value="1">
                        <input type="hidden" name="project_id" value="<?php echo $editProject ? (int)$editProject['id'] : 0; ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                            <div class="form-group">
                                <label>Project Title *</label>
                                <input type="text" name="proj_title" value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>" required placeholder="e.g. Pestcozam">
                            </div>
                            <div class="form-group">
                                <label>Tech Stack <span style="font-weight:400;color:#888;">(comma-separated)</span></label>
                                <input type="text" name="proj_tech_stack" value="<?php echo htmlspecialchars($editProject['tech_stack'] ?? ''); ?>" placeholder="PHP, MySQL, Flutter">
                            </div>
                            <div class="form-group" style="grid-column:1/-1;">
                                <label>Description</label>
                                <textarea name="proj_description" rows="4" placeholder="What does this project do?"><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>URL <span style="font-weight:400;color:#888;">(live site / demo)</span></label>
                                <input type="url" name="proj_url" value="<?php echo htmlspecialchars($editProject['project_url'] ?? ''); ?>" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label>GitHub URL</label>
                                <input type="url" name="proj_github" value="<?php echo htmlspecialchars($editProject['github_url'] ?? ''); ?>" placeholder="https://github.com/...">
                            </div>
                            <div class="form-group">
                                <label>Project Screenshot</label>
                                <input type="file" name="proj_image" accept="image/*">
                                <?php if (!empty($editProject['image'])): ?>
                                <div style="margin-top:8px;"><img src="../<?php echo htmlspecialchars($editProject['image']); ?>" style="height:60px;border:1px solid #e5e7eb;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Sort Order <span style="font-weight:400;color:#888;">(1 = first)</span></label>
                                <input type="number" name="proj_order" value="<?php echo (int)($editProject['sort_order'] ?? count($allProjects) + 1); ?>" min="1">
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo $editProject ? 'Update Project' : 'Save Project'; ?>
                            </button>
                            <?php if ($editProject): ?>
                            <a href="edit_portfolio.php#projects" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Projects list -->
                <?php if (!empty($allProjects)): ?>
                <div style="background:white;border:1px solid #e5e7eb;border-top:4px solid #1d4ed8;padding:28px;">
                    <h3 style="font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;margin-bottom:18px;">
                        <i class="fa fa-list"></i> Your Projects (<?php echo count($allProjects); ?>)
                    </h3>
                    <?php foreach ($allProjects as $i => $proj): ?>
                    <div style="border:1px solid #e5e7eb;border-left:4px solid #1d4ed8;padding:16px 20px;margin-bottom:14px;display:flex;align-items:flex-start;gap:18px;flex-wrap:wrap;">
                        <?php if (!empty($proj['image'])): ?>
                        <img src="../<?php echo htmlspecialchars($proj['image']); ?>" style="width:80px;height:56px;object-fit:cover;border:1px solid #e5e7eb;flex-shrink:0;">
                        <?php endif; ?>
                        <div style="flex:1;min-width:200px;">
                            <strong style="font-size:1rem;display:block;margin-bottom:4px;"><?php echo htmlspecialchars($proj['title']); ?></strong>
                            <div style="font-size:0.82rem;color:#6b7280;margin-bottom:6px;"><?php echo htmlspecialchars(substr($proj['description'] ?? '', 0, 120)) . (strlen($proj['description'] ?? '') > 120 ? '...' : ''); ?></div>
                            <div style="font-size:0.78rem;color:#1d4ed8;font-weight:700;"><?php echo htmlspecialchars($proj['tech_stack'] ?? ''); ?></div>
                        </div>
                        <div style="display:flex;gap:8px;flex-shrink:0;">
                            <a href="edit_portfolio.php?edit_proj=<?php echo (int)$proj['id']; ?>#projects" class="btn btn-small btn-secondary" style="padding:6px 14px;font-size:0.8rem;"><i class="fa fa-pencil"></i> Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this project?');" style="display:inline;">
                                <input type="hidden" name="delete_project" value="1">
                                <input type="hidden" name="project_id" value="<?php echo (int)$proj['id']; ?>">
                                <button type="submit" style="background:#ef4444;color:white;border:none;padding:6px 14px;cursor:pointer;font-size:0.8rem;font-weight:700;text-transform:uppercase;"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const formActions = document.getElementById('portfolioFormActions');

            function activateTab(tabId) {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                const btn = document.querySelector('.tab-btn[data-tab="' + tabId + '"]');
                const content = document.getElementById(tabId);
                if (btn) btn.classList.add('active');
                if (content) content.classList.add('active');
                if (formActions) formActions.style.display = tabId === 'projects' ? 'none' : '';
            }
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    activateTab(button.getAttribute('data-tab'));
                });
            });

            // If URL has #projects, switch to that tab
            if (window.location.hash === '#projects') {
                activateTab('projects');
            }
            
            const certificatesContainer = document.getElementById('certificates-container');
            const certificateTemplate = document.getElementById('certificate-template');
            const addCertificateBtn = document.getElementById('add-certificate');
            const deletedCertIds = document.getElementById('deleted_cert_ids');
            let deletedIds = [];
            
            addCertificateBtn.addEventListener('click', function() {
                const clone = document.importNode(certificateTemplate.content, true);
                certificatesContainer.appendChild(clone);
                
                const newItem = certificatesContainer.lastElementChild;
                const removeBtn = newItem.querySelector('.remove-certificate');
                removeBtn.addEventListener('click', function() {
                    newItem.remove();
                });
            });
            
            document.querySelectorAll('.remove-certificate').forEach(button => {
                button.addEventListener('click', function() {
                    const certId = this.getAttribute('data-index');
                    if (certId) {
                        deletedIds.push(certId);
                        deletedCertIds.value = deletedIds.join(',');
                    }
                    this.closest('.certificate-item').remove();
                });
            });
        });
    </script>
</body>
</html>
