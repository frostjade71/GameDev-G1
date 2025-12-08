# 🔧 Active Users Session Tracking Setup

## Overview
This guide will help you implement real-time session tracking to show currently logged-in users in the Admin Dashboard.

---

## Step 1: Create the `user_sessions` Table

Run this SQL in **phpMyAdmin**:

```sql
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `session_id` VARCHAR(255) NOT NULL,
  `login_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_last_activity` (`last_activity`),
  KEY `idx_active_sessions` (`is_active`, `last_activity`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## Step 2: What's Already Updated

✅ **login.php** - Now creates a session record when users log in  
✅ **logout.php** - Now marks sessions as inactive when users log out  
✅ **dashboard-stats.php** - Now queries active sessions instead of game progress  

---

## Step 3: Update the Stat Card in moderation.php

Find the "Active Players" stat card (around line 217-229) and replace it with:

```php
<!-- Active Users Stat Card -->
<div class="stat-card stat-success">
    <div class="stat-icon">
        <i class="fas fa-user-check"></i>
    </div>
    <div class="stat-content">
        <div class="stat-value" data-target="<?php echo $dashboardStats['active_users']; ?>">0</div>
        <div class="stat-label">Active Users</div>
        <div class="stat-sublabel">
            <i class="fas fa-clock"></i>
            <?php echo $dashboardStats['active_today']; ?> active this day
        </div>
    </div>
</div>
```

**Changes:**
- Icon: `fa-gamepad` → `fa-user-check`
- Label: "Active Players" → "Active Users"
- Sublabel: "Last 30 days" → "{number} active this day"

---

## How It Works

### Active Users Count
Shows users with **active sessions in the last 30 minutes**:
```sql
SELECT COUNT(DISTINCT user_id) 
FROM user_sessions 
WHERE is_active = 1 
AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
```

### Active Today Count
Shows users who **logged in at any point today**:
```sql
SELECT COUNT(DISTINCT user_id) 
FROM user_sessions 
WHERE DATE(login_time) = CURDATE()
```

---

## Testing

1. **Run the SQL** to create the `user_sessions` table
2. **Log out** of your current session
3. **Log back in** - this will create your first session record
4. **Go to Admin Dashboard** - you should see:
   - Active Users: **1** (you, logged in within last 30 minutes)
   - Sublabel: "**1 active this day**"

---

## Session Management

- **Login**: Creates new session record
- **Logout**: Marks session as `is_active = 0`
- **Inactive**: Sessions older than 30 minutes don't count as "active"
- **Today's Activity**: All logins from today are counted

---

## Troubleshooting

### If Active Users shows 0:
1. Make sure you ran the SQL to create the table
2. Log out and log back in to create a session
3. Check if `user_sessions` table has records

### If you get SQL errors:
- Make sure the `users` table exists (for foreign key)
- Check that you're using the correct database

---

## Summary

✅ **Created**: `user_sessions` table for tracking  
✅ **Updated**: login.php, logout.php, dashboard-stats.php  
⏳ **Manual**: Update stat card in moderation.php (see Step 3)

After completing these steps, your dashboard will show real-time active user counts!
