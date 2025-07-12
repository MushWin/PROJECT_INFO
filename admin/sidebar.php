<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h2>Portfolio CMS</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="main-menu">
            <li><a href="dashboard.php" <?php if(basename($_SERVER['SCRIPT_NAME']) == 'dashboard.php') echo 'class="active"'; ?>>
                <i class="fa fa-dashboard"></i> Dashboard
            </a></li>
            <li><a href="edit_portfolio.php" <?php if(basename($_SERVER['SCRIPT_NAME']) == 'edit_portfolio.php') echo 'class="active"'; ?>>
                <i class="fa fa-user-circle"></i> Edit Portfolio
            </a></li>
            <li><a href="change_password.php" <?php if(basename($_SERVER['SCRIPT_NAME']) == 'change_password.php') echo 'class="active"'; ?>>
                <i class="fa fa-key"></i> Change Password
            </a></li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../index.php" target="_blank">
            <i class="fa fa-eye"></i> View Portfolio
        </a>
        <a href="../logout.php">
            <i class="fa fa-sign-out"></i> Logout
        </a>
    </div>
</aside>
