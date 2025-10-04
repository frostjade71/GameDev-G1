# VocabWorld - Educational Vocabulary Game

## Overview
VocabWorld is a browser-based educational vocabulary game designed for students in Grades 7 to 10, aligned with the Philippine K-12 Language Arts curriculum. The game focuses on vocabulary practice and acquisition through engaging, curriculum-aligned tasks.

## Features

### Core Gameplay
- **Multiple Question Types**: Definitions, synonyms, antonyms, and word scrambles
- **Adaptive Difficulty**: Questions become harder as players progress and maintain streaks
- **Real-time Scoring**: Points awarded based on difficulty and streak multipliers
- **Progress Tracking**: Level progression and performance analytics

### Character Customization
- **Avatar System**: Visual character representation with customization options
- **Shop System**: Spend earned points on cosmetic items (hats, clothes, colors, accessories)
- **Progress Integration**: Character reflects player achievements and progress

### Educational Features
- **Curriculum Alignment**: Vocabulary words aligned with Grade 7-10 standards
- **Learning Mode**: Browse and study vocabulary words before playing
- **Performance Analytics**: Track accuracy, average scores, and improvement over time
- **Accessibility**: Responsive design for desktop and mobile devices

### Technical Features
- **Database Integration**: MySQL integration for user progress and customization
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Royal Blue Theme**: Professional design matching the VocabWorld logo
- **Real-time Updates**: Live score tracking and character customization

## File Structure

```
MainGame/vocabworld/
├── index.php              # Main game interface
├── style.css              # Royal blue themed styling
├── script.js              # Game logic and functionality
├── vocabulary_data.php    # Comprehensive vocabulary database
├── save_progress.php      # Database integration for game progress
├── save_character.php     # Database integration for character data
├── api/
│   └── vocabulary.php     # API endpoint for vocabulary data
└── README.md              # This documentation
```

## Database Integration

The game integrates with the existing MySQL database structure:

### Tables Used
- `users`: User authentication and profile information
- `game_scores`: Individual game session scores and performance
- `game_progress`: User progress, achievements, and character customization
- `user_settings`: User preferences and game settings

### Data Storage
- **Game Progress**: Scores, levels, questions answered, accuracy rates
- **Character Data**: Customization items, colors, accessories
- **Achievements**: Unlocked achievements and milestones
- **Statistics**: Average performance, total play time, session history

## Game Mechanics

### Scoring System
- **Base Points**: 100 points per correct answer
- **Streak Multiplier**: 2x, 3x, 4x, 5x for consecutive correct answers
- **Level Multiplier**: Increases with player level progression
- **Difficulty Bonus**: Harder questions award more points

### Question Types
1. **Definition Questions**: "What word means: [definition]?"
2. **Synonym Questions**: "What is a synonym for [word]?"
3. **Antonym Questions**: "What is an antonym for [word]?"
4. **Word Scrambles**: "Unscramble this word: [scrambled letters]"

### Character Customization
- **Hats**: Crown, baseball cap, top hat, knight helmet, cowboy hat
- **Clothes**: Business suit, hoodie, elegant dress, knight armor, lab coat
- **Colors**: Royal blue, crimson red, emerald green, golden yellow, purple
- **Accessories**: Smart glasses, sunglasses, luxury watch, gold necklace, magic ring

## Educational Alignment

### Grade 7 Vocabulary (Difficulty 1-2)
- Basic academic vocabulary
- Common words with clear definitions
- Simple sentence examples

### Grade 8 Vocabulary (Difficulty 2-3)
- Intermediate academic terms
- More complex definitions
- Contextual usage examples

### Grade 9 Vocabulary (Difficulty 3-4)
- Advanced academic vocabulary
- Sophisticated word usage
- Complex sentence structures

### Grade 10 Vocabulary (Difficulty 4-5)
- College-level vocabulary
- Abstract concepts
- Professional terminology

## Installation & Setup

1. **Database Setup**: Ensure MySQL database is configured with the provided schema
2. **File Placement**: Place all files in the `MainGame/vocabworld/` directory
3. **Permissions**: Ensure PHP has write permissions for database operations
4. **Configuration**: Update database connection settings in `config.php`

## Usage

### For Students
1. **Login**: Access the game through the main portal
2. **Start Game**: Begin vocabulary practice sessions
3. **Learn Mode**: Study vocabulary words before playing
4. **Customize Character**: Spend points on character items
5. **Track Progress**: Monitor performance and improvements

### For Teachers
1. **Monitor Progress**: View student performance through the database
2. **Customize Content**: Modify vocabulary data for specific needs
3. **Track Engagement**: Monitor student participation and improvement

## Technical Requirements

- **PHP 7.4+**: For server-side functionality
- **MySQL 5.7+**: For data storage and retrieval
- **Modern Browser**: Chrome, Firefox, Safari, Edge (latest versions)
- **JavaScript ES6+**: For client-side game logic
- **CSS3**: For responsive design and animations

## Browser Compatibility

- **Desktop**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **Mobile**: iOS Safari 13+, Chrome Mobile 80+, Samsung Internet 12+
- **Responsive**: Optimized for screens 320px to 1920px wide

## Performance Optimization

- **Lazy Loading**: Vocabulary data loaded on demand
- **Caching**: Browser caching for static assets
- **Database Indexing**: Optimized queries for fast data retrieval
- **Responsive Images**: Optimized background images and assets

## Security Features

- **User Authentication**: Secure login system integration
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based request validation

## Future Enhancements

- **Multiplayer Mode**: Competitive vocabulary challenges
- **Teacher Dashboard**: Advanced analytics and content management
- **Mobile App**: Native iOS and Android applications
- **AI Integration**: Personalized learning recommendations
- **Gamification**: Badges, leaderboards, and achievement systems

## Support & Maintenance

- **Regular Updates**: Vocabulary content and game mechanics
- **Bug Fixes**: Continuous improvement and error resolution
- **Performance Monitoring**: Database and server optimization
- **User Feedback**: Integration of student and teacher suggestions

## License

This educational game is part of the Word Weavers School Portal system and is designed for educational use in Philippine K-12 institutions.
