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
			$canDelete = (($_SESSION['grade_level'] ?? '') === 'Developer') && ($id != $selfId);
			$deleteDisabled = $canDelete ? '' : 'disabled title="Delete (Developer only)"';
			$selfWarnDisabled = ($id == $selfId) ? 'disabled="disabled"' : '';
			echo '<tr>';
			echo '<td data-label="ID">' . $id . '</td>';
			echo '<td data-label="Username">' . $username . '</td>';
			echo '<td data-label="Email">' . $email . '</td>';
			echo '<td data-label="Grade Level"><span class="grade-badge">' . $grade . '</span></td>';
			echo '<td data-label="Section">' . $section . '</td>';
			echo '<td data-label="Join Date">' . $joinDate . '</td>';
			echo '<td class="actions" data-label="Actions">';
			echo '<button class="btn-view" onclick="viewUser(' . $id . ')" title="View"><i class="fas fa-eye"></i></button>';
			echo '<button class="btn-warn" onclick="warnUser(' . $id . ')" title="Warn" ' . $selfWarnDisabled . '><i class="fas fa-exclamation-triangle"></i></button>';
			if ($canDelete) {
				echo '<button class="btn-delete" onclick="deleteUser(' . $id . ', \'' . htmlspecialchars(addslashes($user['username'])) . '\')" title="Delete"><i class="fas fa-trash"></i></button>';
			} else {
				echo '<button class="btn-delete" disabled title="Delete (Developer only)"><i class="fas fa-trash"></i></button>';
			}
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
