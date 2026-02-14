<?php
// Dashboard Statistics Calculator
// This file calculates and returns all statistics needed for the admin dashboard
// Note: This file is included by dashboard.php which has already loaded config.php

// Initialize statistics array
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'active_today' => 0,
    'recent_users' => 0,
    'avg_gwa' => 0,
    'total_essence' => 0,
    'total_shards' => 0,
    'total_games_played' => 0,
    'grade_distribution' => [],
    'recent_activity' => [],
    'top_performers' => [],
    'role_distribution' => [],
    'top_gwa_by_grade' => []
];

try {
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Recent Users (last 7 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats['recent_users'] = $stmt->fetch()['count'];
    
    // Currently Active Users (logged in with active sessions within last 30 minutes)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM user_sessions 
        WHERE is_active = 1 
        AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $result = $stmt->fetch();
    $stats['active_users'] = $result['count'] ?? 0;
    
    // Users Active Today (logged in at any point today)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM user_sessions 
        WHERE DATE(login_time) = CURDATE()
    ");
    $result = $stmt->fetch();
    $stats['active_today'] = $result['count'] ?? 0;
    
    // Average GWA
    $stmt = $pdo->query("
        SELECT AVG(gwa) as avg_gwa 
        FROM user_gwa
    ");
    $result = $stmt->fetch();
    $stats['avg_gwa'] = $result['avg_gwa'] ? round($result['avg_gwa'], 2) : 0;
    
    // Total Essence
    $stmt = $pdo->query("
        SELECT SUM(essence_amount) as total 
        FROM user_essence
    ");
    $result = $stmt->fetch();
    $stats['total_essence'] = $result['total'] ?? 0;
    
    // Total Shards
    $stmt = $pdo->query("
        SELECT SUM(current_shards) as total 
        FROM user_shards
    ");
    $result = $stmt->fetch();
    $stats['total_shards'] = $result['total'] ?? 0;
    
    // Total Games Played (sum of monsters defeated as proxy)
    $stmt = $pdo->query("
        SELECT SUM(total_monsters_defeated) as total 
        FROM game_progress
    ");
    $result = $stmt->fetch();
    $stats['total_games_played'] = $result['total'] ?? 0;
    
    // Grade Level Distribution (Students only - exclude Admin, Developer, Teacher)
    $stmt = $pdo->query("
        SELECT grade_level, COUNT(*) as count 
        FROM users 
        WHERE grade_level NOT IN ('Admin', 'Developer', 'Teacher')
        GROUP BY grade_level 
        ORDER BY grade_level
    ");
    $stats['grade_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top GWA by Grade Level
    $stmt = $pdo->query("
        SELECT 
            u.grade_level,
            AVG(ug.gwa) as avg_gwa,
            COUNT(ug.user_id) as student_count
        FROM user_gwa ug
        JOIN users u ON ug.user_id = u.id
        WHERE u.grade_level NOT IN ('Admin', 'Developer', 'Teacher')
        GROUP BY u.grade_level
        ORDER BY avg_gwa DESC
    ");
    $stats['top_gwa_by_grade'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Role Distribution (Admin, Developer, Teacher, Students)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN grade_level IN ('Admin', 'Developer') THEN 'Admin'
                WHEN grade_level = 'Teacher' THEN 'Teacher'
                ELSE 'Student'
            END as role,
            COUNT(*) as count
        FROM users
        GROUP BY role
        ORDER BY count DESC
    ");
    $stats['role_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent Activity (last 10 logged-in users)
    $stmt = $pdo->query("
        SELECT id, username, email, grade_level, last_login 
        FROM users 
        WHERE last_login IS NOT NULL
        ORDER BY last_login DESC 
        LIMIT 10
    ");
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top Performers by Fame (views + crescents)
    $stmt = $pdo->query("
        SELECT 
            username, 
            cresents, 
            views,
            (cresents * 10 + views) as fame_score
        FROM user_fame 
        ORDER BY fame_score DESC 
        LIMIT 5
    ");
    $stats['top_performers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top GWA Users (highest GWA scores)
    $stmt = $pdo->query("
        SELECT 
            u.username,
            ug.gwa,
            u.grade_level
        FROM user_gwa ug
        JOIN users u ON ug.user_id = u.id
        WHERE u.grade_level NOT IN ('Admin', 'Developer', 'Teacher')
        ORDER BY ug.gwa DESC
        LIMIT 5
    ");
    $stats['top_gwa_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Daily Traffic (last 30 days)
    $stmt = $pdo->query("
        SELECT 
            DATE(login_time) as login_date, 
            COUNT(DISTINCT user_id) as count 
        FROM user_sessions 
        WHERE login_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(login_time) 
        ORDER BY login_date ASC
    ");
    $stats['daily_traffic'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard Stats Error: " . $e->getMessage());
    // Return empty stats on error
}

// Return stats array for use in dashboard.php
return $stats;
