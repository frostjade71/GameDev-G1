# Word Weavers: Educational Game Platform

## Overview
Word Weavers is a web-based educational application thesis project designed by Group 1 College Seniors of Holy Cross College of Carigara Incorporated. It aims to make learning English fun and engaging through various interactive game modes. The platform features vocabulary and grammar-based games that help users enhance their language skills while allowing both teachers and students to track and monitor learning progress over time.
## Features

### User Authentication
- Secure login/registration system with email verification
- Session management
- User profile management

### Games
1. **Vocabworld**
   - Top-down educational vocabulary rpg game
   - Level-based progression
   - Character Customization

2. **Grammar Heroes **
   - Grammar exercises and challenges
   - Progress tracking
   - Interactive learning modules

### User Interface
- Responsive design for desktop and mobile devices
- Interactive game selection menu
- Progress tracking dashboard
- Achievement badges and rewards

### Social Features
- Friends system
- Highscore leaderboards
- Achievement sharing

## Technology Stack

### Frontend
- HTML5, CSS3, JavaScript
- Responsive design with mobile-first approach
- Interactive UI components

### Backend
- PHP
- MySQL Database
- RESTful API endpoints

### Dependencies
- PHPMailer (for email functionality)
- Various frontend libraries (Font Awesome, etc.)

## Getting Started

### Prerequisites
- XAMPP/WAMP/MAMP (or similar local server environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with JavaScript enabled

### Installation
1. Clone the repository to your local web server directory (e.g., `htdocs` or `www`)
2. Import the database schema from `onboarding/database_setup_updated.sql`
3. Configure your database connection in `onboarding/config.php`
4. Start your local web server and MySQL service
5. Access the application through your web browser at `http://localhost/GameDev-G1`

## Project Structure

```
GameDev-G1/
├── MainGame/                 # Game logic and assets
│   ├── grammarheroes/        # Grammar game components
│   └── vocabworld/           # Vocabulary game components
├── api/                      # API endpoints
├── assets/                   # Static assets (images, styles, etc.)
├── includes/                 # Shared PHP includes
├── navigation/               # Navigation components
│   ├── friends/              # Friends system
│   ├── profile/              # User profile management
│   └── shared/               # Shared navigation components
├── notif/                    # Notification system
├── onboarding/               # User authentication and registration
│   └── otp/                  # One-time password functionality
├── overview/                 # Dashboard and statistics
├── settings/                 # User settings
├── credits.php               # Credits page
├── game-selection.php        # Game selection screen
├── index.php                 # Entry point
├── menu.php                  # Main menu
└── styles.css                # Global styles
```

## Configuration

### Database Configuration
Edit `onboarding/config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'word_weavers');
```

### Email Configuration
Configure PHPMailer settings in `onboarding/otp/send_otp.php` for email verification.

## Usage
1. Register a new account or log in with existing credentials
2. Complete the email verification process
3. Access the main menu and select a game mode
4. Complete levels to earn points and achievements
5. Track your progress in the profile section

## Contributing
Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact
For any questions or feedback, please contact wordweavershccci@gmail.com or jaderbypenaranda@gmail.com

---
*Last updated: October 2023*
