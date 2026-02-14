<?php
// Ensure clean JSON output for AJAX
ini_set('display_errors', '0');
ini_set('html_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// Buffer any accidental output
ob_start();

require_once '../../../onboarding/config.php';

// Ensure user is logged in and has Admin/Developer access like in dashboard.php
$gradeLevel = $_SESSION['grade_level'] ?? '';
$isAdminDev = in_array(strtolower($gradeLevel), array_map('strtolower', ['Developer', 'Admin']));

if (!function_exists('isLoggedIn') || !isLoggedIn() || !$isAdminDev) {
	// Clean any buffered output before JSON
	ob_end_clean();
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

// Params
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$grade_filter = isset($_GET['grade']) ? $_GET['grade'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate sort/order
$valid_columns = ['id', 'username', 'email', 'grade_level', 'section', 'created_at'];
$sort = in_array($sort, $valid_columns) ? $sort : 'id';
$order_sql = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

// Build query
$query = "SELECT id, username, email, grade_level, section, created_at FROM users";
$params = [];
if ($grade_filter !== 'all') {
	$query .= " WHERE grade_level = ?";
	$params[] = $grade_filter;
}

if (!empty($search)) {
    // Add WHERE or AND depending on if we already have a WHERE clause
    $query .= ($grade_filter !== 'all') ? " AND" : " WHERE";
    $query .= " (username LIKE ? OR email LIKE ? OR grade_level LIKE ? OR section LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
$query .= " ORDER BY $sort $order_sql";

try {
	$stmt = $pdo->prepare($query);
	$stmt->execute($params);
	$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Render rows HTML (keep the same structure/labels)
	ob_start();
	if (empty($users)) {
		echo '<tr><td colspan="7" class="no-users">No users found</td></tr>';
	} else {
		foreach ($users as $user) {
			$id = htmlspecialchars($user['id']);
			$username = htmlspecialchars($user['username']);
			$email = htmlspecialchars($user['email']);
			$grade = htmlspecialchars($user['grade_level']);
			$section = !empty($user['section']) ? htmlspecialchars($user['section']) : 'N/A';
			$joinDate = date('M j, Y', strtotime($user['created_at']));
			$selfId = $_SESSION['user_id'] ?? null;
			$currentUserGrade = $_SESSION['grade_level'] ?? '';
			// Logic from user-management.php: disabled if self or not developer
			$isDeveloper = ($currentUserGrade === 'Developer');
			$isSelf = ($id == $selfId);
			$canDelete = $isDeveloper && !$isSelf;
			$deleteDisabledAttr = $canDelete ? '' : 'disabled style="opacity:0.5;cursor:not-allowed;"';

			echo '<tr>';
			echo '<td data-label="ID">' . $id . '</td>';
			echo '<td data-label="Username">
				<div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
					<img src="../../assets/menu/defaultuser.png" 
						 alt="Avatar" 
						 class="creator-avatar"
						 style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
					<span class="creator-name">' . $username . '</span>
				</div>
			</td>';
			echo '<td data-label="Email">' . $email . '</td>';
			echo '<td data-label="Grade Level"><span class="grade-badge">' . $grade . '</span></td>';
			// Section column removed to match user-management.php
			echo '<td data-label="Join Date">' . $joinDate . '</td>';
			echo '<td class="action-buttons" data-label="Actions">';
			echo '<button class="btn-edit" onclick="viewUser(' . $id . ')" title="View"><i class="fas fa-eye"></i></button>';
			echo '<button class="btn-delete" onclick="deleteUser(' . $id . ', \'' . htmlspecialchars(addslashes($user['username'])) . '\')" title="Delete" ' . $deleteDisabledAttr . '><i class="fas fa-trash"></i></button>';
			echo '</td>';
			echo '</tr>';
		}
	}
	$rowsHtml = ob_get_clean();

	// Discard any accidental output captured before
	ob_clean();
	echo json_encode([
		'success' => true,
		'rows_html' => $rowsHtml
	]);
} catch (Exception $e) {
	$extra = ob_get_clean();
	echo json_encode([
		'success' => false,
		'message' => 'Failed to load users',
		'detail' => $extra ? trim($extra) : null
	]);
}
