<?php
// Disable any error output that could interfere with JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../onboarding/config.php';

// Start session and check if user is logged in
session_start();
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Clear any output buffers completely
while (ob_get_level()) {
    ob_end_clean();
}

// Get current user information
$current_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, grade_level, section, profile_image FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user = $stmt->fetch();

if (!$current_user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

// Get the user ID from POST data
$viewed_user_id = isset($_POST['viewed_user_id']) ? (int)$_POST['viewed_user_id'] : 0;

if (!$viewed_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

// Get the viewed user's information
$stmt = $pdo->prepare("SELECT id, username, email, grade_level, section, about_me, created_at FROM users WHERE id = ?");
$stmt->execute([$viewed_user_id]);
$viewed_user = $stmt->fetch();

if (!$viewed_user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Viewed user not found']);
    exit();
}

// Initialize user_fame table and get user stats
function initializeUserFame($pdo, $username) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_fame (username, cresents, views) VALUES (?, 0, 0)");
    $stmt->execute([$username]);
}

function getUserFame($pdo, $username) {
    initializeUserFame($pdo, $username);
    $stmt = $pdo->prepare("SELECT cresents, views FROM user_fame WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

function toggleCrescent($pdo, $viewer_username, $viewed_username, $viewer_id, $viewed_id) {
    initializeUserFame($pdo, $viewer_username);
    initializeUserFame($pdo, $viewed_username);
    
    // Check if viewer has already given a crescent to viewed user
    $stmt = $pdo->prepare("SELECT id FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
    $stmt->execute([$viewer_username, $viewed_username]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove crescent
        $stmt = $pdo->prepare("DELETE FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
        $stmt->execute([$viewer_username, $viewed_username]);
        $stmt = $pdo->prepare("UPDATE user_fame SET cresents = cresents - 1 WHERE username = ?");
        $stmt->execute([$viewed_username]);
        
        // Remove notification
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND type = 'cresent_received' AND JSON_EXTRACT(data, '$.sender_id') = ?");
        $stmt->execute([$viewed_id, $viewer_id]);
        
        return false; // Crescent removed
    } else {
        // Add crescent
        $stmt = $pdo->prepare("INSERT INTO user_crescents (giver_username, receiver_username, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$viewer_username, $viewed_username]);
        $stmt = $pdo->prepare("UPDATE user_fame SET cresents = cresents + 1 WHERE username = ?");
        $stmt->execute([$viewed_username]);
        
        // Add notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, data) VALUES (?, 'cresent_received', ?, ?)");
        $message = $viewer_username . " has given you a Cresent";
        $data = json_encode(['sender_id' => $viewer_id, 'sender_username' => $viewer_username]);
        $stmt->execute([$viewed_id, $message, $data]);
        
        return true; // Crescent added
    }
}

function hasGivenCrescent($pdo, $viewer_username, $viewed_username) {
    $stmt = $pdo->prepare("SELECT id FROM user_crescents WHERE giver_username = ? AND receiver_username = ?");
    $stmt->execute([$viewer_username, $viewed_username]);
    return $stmt->fetch() !== false;
}

// Create user_crescents table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS user_crescents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    giver_username VARCHAR(255) NOT NULL,
    receiver_username VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_crescent (giver_username, receiver_username)
)");

// Handle crescent toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_crescent') {
    if ($current_user['username'] !== $viewed_user['username']) {
        try {
            $added = toggleCrescent($pdo, $current_user['username'], $viewed_user['username'], $current_user['id'], $viewed_user['id']);
            $user_fame = getUserFame($pdo, $viewed_user['username']);
            $crescents_count = $user_fame ? $user_fame['cresents'] : 0;
            $has_given_crescent = $added;
            
            // Return JSON response for AJAX
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cresents' => $crescents_count,
                'has_given' => $has_given_crescent
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Cannot give crescent to yourself'
        ]);
    }
}
?>
