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

$contactSuccess = '';
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $contactError = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactError = 'Please enter a valid email address.';
    } else {
        $contactStmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $contactStmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($contactStmt->execute()) {
            $contactSuccess = 'Your message has been sent. We will contact you soon!';
            
            if ($isLoggedIn) {
                logActivity($conn, $userId, 'Sent a contact message');
            }
        } else {
            $contactError = 'Failed to send your message. Please try again later.';
        }
        
        $contactStmt->close();
    }
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
    <script src="js/validation.js" defer></script>
    <script src="js/session-timeout.js" defer></script>
</head>
<body <?php echo $isLoggedIn ? 'data-logged-in="true"' : ''; ?>>
    <label class="burger" for="burger">
        <input type="checkbox" id="burger">
        <span></span>
        <span></span>
        <span></span>
    </label>
    
    <div class="overlay"></div>
    
    <nav class="sidebar-nav">
        <div class="nav-main-items">
            <a href="#about" class="nav-item nav-icon">
                <i class="fa fa-user-circle"></i>
                <span>About</span>
            </a>
            <a href="#resume" class="nav-item">
                <i class="fa fa-file-text"></i>
                <span>Resume</span>
            </a>
            <?php if (!empty($portfolio['certifications'])): ?>
            <a href="#certifications" class="nav-item">
                <i class="fa fa-certificate"></i>
                <span>Certifications</span>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="nav-footer">
            <?php if ($isLoggedIn): ?>
                <a href="admin/dashboard.php" class="nav-item">
                    <i class="fa fa-tachometer"></i>
                    <span>Dashboard</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fa fa-sign-out"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-item">
                    <i class="fa fa-sign-in"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <section id="profile" class="container">
        <div class="profile-content">
            <div class="profile-image">
                <img src="<?php echo htmlspecialchars($portfolio['profile_image'] ?? 'images/default-profile.jpg'); ?>" alt="Profile Picture">
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($portfolio['name'] ?? 'My Name'); ?></h1>
            <h2 class="profile-title"><?php echo htmlspecialchars($portfolio['title'] ?? 'Professional Title'); ?></h2>
            <p class="profile-description"><?php echo htmlspecialchars($portfolio['short_bio'] ?? 'A short description about me.'); ?></p>
        </div>
    </section>

    <div class="section-separator"></div>

    <?php if (!empty($portfolio)): ?>
    <section id="main-content" class="container section">
        <div class="two-column-layout">
            <div class="column" id="about">
                <h2>About Me</h2>
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
                </div>
            </div>
            
            <div class="column" id="resume">
                <h2>Resume</h2>
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
        document.addEventListener('DOMContentLoaded', function() {
            const burger = document.getElementById('burger');
            const sidebar = document.querySelector('.sidebar-nav');
            const overlay = document.querySelector('.overlay');
            const body = document.body;
            
            burger.addEventListener('change', function() {
                if (this.checked) {
                    sidebar.classList.add('active');
                    overlay.classList.add('active');
                    body.style.overflow = 'hidden';
                } else {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    body.style.overflow = '';
                }
            });
            
            overlay.addEventListener('click', function() {
                burger.checked = false;
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.style.overflow = '';
            });
            
            const navLinks = document.querySelectorAll('.sidebar-nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    burger.checked = false;
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    body.style.overflow = '';
                });
            });
            
            const aboutSection = document.getElementById('about');
            const resumeSection = document.getElementById('resume');
            const aboutContent = document.getElementById('about-content');
            
            if (aboutSection && resumeSection) {
                document.addEventListener('DOMContentLoaded', function() {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.target.id === 'about-content' && 
                                mutation.attributeName === 'style' &&
                                aboutContent.style.display !== 'none') {
                                const loader = document.getElementById('about-loader');
                                if (loader) {
                                    loader.style.display = 'none';
                                    loader.style.height = '0';
                                    loader.style.margin = '0';
                                }
                            }
                        });
                    });
                    
                    if (aboutContent) {
                        observer.observe(aboutContent, { attributes: true });
                    }
                });
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            function showContent(contentId, loaderId) {
                setTimeout(function() {
                    const contentElement = document.getElementById(contentId);
                    const loaderElement = document.getElementById(loaderId);
                    
                    if (contentElement && loaderElement) {
                        loaderElement.style.display = 'none';
                        contentElement.style.display = 'block';
                    }
                }, Math.random() * 500 + 300);
            }
            
            showContent('about-content', 'about-loader');
            showContent('resume-content', 'resume-loader');
            showContent('certifications-content', 'certifications-loader');
        });
        
        <?php if ($isLoggedIn): ?>
        const inactivityTime = function() {
            let time;
            const resetTimer = function() {
                clearTimeout(time);
                time = setTimeout(logout, 30 * 60 * 1000);
            };

            const logout = function() {
                window.location.href = "logout.php?timeout=1";
            };

            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onload = resetTimer;
            document.onmousedown = resetTimer;
            document.ontouchstart = resetTimer;
            document.onclick = resetTimer;
            document.onscroll = resetTimer;

            resetTimer();
        };

        inactivityTime();
        <?php endif; ?>
    </script>
</body>
</html>
