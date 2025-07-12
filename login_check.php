<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'portfolio_db';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userToCheck = 'Dwin';
$emailToCheck = 'aldwinsuarez@gmail.com';

$stmt = $conn->prepare("SELECT id, username, password, email, is_active FROM users WHERE username = ?");
$stmt->bind_param("s", $userToCheck);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Login Check Results</h2>";

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p>✅ User '{$user['username']}' FOUND in database.</p>";
    echo "<p>Email: {$user['email']}</p>";
    echo "<p>Is active: " . ($user['is_active'] ? "Yes" : "No") . "</p>";
    echo "<p>Stored password hash: {$user['password']}</p>";
    echo "<p>To log in, use:</p>";
    echo "<p>Username: {$user['username']}</p>";
    echo "<p>Password: dwin1111</p>";
} else {
    echo "<p>❌ User '$userToCheck' NOT found by username.</p>";
    
    $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE email = ?");
    $stmt->bind_param("s", $emailToCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>✅ But user found by email '{$emailToCheck}'</p>";
        echo "<p>Correct username is: {$user['username']}</p>";
    } else {
        echo "<p>❌ User NOT found by email '{$emailToCheck}' either.</p>";
    }
}

$result = $conn->query("SELECT id, username, email FROM users");
echo "<h3>All Users in Database:</h3>";
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>ID: {$row['id']} - Username: {$row['username']} - Email: {$row['email']}</li>";
}
echo "</ul>";

$conn->close();
?>
