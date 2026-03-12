<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']);

$userData = null;
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $userStmt = $conn->prepare("SELECT username, first_name, last_name, email FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    
    $_SESSION['last_activity'] = time();
}

$portfolioStmt = null;
if ($isLoggedIn) {
    $portfolioStmt = $conn->prepare("SELECT * FROM portfolio WHERE user_id = ? LIMIT 1");
    $portfolioStmt->bind_param("i", $userId);
    $portfolioStmt->execute();
    $result = $portfolioStmt->get_result();
    if ($result->num_rows === 0) {
        $portfolioStmt->close();
        $portfolioStmt = $conn->prepare("SELECT * FROM portfolio LIMIT 1");
        $portfolioStmt->execute();
        $result = $portfolioStmt->get_result();
    }
} else {
    $portfolioStmt = $conn->prepare("SELECT * FROM portfolio LIMIT 1");
    $portfolioStmt->execute();
    $result = $portfolioStmt->get_result();
}

$portfolio = [];
if ($result && $result->num_rows > 0) {
    $portfolio = $result->fetch_assoc();
}
$portfolioStmt->close();

// Fetch projects
$projects = [];
$portfolioOwnerId = !empty($portfolio['user_id']) ? (int)$portfolio['user_id'] : 0;

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

if ($portfolioOwnerId > 0) {
    $projectsStmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY sort_order ASC, id ASC");
    $projectsStmt->bind_param("i", $portfolioOwnerId);
    $projectsStmt->execute();
    $projectsResult = $projectsStmt->get_result();
    while ($row = $projectsResult->fetch_assoc()) {
        $projects[] = $row;
    }
    $projectsStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portfolio['name'] ?? 'My Portfolio'); ?></title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/session-timeout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <script src="js/validation.js" defer></script>
    <script src="js/session-timeout.js" defer></script>
