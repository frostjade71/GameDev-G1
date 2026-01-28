<?php
// Turn off error reporting for display, but log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

try {
    require_once '../../onboarding/config.php';

    // session_start() is already called in config.php, so we don't need it here.
    // If config.php didn't start it, we would check session_status() first.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check permissions
    $gradeLevel = $_SESSION['grade_level'] ?? '';
    $isTeacherOrAdmin = in_array(strtolower($gradeLevel), array_map('strtolower', ['Teacher', 'Admin', 'Developer']));

    if (!function_exists('isLoggedIn') || !isLoggedIn() || !$isTeacherOrAdmin) {
        ob_clean();
        echo json_encode(['html' => '', 'total' => 0]);
        exit();
    }

    $selected_grade = $_GET['grade'] ?? 'all';
    $selected_section = $_GET['section'] ?? 'all';
    $search_query = $_GET['search'] ?? '';

    // Build Query
    $query = "SELECT l.*, u.username as creator_name, u.profile_image as creator_image 
              FROM lessons l 
              LEFT JOIN users u ON l.created_by = u.id 
              WHERE 1=1";
    $params = [];

    if ($selected_grade !== 'all') {
        $query .= " AND l.grade_level = ?";
        $params[] = $selected_grade;
    }

    if ($selected_section !== 'all') {
        $query .= " AND l.section = ?";
        $params[] = $selected_section;
    }

    if (!empty($search_query)) {
        $query .= " AND (l.title LIKE ? OR l.content LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }

    $query .= " ORDER BY l.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $lessons = $stmt->fetchAll();

    $total_lessons = count($lessons);
    $html = '';

    if (empty($lessons)) {
        $html .= '<tr><td colspan="6" class="no-vocab">No lessons found. Click "Create Lesson" to add one.</td></tr>';
    } else {
        foreach ($lessons as $lesson) {
            $creator_image = !empty($lesson['creator_image']) ? '../../' . htmlspecialchars($lesson['creator_image']) : '../../assets/menu/defaultuser.png';
            $creator_name = htmlspecialchars(explode(' ', $lesson['creator_name'])[0]);
            $date = date('M d, Y', strtotime($lesson['created_at']));
            $title = addslashes(htmlspecialchars($lesson['title']));
            
            $html .= '<tr>
                <td data-label="Title">
                    <strong>' . htmlspecialchars($lesson['title']) . '</strong>
                </td>
                <td data-label="Grade">
                    <span class="grade-badge">Grade ' . htmlspecialchars($lesson['grade_level']) . '</span>
                </td>
                <td data-label="Section">
                    ' . htmlspecialchars($lesson['section'] ?: 'All Sections') . '
                </td>
                <td data-label="Created by">
                    <div class="creator-info" style="display: flex; align-items: center; gap: 8px;">
                        <img src="' . $creator_image . '" 
                             alt="Avatar" 
                             class="creator-avatar"
                             style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.2);">
                        <span class="creator-name">' . $creator_name . '</span>
                    </div>
                </td>
                <td data-label="Date">
                    ' . $date . '
                </td>
                <td data-label="Actions" class="action-buttons">
                    <a href="edit_lesson.php?id=' . $lesson['id'] . '" class="btn-edit" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn-delete" onclick="deleteLesson(' . $lesson['id'] . ', \'' . $title . '\')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>';
        }
    }

    // Clear buffer before sending JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'total' => number_format($total_lessons)]);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    // In production, might not want to show exact error, but for debugging/admin useful
    echo json_encode(['html' => '<tr><td colspan="6">Error loading lessons.</td></tr>', 'total' => 0, 'error' => $e->getMessage()]);
}
?>
