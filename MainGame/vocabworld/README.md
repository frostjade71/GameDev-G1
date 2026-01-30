# VocabWorld - Educational Vocabulary Game

## Overview

VocabWorld is a browser-based educational vocabulary game designed for students in Grades 7 to 10. The game focuses on vocabulary practice and acquisition through engaging, curriculum-aligned tasks.

## Features

### Core Gameplay

- **Multiple Question Types**: Definitions, synonyms, antonyms, and more.
- **Progress Tracking**: Level progression.
- **Economy System**: Earn and spend points on cosmetic items (characters so far).

### Character Customization

- **Change Character**: Change your character.

### Educational Features

- **Teacher-Curated Content**: Every question in the gameplay is sourced from the teacher's word bank.
- **Dynamic Learning**: Teachers can add, edit, or remove questions to tailor the curriculum.
- **Learning Mode**: Browse and study vocabulary words before playing.
- **Interactive Lesson Views**: Grade-specific portals (Grade 7-10) with access control.
- **Performance Analytics**: Track accuracy, average scores, and improvement over time.
- **Accessibility**: Responsive design for desktop and mobile devices.

## Teacher Guide

### Dashboard Overview

Teachers have access to a dedicated dashboard for managing content and tracking student progress.

### Lesson Management

Located in `navigation/teacher/lessons.php`, this feature allows teachers to:

- **Create Lessons**: Build new vocabulary lessons with custom titles and descriptions.
- **Edit Lessons**: Modify existing lesson details using the editor (`edit_lesson.php`).
- **Delete Lessons**: Remove outdated or unused lessons (`delete_lesson.php`).
- **View Lessons**: Preview how lessons appear to students.

### Question Management

Teachers can curate the question bank to align with their curriculum:

- **Add Questions**: Input new vocabulary words, definitions, and distractors.
- **Edit Questions**: Refine existing questions for clarity or difficulty adjustments.
- **Dynamic Content**: Questions added here are immediately available in the "Learn Mode" and gameplay sessions.

## Game Mechanics

### Scoring System

- **Correct Answer**:
  - **EXP Gained**: +25 EXP
  - **Essence Earned**: 5-10 Essence (Randomized)
  - **Outcome**: Monster defeated.

- **Wrong Answer**:
  - **Damage Taken**: 10-25 HP (Randomized)
  - **EXP Gained**: +5 EXP (Participation bonus)
  - **Outcome**: Monster defeated, but health is lost.

- **Game Over**: Occurs when HP reaches 0.

### Question Types

1. **Definition Questions**: "What word means: [definition]?"
2. **Synonym Questions**: "What is a synonym for [word]?"
3. **Antonym Questions**: "What is an antonym for [word]?"
4. **Word Scrambles**: "Unscramble this word: [scrambled letters]"

### Economy System

#### Shards & Essence

- **Essence**: Primary currency earned from defeating monsters (5-10 per kill).
- **Shards**: Premium currency used for character customization.
- **Conversion**: Exchange Essence for Shards in the "Convert" page.
  - **Small Pack**: 1 Shard (Cost: 20 Essence)
  - **Medium Pack**: 5 Shards (Cost: 100 Essence)
  - **Large Pack**: 10 Shards (Cost: 200 Essence)

#### Shop System

- **Character Shop**: Purchase new characters (e.g., Amber) using Shards.
- **Ownership Verification**: Tracks owned items and prevents duplicate purchases.

## File Structure

```
MainGame/vocabworld/
├── index.php                 # Main hub: Profile, Stats, Navigation
├── game.php                  # Core Gameplay: Phaser Engine, Battles, API Calls
├── style.css                 # Global Styles
├── script.js                 # Global Scripts
├── shard_manager.php         # Economy Backend: Shard management logic
├── api/
│   ├── vocabulary.php        # API: Fetches questions
│   ├── essence_manager.php   # API: Manages Essence currency
│   └── level_manager.php     # API: Handles XP and Leveling
├── learnvocabmenu/
│   ├── learn.php             # Learning Portal: Grade selection
│   ├── grade7.php            # Grade 7 Lesson View
│   ├── grade8.php            # Grade 8 Lesson View
│   ├── grade9.php            # Grade 9 Lesson View
│   └── grade10.php           # Grade 10 Lesson View
├── charactermenu/
│   ├── character.php         # Character Profile: Stats & Preview
│   ├── shop_characters.php   # Character Shop: Buy new avatars
│   └── convert.php           # Essence Exchange: Convert Essence to Shards
├── instructions/
│   └── instructions.php      # How to Play: Comprehensive guide
└── README.md                 # Project Documentation
```

### Key Files

- **`game.php`**: The heart of the game. Initializes the Phaser game instance, manages the player sprite and monster groups, handles collision detection (initiating battles), and updates the UI with real-time stats.
- **`index.php`**: The central hub where users land. It displays a summary of the user's profile and provides navigation cards to all major sections.
- **`shard_manager.php`**: A robust backend class that handles all Shard-related operations.
- **`learnvocabmenu/learn.php`**: The gateway to learning. It enforces role-based access control.
- **`charactermenu/character.php`**: The player's personal space. Shows detailed statistics and allows them to view their current character.
- **`instructions/instructions.php`**: An interactive guide that explains game mechanics, controls, and rewards.