</head>
<body <?php echo $isLoggedIn ? 'data-logged-in="true"' : ''; ?> style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <!-- Page loader -->
    <div id="page-loader" style="position:fixed;inset:0;background:#0d1117;z-index:9999;display:flex;align-items:center;justify-content:center;transition:opacity 0.5s ease;">
        <div style="text-align:center;">
            <div style="width:50px;height:50px;border:3px solid rgba(74,159,233,0.2);border-top-color:#4a9fe9;border-radius:50%;animation:rotateLine 0.8s linear infinite;margin:0 auto 16px;"></div>
            <p style="color:rgba(255,255,255,0.5);font-size:0.85rem;letter-spacing:2px;text-transform:uppercase;">Loading</p>
        </div>
    </div>
    <!-- Geometric Top Navigation -->
    <nav class="top-nav" id="topNav">
        <a href="#" class="top-nav-logo">
            <span class="logo-geo">//</span>&nbsp;<?php echo htmlspecialchars($portfolio['name'] ?? 'Portfolio'); ?>
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
        <div class="top-nav-links" id="topNavLinks">
            <a href="#about">About</a>
            <a href="#resume">Resume</a>
            <a href="#projects">Projects</a>
            <?php if (!empty($portfolio['certifications'])): ?>
            <a href="#certifications">Certs</a>
            <?php endif; ?>
            <div class="nav-divider-v"></div>
            <?php if ($isLoggedIn): ?>
            <a href="admin/dashboard.php" style="display:inline-flex;align-items:center;gap:6px;"><i class="fa fa-th-large"></i> Dashboard</a>
            <a href="logout.php" class="nav-cta">Logout</a>
            <?php else: ?>
            <a href="login.php" class="nav-cta">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="overlay" id="mobileNavOverlay"></div>

    <section id="profile">
        <div class="profile-split">
            <!-- Left: Text Content -->
            <div class="profile-text-side">
                <div class="profile-eyebrow">
                    <span class="eyebrow-line"></span>
                    Available for opportunities
                </div>
                <h1 class="profile-name"><?php echo htmlspecialchars($portfolio['name'] ?? 'My Name'); ?></h1>
                <h2 class="profile-title"><?php echo htmlspecialchars($portfolio['title'] ?? 'Professional Title'); ?></h2>
                <p class="profile-description"><?php echo htmlspecialchars($portfolio['short_bio'] ?? 'A short description about me.'); ?></p>
                <div class="profile-actions">
                    <a href="#about" class="btn"><i class="fa fa-arrow-down"></i>&nbsp;Explore</a>
                    <?php if (!empty($portfolio['cv_link'])): ?>
                    <a href="<?php echo htmlspecialchars($portfolio['cv_link']); ?>" class="btn btn-outline" download><i class="fa fa-download"></i>&nbsp;CV</a>
                    <?php endif; ?>
                    <?php if (!empty($portfolio['github'])): ?>
                    <a href="<?php echo htmlspecialchars($portfolio['github']); ?>" class="btn btn-outline" target="_blank" rel="noopener noreferrer"><i class="fa fa-github"></i>&nbsp;GitHub</a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Right: Geometric Image Frame -->
            <div class="profile-image-side">
                <div class="profile-image-frame">
                    <div class="profile-image">
                        <img src="<?php echo htmlspecialchars($portfolio['profile_image'] ?? 'images/default-profile.jpg'); ?>" alt="Profile Picture">
                    </div>
                    <div class="frame-corner frame-tl"></div>
                    <div class="frame-corner frame-tr"></div>
                    <div class="frame-corner frame-bl"></div>
                    <div class="frame-corner frame-br"></div>
                </div>
            </div>
        </div>
        <div class="scroll-indicator">
            <span>Scroll</span>
            <span class="scroll-arrow"></span>
        </div>
    </section>

    <div class="section-separator"></div>

    <?php if (!empty($portfolio)): ?>
    <section id="main-content" class="container section">
        <div class="two-column-layout">
            <div class="column reveal-left" id="about">
                <span class="section-badge"><i class="fa fa-user"></i> &nbsp;About</span>
                <h2 style="text-align:left;">About Me</h2>
                <div id="about-loader" class="content-loader">
                    <div class="loader">
                        <div class="wrapper">
                            <div class="circle"></div>
                            <div class="line-1"></div>
                            <div class="line-2"></div>
                            <div class="line-3"></div>
                            <div class="line-4"></div>
                        </div>
                    </div>
                </div>
                <div id="about-content" class="about-content" style="display: none;">
                    <div class="about-text">
                        <?php echo nl2br(htmlspecialchars($portfolio['about_me'] ?? $portfolio['bio'] ?? 'Biography information will appear here.')); ?>
                    </div>
                    <?php if (!empty($portfolio['skills'])): ?>
                    <div style="margin-top:28px;">
                        <h3 style="font-size:1rem;color:#555;font-weight:600;margin-bottom:14px;text-transform:uppercase;letter-spacing:1px;"><i class="fa fa-cogs" style="color:var(--primary-color);margin-right:7px;"></i>Skills &amp; Technologies</h3>
                        <?php
                        $skillsRaw = $portfolio['skills'];
                        $skillsArr = json_decode($skillsRaw, true);
                        if (!is_array($skillsArr)) {
                            $skillsArr = preg_split('/\n/', $skillsRaw);
                        }
                        // Group into categories: lines ending with ":" start a new group
                        $groups = [];
                        $currentGroup = 'Skills';
                        foreach ($skillsArr as $line) {
                            $line = trim($line);
                            if ($line === '') continue;
                            // Detect a category header (ends with colon, no commas, ≤60 chars)
                            if (substr($line, -1) === ':' && strpos($line, ',') === false && strlen($line) <= 60) {
                                $currentGroup = rtrim($line, ':');
                            } else {
                                // May be a comma-separated list within one line
                                $items = preg_split('/,/', $line);
                                foreach ($items as $item) {
                                    $item = trim(ltrim($item, '-'));
                                    if ($item !== '') {
                                        $groups[$currentGroup][] = $item;
                                    }
                                }
                            }
                        }
                    ?>
                    <?php foreach ($groups as $groupName => $items): ?>
                    <div class="skill-group">
                        <div class="skill-group-label"><?php echo htmlspecialchars($groupName); ?></div>
                        <div class="skills-tags">
                            <?php foreach ($items as $skill): ?>
                            <span class="skill-tag"><i class="fa fa-check-circle"></i><?php echo htmlspecialchars(trim($skill)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="column reveal-right" id="resume">
                <span class="section-badge"><i class="fa fa-file-text"></i> &nbsp;Resume</span>
                <h2 style="text-align:left;">My Resume</h2>
                <div id="resume-loader" class="content-loader">
                    <div class="loader">
                        <div class="wrapper">
                            <div class="circle"></div>
                            <div class="line-1"></div>
                            <div class="line-2"></div>
                            <div class="line-3"></div>
                            <div class="line-4"></div>
                        </div>
                    </div>
                </div>
                <div id="resume-content" class="resume-content" style="display: none;">
                    <div class="resume-box">
                        <div class="resume-section">
                            <h3><i class="fa fa-graduation-cap"></i> Education</h3>
                            <div class="resume-section-content">
                                <?php echo nl2br(htmlspecialchars($portfolio['education'] ?? 'Education information will appear here.')); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="resume-box">
                        <div class="resume-section">
                            <h3><i class="fa fa-briefcase"></i> Experience</h3>
                            <div class="resume-section-content">
                                <?php echo nl2br(htmlspecialchars($portfolio['experience'] ?? 'Experience information will appear here.')); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="resume-box">
                        <div class="resume-section">
                            <h3><i class="fa fa-cogs"></i> Skills</h3>
                            <div class="resume-section-content">
                                <?php echo nl2br(htmlspecialchars($portfolio['skills'] ?? 'Skills information will appear here.')); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($portfolio['cv_link'])): ?>
                    <a href="<?php echo htmlspecialchars($portfolio['cv_link']); ?>" class="btn download-cv" download><i class="fa fa-download"></i> Download CV</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-separator"></div>
        
        <?php if (!empty($portfolio['certifications'])): ?>
        <div id="certifications">
            <h2><i class="fa fa-certificate"></i> Certifications</h2>
            <div id="certifications-loader" class="content-loader">
                <div class="loader">
                    <div class="wrapper">
                        <div class="circle"></div>
                        <div class="line-1"></div>
                        <div class="line-2"></div>
                        <div class="line-3"></div>
                        <div class="line-4"></div>
                    </div>
                </div>
            </div>
            <div id="certifications-content" class="certifications-content" style="display: none;">
                <?php 
                $isJson = function_exists('isJson') ? isJson($portfolio['certifications']) : 
                         (json_decode($portfolio['certifications']) !== null);
                         
                if ($isJson) {
                    $certs = json_decode($portfolio['certifications'], true);
                    if (!empty($certs)) {
                        echo '<div class="certificates-grid">';
                        foreach ($certs as $cert) {
                            echo '<div class="certificate-card">';
                            
                            // Display certificate image if available
                            if (!empty($cert['image'])) {
                                if (pathinfo($cert['image'], PATHINFO_EXTENSION) === 'pdf') {
                                    echo '<div class="certificate-image pdf-certificate">';
                                    echo '<a href="' . htmlspecialchars($cert['image']) . '" target="_blank">';
                                    echo '<i class="fa fa-file-pdf-o"></i>';
                                    echo '<span>View PDF</span>';
                                    echo '</a>';
                                    echo '</div>';
                                } else {
                                    echo '<div class="certificate-image">';
                                    echo '<a href="' . htmlspecialchars($cert['image']) . '" target="_blank">';
                                    echo '<img src="' . htmlspecialchars($cert['image']) . '" alt="Certificate">';
                                    echo '</a>';
                                    echo '</div>';
                                }
                            }
                            
                            echo '<div class="certificate-details">';
                            echo '<h3>' . htmlspecialchars($cert['title']) . '</h3>';
                            
                            if (!empty($cert['date'])) {
                                echo '<div class="certificate-date">';
                                echo '<i class="fa fa-calendar"></i> ' . htmlspecialchars($cert['date']);
                                echo '</div>';
                            }
                            
                            if (!empty($cert['description'])) {
                                echo '<p>' . nl2br(htmlspecialchars($cert['description'])) . '</p>';
                            }
                            
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo nl2br(htmlspecialchars($portfolio['certifications']));
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- ===== PROJECTS SECTION ===== -->
    <section id="projects" style="background:#09090b;padding:72px 0;border-top:2px solid #09090b;">
        <div class="container">
            <div style="margin-bottom:50px;" class="reveal">
                <span class="section-badge" style="background:#1d4ed8;"><i class="fa fa-code"></i> &nbsp;Projects</span>
                <h2 style="color:#ffffff;margin-top:10px;">My Projects</h2>
            </div>
            <?php if (!empty($projects)): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $i => $proj): ?>
                <div class="project-card reveal" style="transition-delay:<?php echo $i * 80; ?>ms;">
                    <?php if (!empty($proj['image'])): ?>
                    <div class="project-card-img">
                        <img src="<?php echo htmlspecialchars($proj['image']); ?>" alt="<?php echo htmlspecialchars($proj['title']); ?>">
                    </div>
                    <?php endif; ?>
                    <div class="project-card-body">
                        <h3 class="project-title"><?php echo htmlspecialchars($proj['title']); ?></h3>
                        <?php if (!empty($proj['description'])): ?>
                        <p class="project-desc"><?php echo nl2br(htmlspecialchars($proj['description'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($proj['tech_stack'])): ?>
                        <div class="project-tech">
                            <?php foreach (preg_split('/[,]+/', $proj['tech_stack']) as $tech): ?>
                                <?php $tech = trim($tech); if ($tech): ?>
                                <span class="tech-badge"><?php echo htmlspecialchars($tech); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <div class="project-links">
                            <?php if (!empty($proj['project_url'])): ?>
                            <a href="<?php echo htmlspecialchars($proj['project_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn" style="font-size:0.72rem;padding:8px 16px;"><i class="fa fa-external-link"></i> Visit Site</a>
                            <?php endif; ?>
                            <?php if (!empty($proj['github_url'])): ?>
                            <a href="<?php echo htmlspecialchars($proj['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" style="font-size:0.72rem;padding:8px 16px;"><i class="fa fa-github"></i> GitHub</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="project-empty reveal">
                <i class="fa fa-code" style="font-size:3rem;color:rgba(255,255,255,0.15);display:block;margin-bottom:16px;"></i>
                <p style="color:rgba(255,255,255,0.35);font-size:0.9rem;letter-spacing:1px;text-transform:uppercase;">No projects added yet &mdash; add them from the admin dashboard.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <?php if (!empty($portfolio['email'])): ?>
                    <div class="contact-info-item">
                        <i class="fa fa-envelope"></i>
                        <span><?php echo htmlspecialchars($portfolio['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($portfolio['phone'])): ?>
                    <div class="contact-info-item">
                        <i class="fa fa-phone"></i>
                        <span><?php echo htmlspecialchars($portfolio['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($portfolio['location'])): ?>
                    <div class="contact-info-item">
                        <i class="fa fa-map-marker"></i>
                        <span><?php echo htmlspecialchars($portfolio['location']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="footer-section">
                    <h3>Connect With Me</h3>
                    <div class="footer-social-links">
                        <?php if (!empty($portfolio['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($portfolio['linkedin']); ?>" target="_blank"><i class="fa fa-linkedin"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['github'])): ?>
                            <a href="<?php echo htmlspecialchars($portfolio['github']); ?>" target="_blank"><i class="fa fa-github"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($portfolio['twitter']); ?>" target="_blank"><i class="fa fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($portfolio['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($portfolio['instagram']); ?>" target="_blank"><i class="fa fa-instagram"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($portfolio['name'] ?? 'My Portfolio'); ?>. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        /* ---- Page Loader ---- */
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.opacity = '0';
                loader.style.pointerEvents = 'none';
                setTimeout(() => loader.remove(), 600);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {

            /* ---- Geometric Top Nav — mobile toggle ---- */
            const toggle    = document.getElementById('navToggle');
            const navLinks  = document.getElementById('topNavLinks');
            const overlay   = document.getElementById('mobileNavOverlay');
            const topNav    = document.getElementById('topNav');
            const body      = document.body;

            function openNav() {
                navLinks.classList.add('open');
                overlay.classList.add('active');
                toggle.classList.add('open');
                body.style.overflow = 'hidden';
            }
            function closeNav() {
                navLinks.classList.remove('open');
                overlay.classList.remove('active');
                toggle.classList.remove('open');
                body.style.overflow = '';
            }

            if (toggle) toggle.addEventListener('click', () => navLinks.classList.contains('open') ? closeNav() : openNav());
            if (overlay) overlay.addEventListener('click', closeNav);
            document.querySelectorAll('.top-nav-links a').forEach(a => a.addEventListener('click', closeNav));

            /* ---- Scroll: shrink nav + highlight active link ---- */
            const anchors = document.querySelectorAll('.top-nav-links a[href^="#"]');

            window.addEventListener('scroll', () => {
                topNav && (window.scrollY > 80 ? topNav.classList.add('scrolled') : topNav.classList.remove('scrolled'));

                let current = '';
                document.querySelectorAll('section[id], div[id]').forEach(s => {
                    if (window.scrollY >= s.offsetTop - 130) current = s.id;
                });
                anchors.forEach(a => {
                    a.classList.remove('active');
                    if (a.getAttribute('href') === '#' + current) a.classList.add('active');
                });
            });

            /* ---- Scroll Reveal ---- */
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, i) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => entry.target.classList.add('visible'), i * 80);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => observer.observe(el));

            /* ---- Content loaders ---- */
            function showContent(contentId, loaderId) {
                setTimeout(function() {
                    const el  = document.getElementById(contentId);
                    const ldr = document.getElementById(loaderId);
                    if (el && ldr) {
                        ldr.style.display = 'none';
                        el.style.display = 'block';
                        el.style.animation = 'fadeInUp 0.5s ease both';
                    }
                }, Math.random() * 300 + 150);
            }
            showContent('about-content', 'about-loader');
            showContent('resume-content', 'resume-loader');
            showContent('certifications-content', 'certifications-loader');
        });

        <?php if ($isLoggedIn): ?>
        (function() {
            let t;
            const reset = () => { clearTimeout(t); t = setTimeout(() => { window.location.href = 'logout.php?timeout=1'; }, 30 * 60 * 1000); };
            ['mousemove','keypress','mousedown','touchstart','click','scroll'].forEach(e => document.addEventListener(e, reset));
            reset();
        })();
        <?php endif; ?>
    </script>
</body>
</html>
