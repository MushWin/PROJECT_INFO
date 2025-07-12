<?php
session_start();
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$timeout_message = '';
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $timeout_message = 'Your session has expired due to inactivity. Please login again.';
}

$register_success = '';
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $register_success = 'Registration successful! You can now log in with your credentials.';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db_connection.php';
    
    $username_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $username_email = htmlspecialchars($username_email, ENT_QUOTES, 'UTF-8');
    
    if (empty($username_email) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else if (strlen($username_email) > 100 || strlen($password) > 100) {
        $error = 'Username/email or password is too long.';
    } else {
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }
        
        if (!$table_exists) {
            $error = 'The database is not set up properly.';
        } else {
            $is_email = filter_var($username_email, FILTER_VALIDATE_EMAIL);
            
            if ($is_email) {
                $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
            } else {
                $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
            }
            
            $stmt->bind_param("s", $username_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();
                    
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    logActivity($conn, $user['id'], 'Login successful');
                    
                    header('Location: index.php');
                    exit;
                } else {
                    sleep(1);
                    
                    logActivity($conn, null, 'Failed login attempt for: ' . $username_email);
                    $error = 'Invalid username/email or password.';
                }
            } else {
                sleep(1);
                
                logActivity($conn, null, 'Failed login attempt for: ' . $username_email);
                $error = 'Invalid username/email or password.';
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Portfolio</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="js/validation.js" defer></script>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; font-src cdnjs.cloudflare.com">
</head>
<body>
    <label class="burger" for="burger">
        <input type="checkbox" id="burger">
        <span></span>
        <span></span>
        <span></span>
    </label>
    
    <div class="overlay"></div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item">
            <i class="fa fa-home"></i>
            <span>Home</span>
        </a>
        <div class="nav-separator" style="border-top: 1px solid rgba(255,255,255,0.2); margin: 10px 0;"></div>
        <a href="login.php" class="nav-item active">
            <i class="fa fa-sign-in"></i>
            <span>Login</span>
        </a>
    </nav>

    <section id="profile" class="container">
        <div class="profile-content">
            <div class="profile-image">
                <i class="fa fa-user-circle-o" style="font-size: 80px; color: #4a9fe9;"></i>
            </div>
            <h1 class="profile-name">Welcome Back</h1>
            <p class="profile-description">Please login to access your portfolio dashboard</p>
        </div>
    </section>

    <div class="section-separator"></div>

    <section class="container section">
        <div class="login-container">
            <div class="login-box column">
                <h2><i class="fa fa-sign-in"></i> Login</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($timeout_message)): ?>
                    <div class="info-message">
                        <i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($timeout_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($register_success)): ?>
                    <div class="success-message">
                        <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($register_success); ?>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" method="post" action="login.php" novalidate>
                    <div class="form-group">
                        <label for="username_email">
                            <i class="fa fa-user"></i> Username or Email
                        </label>
                        <input type="text" id="username_email" name="username_email" required maxlength="100">
                        <div class="error-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fa fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password" required maxlength="100" autocomplete="current-password">
                        <div class="error-feedback"></div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fa fa-sign-in"></i> Login
                    </button>
                </form>
                
                <div class="back-to-home">
                    <a href="index.php"><i class="fa fa-arrow-left"></i> Back to Portfolio</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> My Portfolio. All Rights Reserved.</p>
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
            
            document.getElementById('username_email').focus();
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>
