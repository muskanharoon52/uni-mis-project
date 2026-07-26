<?php
// test_login.php - Debug login

require_once __DIR__ . '/config/db.php';

$email = 'sso@university.edu';
$password = 'password123';

echo "<h2>Login Debug</h2>";

// Check user exists
$query = "SELECT * FROM users WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "❌ User not found: $email<br>";
} else {
    echo "✅ User found: " . $user['full_name'] . "<br>";
    echo "Role ID: " . $user['role_id'] . "<br>";
    echo "Password Hash: " . $user['password_hash'] . "<br><br>";
    
    // Check password
    if (password_verify($password, $user['password_hash'])) {
        echo "✅ Password is CORRECT!<br>";
        echo "You can login with: $email / $password";
    } else {
        echo "❌ Password is INCORRECT!<br>";
        echo "Hash: " . $user['password_hash'] . "<br>";
        echo "Password tried: $password<br>";
        
        // Generate new hash
        $new_hash = password_hash($password, PASSWORD_DEFAULT);
        echo "New hash for '$password': $new_hash<br><br>";
        echo "Run this query to fix:<br>";
        echo "<code>UPDATE users SET password_hash = '$new_hash' WHERE email = '$email';</code>";
    }
}
?>