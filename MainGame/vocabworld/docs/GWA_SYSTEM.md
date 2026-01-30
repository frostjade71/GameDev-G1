# GWA System Documentation

## Overview

The **GWA (General Weighted Average)** is a performance metric in VocabWorld that represents a player's overall progress and standing. Unlike traditional academic GWA where lower is better, in VocabWorld, a **higher GWA indicates better performance**.

## Calculation Formula

The GWA is calculated based on the player's total level across the game.

**Formula:**

```
GWA = Total Player Level × 1.5
```

### Example Calculations

- **Level 1**: 1 \* 1.5 = **1.50 GWA**
- **Level 5**: 5 \* 1.5 = **7.50 GWA**
- **Level 10**: 10 \* 1.5 = **15.00 GWA**

## Technical Implementation

### File Location

- **Logic**: `includes/gwa_updater.php`
- **Function**: `updateUserGWA()`

### Database Integration

The system uses two tables to calculate and store GWA:

1.  **Source Table**: `game_progress`
    - Retrieves `player_level` for the specific game type (e.g., 'vocabworld').
2.  **Target Table**: `user_gwa`
    - Stores the calculated score in the `gwa` column.

### Update Triggers

The GWA is automatically recalculated and updated in the following scenarios:

1.  **Character Menu Access**: When visiting `charactermenu/character.php`, the system calls `updateAllUserGWAs()` to ensure the displayed GWA is up-to-date.
2.  **Profile Viewing**: The main profile page also triggers a GWA refresh to reflect recent level-ups.

## Display

The GWA is prominently displayed in:

- **Game HUD**: Top status bar (`game.php`).
- **Character Menu**: Stats panel (`charactermenu/character.php`).
- **Victory Screen**: Summary after clearing a world.
