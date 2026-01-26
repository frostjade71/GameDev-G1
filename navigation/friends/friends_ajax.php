<?php
require_once '../../onboarding/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'shuffle_users') {
    // Replicate logic from friends.php to get suggested friends
    
    // Get all pending friend requests sent by current user
    $stmt = $pdo->prepare("SELECT receiver_id FROM friend_requests WHERE requester_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get 6 random users not already friends or pending
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.grade_level, u.profile_image, u.created_at 
        FROM users u
        WHERE u.id != ? 
        AND u.id NOT IN (
            SELECT CASE 
                WHEN f.user1_id = ? THEN f.user2_id
                WHEN f.user2_id = ? THEN f.user1_id
            END
            FROM friends f
            WHERE f.user1_id = ? OR f.user2_id = ?
        )
        AND u.id NOT IN (
            SELECT requester_id 
            FROM friend_requests 
            WHERE receiver_id = ? AND status = 'pending'
        )
        ORDER BY RAND() 
        LIMIT 6
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $all_users = $stmt->fetchAll();

    $suggested_friends = [];
    foreach ($all_users as $suggested_user) {
        $has_pending_request = in_array($suggested_user['id'], $pending_requests);
        
        // Determine the highest badge for this user
        $is_jaderby = (strtolower($suggested_user['username']) === 'jaderby garcia peñaranda');
        $is_admin = ($suggested_user['grade_level'] === 'Admin' || $is_jaderby);
        $is_teacher = ($suggested_user['grade_level'] === 'Teacher');
        
        $highest_badge = null;
        if ($is_jaderby) {
            $highest_badge = ['src' => '../../assets/badges/developer.png', 'alt' => 'Developer Badge', 'title' => 'Developer'];
        } elseif ($is_admin) {
            $highest_badge = ['src' => '../../assets/badges/moderator.png', 'alt' => 'Admin Badge', 'title' => 'Admin'];
        } elseif ($is_teacher) {
            $highest_badge = ['src' => '../../assets/badges/teacher.png', 'alt' => 'Teacher Badge', 'title' => 'Teacher'];
        }
        
        $suggested_friends[] = [
            'id' => $suggested_user['id'],
            'username' => $suggested_user['username'],
            'profile_image' => !empty($suggested_user['profile_image']) ? '../../' . $suggested_user['profile_image'] : '../../assets/menu/defaultuser.png',
            'grade_level' => $suggested_user['grade_level'],
            'joined_date' => date('M j, Y', strtotime($suggested_user['created_at'])),
            'has_pending_request' => $has_pending_request,
            'highest_badge' => $highest_badge
        ];
    }

    echo json_encode(['success' => true, 'users' => $suggested_friends]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
