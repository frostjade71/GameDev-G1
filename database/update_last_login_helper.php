<?php
/**
 * Update Last Login Helper
 * Add this code to your login.php file after successful authentication
 * to track when users log in
 */

// Example: Add this after verifying user credentials in your login.php
// Replace $user_id with the actual user ID from your login process

/*
// Update last_login timestamp
try {
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
} catch (PDOException $e) {
    error_log("Failed to update last_login: " . $e->getMessage());
}
*/

// OR if you want to add it to an existing login file, find where the user is authenticated
// and add the UPDATE query there. For example:

/*
// After successful login verification
if (password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['grade_level'] = $user['grade_level'];
    
    // Update last login timestamp
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Redirect to dashboard
    header('Location: ../menu.php');
    exit();
}
*/
?>
