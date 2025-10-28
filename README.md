# Word Weavers: Educational Game Platform

## Overview
Word Weavers is a web-based educational application developed by Group 1 College Seniors of Holy Cross College of Carigara Incorporated. This interactive platform makes learning English engaging through various game modes, combining vocabulary and grammar exercises with RPG elements to create an immersive learning experience.

## Features

### User Authentication
- Secure login/registration system with email verification
- Session management
- User profile management

### Games
1. **Vocabworld**
   - Top-down educational vocabulary RPG game
   - Level-based progression system
   - Character customization options
   - Interactive word challenges

2. **Grammar Heroes**
   - Engaging grammar exercises
   - Progress tracking and analytics
   - Interactive learning modules
   - Achievement system

### User Interface
- Responsive design for all devices
- Intuitive game selection menu
- Comprehensive progress dashboard
- Achievement and reward system

### Social Features
- Friends system with chat
- Global highscore leaderboards
- Achievement sharing
- Progress comparison

## Technology Stack

### Frontend
- HTML5, CSS3, JavaScript
- Responsive design with mobile-first approach
- Interactive UI components

### Backend
- PHP 8.0+
- MySQL Database
- RESTful API architecture

### Dependencies
- PHPMailer for email functionality
- Frontend libraries (Font Awesome, etc.)
- JWT for authentication

## Getting Started

### Prerequisites
- XAMPP/WAMP/MAMP (or similar local server environment)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Modern web browser with JavaScript enabled

### Installation
1. Clone the repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/GameDev-G1.git
   ```
2. Import the database schema from `onboarding/database_setup_updated.sql`
3. Configure your database connection in `onboarding/config.php`
4. Set up your local server environment
5. Access the application at `http://localhost/GameDev-G1`

## Project Structure

```
GameDev-G1/
├── MainGame/                 # Core game logic and assets
│   ├── grammarheroes/        # Grammar game implementation
│   └── vocabworld/           # Vocabulary RPG game components
├── api/                      # RESTful API endpoints
├── assets/                   # Static files (images, styles, scripts)
├── includes/                 # Shared PHP classes and functions
├── navigation/               # UI navigation components
│   ├── friends/              # Social features
│   ├── profile/              # User management
│   └── shared/               # Common components
├── notif/                    # Notification system
├── onboarding/               # Authentication system
│   └── otp/                  # Email verification
├── overview/                 # User dashboard
├── settings/                 # Application settings
├── .htaccess                 # Server configuration
├── index.php                 # Application entry point
└── README.md                 # This file
```

## Configuration

### Database Setup
Edit `onboarding/config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'word_weavers');
```

### Email Configuration
Configure PHPMailer in `onboarding/otp/send_otp.php` for email verification.

## Usage
1. Register a new account and verify your email
2. Complete your profile setup
3. Select a game mode from the main menu
4. Progress through levels and complete challenges
5. Track your achievements and compare with friends

## Contributing
We welcome contributions! Here's how to get started:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -m 'Add NewFeature'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Open a Pull Request

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact
For any inquiries or feedback, please contact:
- Email: wordweavershccci@gmail.com
- Lead Developer: jaderbypenaranda@gmail.com

---
*Last updated: October 28th, 2025*
