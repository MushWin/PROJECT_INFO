<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$directories = [
    '../uploads',
    '../uploads/profile',
    '../uploads/cv',
    '../uploads/certificates'
];

echo "<h1>Upload Directory Maintenance</h1>";

foreach ($directories as $dir) {
    echo "<p>Checking directory: $dir... ";
    
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created successfully.";
            chmod($dir, 0777);
            echo " Permissions set to 0777.";
        } else {
            echo "FAILED TO CREATE!";
        }
    } else {
        echo "Already exists.";
        if (is_writable($dir)) {
            echo " Directory is writable.";
        } else {
            echo " WARNING: Directory is not writable!";
            if (chmod($dir, 0777)) {
                echo " Permissions updated to 0777.";
            } else {
                echo " Failed to update permissions.";
            }
        }
    }
    
    echo "</p>";
}

echo "<p><a href='edit_portfolio.php'>Return to Edit Portfolio</a></p>";
?>
