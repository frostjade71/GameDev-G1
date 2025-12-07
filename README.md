<div align="center">

# <img src="assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" height="50" style="vertical-align: middle; margin-bottom: 5px;"> **WORD WEAVERS: EDUCATIONAL GAME PLATFORM**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net/)
[![Docker](https://img.shields.io/badge/Docker-✓-blue.svg)](https://www.docker.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue.svg)](https://www.mysql.com/)

A comprehensive web-based educational platform developed by Group 3 Computer Science Seniors at Holy Cross College of Carigara Incorporated in partial fulfillment of the requirements for the degree of Bachelor of Science in Computer Science. This interactive platform, created under the thesis titled "Developing Educational Games for High School Language Arts: Design Principles and Effectiveness," helps learners improve their English skills through immersive language arts web games featuring vocabulary building, grammar challenges, and social learning features.

</div>

## Project Overview

Word Weavers is designed to help Junior High School students (Grades 7-10) learn English in a fun and engaging way, following the **Philippine K-12 Curriculum** standards set by DepEd. Through interactive games, students can improve their vocabulary, grammar, reading comprehension, and communication skills while actually enjoying the learning process. The platform tracks student progress and provides meaningful insights, making it easier for both students and teachers to see real learning outcomes that align with K-12 assessment standards.

## Key Features

### Public Access
- Secure user registration with email verification
- OTP-based authentication system
- Real-time progress tracking and GWA (Grade Weighted Average) calculation
- Global leaderboards and achievement system
- Interactive game selection interface

### Game Experiences
- **Vocabworld**: Top-down educational vocabulary RPG with level-based progression
- **Grammar Heroes**: Interactive grammar challenges (coming soon)
- Character customization and progression system
- Save/load game functionality
- Multiple game worlds and environments
- Auto-detection currency system (Essence & Shards)

### Social Features
- Friends system with request management
- Favorites and bookmarking system
- Global highscore leaderboards
- Achievement sharing and comparison
- Real-time notification system
- Progress tracking and comparison

### Admin Console
- User moderation and management
- Notification system administration
- Profile management tools
- System analytics and reporting
- Complete platform oversight

## Quick Start

### <img src="https://skillicons.dev/icons?i=docker" alt="Docker" height="40" style="vertical-align: middle;"> With Docker (Recommended)
```bash
git clone https://github.com/frostjade71/GameDev-G1 GameDev-G1
cd GameDev-G1
docker-compose up -d
```

Access the application:
- **Web Interface**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

### Traditional Installation
```bash
git clone https://github.com/frostjade71/GameDev-G1 GameDev-G1
cd GameDev-G1
# Import database/school_portal 11-18-25.sql to your MySQL server
# Configure onboarding/config.php with your database credentials
```

Access the application at `http://localhost/GameDev-G1`

> **Security Note**: Configure your email settings in `onboarding/otp/send_otp.php` for OTP verification!

## Game System

The platform implements an engaging educational game ecosystem:

### Vocabworld Features
1. **Character Selection**: Choose from Ethan, Emma, Amber, and more characters
2. **Currency System**: Dual currency with Essence and Shards
3. **Level Progression**: Advance through vocabulary challenges
4. **Save System**: Persistent game progress
5. **Multiple Worlds**: Diverse game environments

### Progression Rules
- **GWA Tracking**: Automatic grade calculation based on performance
- **Achievement System**: Unlock badges and rewards through gameplay
- **Leaderboard Rankings**: Compete globally with other learners
- **Social Integration**: Share progress and compare with friends

## Technology Stack

<p align="center">
  <img src="https://skillicons.dev/icons?i=html,css,js,php,mysql,docker,vscode" alt="Technology Stack" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/icons/apache/apache-original-wordmark.svg" height="50" alt="Apache" />
</p>


## Configuration

### Database Setup
Edit `onboarding/config.php` with your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'school_portal');
```

### Docker Environment
The Docker setup uses these environment variables:
- `DB_HOST=db`
- `DB_USER=root`
- `DB_PASS=rootpassword`
- `DB_NAME=school_portal`

### Email Configuration
Configure PHPMailer in `onboarding/otp/send_otp.php` for OTP verification.

## Security Features

- **Password Hashing**: bcrypt encryption for all passwords
- **Prepared Statements**: PDO with parameterized queries
- **Input Sanitization**: All user inputs are sanitized and validated
- **Session Management**: Secure session handling with HTTP-only cookies
- **Email Verification**: OTP-based account verification
- **SQL Injection Prevention**: Prepared statements throughout the application

## Usage Guide

1. **Register**: Create a new account and verify your email via OTP
2. **Profile Setup**: Complete your profile information
3. **Select Game**: Choose from available game modes
4. **Character Selection**: Pick your character and customize
5. **Play & Learn**: Progress through vocabulary challenges
6. **Track Progress**: Monitor your GWA and achievements
7. **Social Features**: Connect with friends and compare progress
8. **Favorites**: Bookmark content for quick access

## Contributing

We welcome contributions! Here's how to get started:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -m 'Add NewFeature'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE.md) file for details.

---

## **Credits** <img src="assets\menu\HCCCI.webp" alt="Word Weavers Logo" height="24" style="vertical-align: middle;"> Group 3 Computer Science Seniors

> #### Documentation & QA/Testers:
- Alfred Estares
- Loren Mae Pascual
- Jeric Ganancial
- Ria Jhen Boreres
- Ken Erickson Bacarisas

> #### **Developer**
- **Jaderby Peñaranda**

  [![Website](https://img.shields.io/badge/🌏-jaderbypenaranda.link-1e88e5)](https://jaderbypenaranda.link/) [![Email](https://img.shields.io/badge/📩-Contact-4caf50)](mailto:jaderbypenaranda@gmail.com)

---

<div align="left">
  <img src="assets/menu/ww_logo_main.webp" alt="Word Weavers Logo" height="40" style="vertical-align: middle; margin-right: 12px;">
  <span><b>Word Weavers</b></span>
  <span style="margin-left: 10px;"><i>Empowering Learners Through Interactive Education</i></span>
</div>

---

**Version**: 1.8.0  
**Last Updated**: December 7, 2025
