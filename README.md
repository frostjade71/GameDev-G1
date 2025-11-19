# Word Weavers: Educational Game Platform

## Overview
Word Weavers is a web-based educational platform developed by Group 1 College Seniors of Holy Cross College of Carigara Incorporated. This interactive platform helps learners improve their English skills through immersive language arts web games.

## Features

### User Authentication
- Secure login/registration system with email verification
- Session management
- User profile management
- OTP verification system using PHPMailer

### Games
1. **Vocabworld**
   - Top-down educational vocabulary RPG game
   - Level-based progression system with essence and shard currency
   - Character customization options (Ethan, Emma, Amber, more charcters soon)
   - Interactive word challenges and vocabulary learning
   - Save/load progress functionality
   - Auto shard detection system
   - Multiple game worlds and maps

2. **Grammar Heroes**
   - soon to be added or not

### User Interface
- Responsive design for all devices
- Intuitive game selection menu
- Comprehensive progress dashboard with GWA (Grade Weighted Average) tracking
- Achievement and reward system
- Modern notification system

### Social Features
- Friends system with send/accept/decline requests
- Favorites system for content bookmarking
- Global highscore leaderboards
- Achievement sharing
- Progress comparison
- Real-time notifications

### Administrative Features
- User moderation system
- Notification management
- User profile management

## Technology Stack

### Frontend
- HTML5, CSS3, JavaScript
- Responsive design with mobile-first approach
- Interactive UI components
- Custom game assets and sprites

### Backend
- PHP 8.0+
- MySQL 8.0 Database
- RESTful API architecture
- PDO for database operations

### Dependencies
- PHPMailer for email functionality
- Frontend libraries (Font Awesome, etc.)
- JWT for authentication

### Containerization
- Docker support with Docker Compose
- Apache server configuration
- MySQL database container
- phpMyAdmin for database management

## Getting Started

### Prerequisites
- Docker and Docker Compose (recommended)
- OR XAMPP/WAMP/MAMP (traditional setup)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Modern web browser with JavaScript enabled

### Installation with Docker (Recommended)
1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/GameDev-G1.git
   ```
2. Run with Docker Compose:
   ```bash
   docker-compose up -d
   ```
3. Access the application:
   - Main application: `http://localhost:8080`
   - phpMyAdmin: `http://localhost:8081`

### Traditional Installation
1. Clone the repository to your web server directory
2. Import the database schema from `database/school_portal 11-18-25.sql`
3. Configure your database connection in `onboarding/config.php`
4. Set up your local server environment
5. Access the application at `http://localhost/GameDev-G1`

## Project Structure

```
GameDev-G1/
├── MainGame/                 # Core game logic and assets
│   ├── grammarheroes/        # Grammar game implementation
│   └── vocabworld/           # Vocabulary RPG game components
│       ├── api/              # Game API endpoints
│       ├── assets/           # Game assets (characters, maps, UI)
│       ├── charactermenu/    # Character selection interface
│       ├── learnvocabmenu/   # Vocabulary learning interface
│       └── instructions/     # Game instructions
├── api/                      # RESTful API endpoints
├── assets/                   # Static files (images, styles, scripts)
│   ├── badges/               # User badge assets
│   ├── banner/               # Banner images
│   ├── menu/                 # Menu UI assets
│   └── pixels/               # Pixel art assets
├── credits/                  # Credits page
├── database/                 # Database schema and backups
├── includes/                 # Shared PHP classes and functions
├── navigation/               # UI navigation components
│   ├── friends/              # Social features
│   ├── profile/              # User management
│   ├── leaderboards/         # Score leaderboards
│   ├── moderation/           # Admin moderation
│   └── shared/               # Common components
├── notif/                    # Notification system
├── onboarding/               # Authentication system
│   └── otp/                  # Email verification
├── overview/                 # User dashboard
├── play/                     # Game selection interface
├── settings/                 # Application settings
├── docker-compose.yml        # Docker configuration
├── Dockerfile                # Docker image configuration
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
define('DB_NAME', 'school_portal');
```

### Docker Environment Variables
The Docker setup uses these environment variables:
- `DB_HOST=db`
- `DB_USER=root`
- `DB_PASS=rootpassword`
- `DB_NAME=school_portal`

### Email Configuration
Configure PHPMailer in `onboarding/otp/send_otp.php` for email verification.

## Game Features

### Vocabworld Game System
- **Character System**: Choose from Ethan (boy), Emma (girl), and many more soon
- **Currency System**: 
  - Essence: Primary game currency
  - Shards: Secondary currency with auto-detection
- **Level Progression**: Complete vocabulary challenges to advance
- **Save System**: Save and load game progress
- **Multiple Worlds**: Different game environments and challenges

### GWA (Grade Weighted Average) System
- Automatic GWA calculation based on game performance
- GWA tracking for different game types
- Grade management and reporting

## Usage
1. Register a new account and verify your email via OTP
2. Complete your profile setup
3. Select a game mode from the main menu
4. Choose your character and start playing
5. Progress through levels and complete vocabulary challenges
6. Track your achievements, GWA, and compare with friends
7. Use the favorites system to bookmark content

## Contributing
We welcome contributions! Here's how to get started:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -m 'Add NewFeature'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Open a Pull Request

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details.

## Contact
For any inquiries or feedback, please contact:
- Email: wordweavershccci@gmail.com
- Lead Developer: jaderbypenaranda@gmail.com

---
*Last updated: November 19th, 2025*
