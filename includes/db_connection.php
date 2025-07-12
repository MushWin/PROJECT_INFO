<?php
$servername = "localhost";
$username = "u302876046_dwin";
$password = "Dw1ndw1n";
$dbname = "u302876046_dwin";

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($servername, $username, $password);

    $conn->set_charset("utf8mb4");
 
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result->num_rows == 0) {
        $conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    $conn->select_db($dbname);
    
} catch (mysqli_sql_exception $e) {
    die("<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;'>
            Database connection failed: " . htmlspecialchars($e->getMessage()) . 
            "<br>Please make sure your MySQL server is running and the credentials are correct.
         </div>");
}
?>
