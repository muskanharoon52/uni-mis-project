<?php
require_once 'includes/db.php';

$email = 'sso@university.edu';

// Check if user exists
$sql = "SELECT user_id, email, full_name, role_id, is_active, password_hash, plain_password 
        FROM users 
        WHERE email = ?";
$user = getRow($sql, [$email]);

echo "<h2>User Check Results:</h2>";
if ($user) {
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<br><strong>User ID:</strong> " . $user['user_id'] . "<br>";
    echo "<strong>Email:</strong> " . $user['email'] . "<br>";
    echo "<strong>Name:</strong> " . $user['full_name'] . "<br>";
    echo "<strong>Role ID:</strong> " . $user['role_id'] . "<br>";
    echo "<strong>Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "<br>";
    echo "<strong>Plain Password:</strong> " . $user['plain_password'] . "<br>";
    
    // Test password verification
    $test_password = 'password123';
    if (password_verify($test_password, $user['password_hash'])) {
        echo "<br>✅ Password 'password123' VERIFIED with password_hash!<br>";
    } else {
        echo "<br>❌ Password 'password123' NOT verified with password_hash<br>";
    }
    
    if ($user['plain_password'] == $test_password) {
        echo "✅ Password 'password123' MATCHES plain_password!<br>";
    } else {
        echo "❌ Password 'password123' NOT match plain_password<br>";
    }
    
} else {
    echo "❌ User 'sso@university.edu' NOT FOUND in database!<br>";
}

// Show all users
echo "<br><h3>All Users in Database:</h3>";
$all_users = getRows("SELECT user_id, email, full_name, role_id, is_active FROM users");
echo "<pre>";
print_r($all_users);
echo "</pre>";
?>