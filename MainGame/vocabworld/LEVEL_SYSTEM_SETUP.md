# Level System Setup Instructions

## 1. Database Setup

Run the SQL file to add the level system columns to your database:

```sql
-- Run this in phpMyAdmin or MySQL command line
SOURCE C:/xampp/htdocs/GameDev-G1/MainGame/vocabworld/setup_level_system.sql;
```

Or manually execute:
```sql
ALTER TABLE game_progress 
ADD COLUMN IF NOT EXISTS player_level INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS experience_points INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_monsters_defeated INT DEFAULT 0;

UPDATE game_progress 
SET player_level = 1, experience_points = 0, total_monsters_defeated = 0 
WHERE player_level IS NULL;
```

## 2. Level System Features

### Experience Points (EXP)
- **Correct Answer**: +25 EXP + Monster defeated
- **Wrong Answer**: +5 EXP (participation reward)

### Level Progression
- **Formula**: EXP needed = 100 × level^1.5
- **Level 1 → 2**: 141 EXP needed
- **Level 2 → 3**: 245 EXP needed
- **Level 3 → 4**: 374 EXP needed
- And so on (exponential growth)

### Level Up Notifications
- Gold "🎉 LEVEL UP!" message appears when leveling up
- Level display updates in real-time
- Longer pause to celebrate level up (2.5 seconds)

## 3. Files Created/Modified

### New Files:
1. `api/level_manager.php` - Level system logic
2. `api/update_level.php` - API endpoint for level updates
3. `setup_level_system.sql` - Database migration

### Modified Files:
1. `game.php` - Added level loading and display
   - Loads player level from database
   - Awards EXP after each battle
   - Shows level up notifications

## 4. How It Works

1. **On Game Load**: 
   - Player's current level is loaded from database
   - Displayed in the stats UI

2. **During Battle**:
   - Answer question correctly: +25 EXP
   - Answer question wrong: +5 EXP
   - System checks if player leveled up

3. **Level Up**:
   - EXP carries over to next level
   - Level display updates immediately
   - Special celebration message shown

4. **Character Menu**:
   - Level is stored in `game_progress` table
   - Can be displayed in character menu if needed

## 5. Testing

1. Start the game and check your level (should be 1 for new players)
2. Answer questions and watch EXP notifications
3. After ~6 correct answers, you should level up to Level 2
4. Check that level persists when you reload the game

## 6. Future Enhancements

Possible additions:
- EXP bar visual progress indicator
- Level-based rewards (unlock features at certain levels)
- Level requirements for harder monsters
- Leaderboard by level
- Level displayed in character menu
