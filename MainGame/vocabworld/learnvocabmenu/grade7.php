<?php
require_once '../../../onboarding/config.php';
require_once '../../../includes/greeting.php';

// Check if user is logged in
requireLogin();

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's vocabworld progress
$stmt = $pdo->prepare("SELECT * FROM game_progress WHERE user_id = ? AND game_type = 'vocabworld'");
$stmt->execute([$user_id]);
$progress = $stmt->fetch();

// Get user's vocabworld scores
$stmt = $pdo->prepare("SELECT * FROM game_scores WHERE user_id = ? AND game_type = 'vocabworld' ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$scores = $stmt->fetchAll();

// Calculate average percentage
$total_sessions = count($scores);
$average_percentage = 0;
if ($total_sessions > 0) {
    $total_score = array_sum(array_column($scores, 'score'));
    $max_possible_score = $total_sessions * 1000; // Assuming max 1000 points per session
    $average_percentage = round(($total_score / $max_possible_score) * 100, 1);
}

// Get character customization data
$character_data = null;
$user_shards = 0;
if ($progress && $progress['unlocked_levels']) {
    $character_data = json_decode($progress['unlocked_levels'], true);
    $user_shards = $character_data['current_points'] ?? 0;
}

// Get shard balance from new shard system
require_once '../shard_manager.php';
$shardManager = new ShardManager($pdo);
$shard_balance = $shardManager->getShardBalance($user_id);
if ($shard_balance) {
    $user_shards = $shard_balance['current_shards'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade 7 - Philippine Literary Texts - VocabWorld</title>
    <link rel="stylesheet" href="../style.css?v=3">
    <link rel="stylesheet" href="../navigation/navigation.css?v=3">
    <link rel="stylesheet" href="learnvocabmenu.css?v=3">
    <link rel="stylesheet" href="../../../notif/toast.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="game-container">
        <!-- Background -->
        <div class="background-image"></div>
        
        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <div class="game-logo-container">
                    <img src="../assets/vocabworldhead.png" alt="VocabWorld" class="game-header-logo">
                </div>
            </div>
            <div class="header-right">
                <div class="shard-currency">
                    <img src="../assets/currency/shard1.png" alt="Shards" class="shard-icon">
                    <span class="shard-count" id="shard-count">0</span>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <span class="greeting"><?php echo getGreeting(); ?></span>
                        <span class="username"><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span>
                    </div>
                    <div class="profile-dropdown">
                        <a href="#" class="profile-icon">
                            <img src="../../../assets/menu/defaultuser.png" alt="Profile" class="profile-img">
                        </a>
                        <div class="profile-dropdown-content">
                            <div class="profile-dropdown-header">
                                <img src="../../../assets/menu/defaultuser.png" alt="Profile" class="profile-dropdown-avatar">
                                <div class="profile-dropdown-info">
                                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <div class="profile-dropdown-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-menu">
                                <a href="../../../navigation/profile/profile.php" class="profile-dropdown-item">
                                    <i class="fas fa-user"></i>
                                    <span>View Profile</span>
                                </a>
                                <a href="../../../navigation/favorites/favorites.php" class="profile-dropdown-item">
                                    <i class="fas fa-star"></i>
                                    <span>My Favorites</span>
                                </a>
                                <a href="../../../settings/settings.php" class="profile-dropdown-item">
                                    <i class="fas fa-cog"></i>
                                    <span>Settings</span>
                                </a>
                            </div>
                            <div class="profile-dropdown-footer">
                                <button class="profile-dropdown-item sign-out" onclick="showLogoutModal()">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sign Out</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Menu -->
        <div id="main-menu" class="screen active">
            <div class="menu-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Grade 7 - Vocabulary Mastery</h1>
                    <p class="page-subtitle">English Language Arts - Building Word Power for Effective Communication</p>
                </div>
                
                <!-- Lesson Content -->
                <div class="lesson-content">
                    <div class="lesson-section">
                        <h2 class="section-title">A. Introduction to Vocabulary Building</h2>
                        <div class="lesson-text">
                            <p>Welcome to your Grade 7 Vocabulary Journey!</p>
                            <p>In this unit, we will explore the fascinating world of words to enhance your communication skills and reading comprehension.</p>
                            <p><strong>Words are the building blocks of language.</strong><br>
                            A rich vocabulary helps you express yourself clearly and understand others better.</p>
                            
                            <p>This lesson focuses on three key areas of vocabulary development:</p>
                            <ul>
                                <li><strong>Context Clues</strong> – Understanding words from how they're used in sentences.</li>
                                <li><strong>Word Relationships</strong> – Exploring synonyms, antonyms, and word families.</li>
                                <li><strong>Affixes and Roots</strong> – Breaking down words into meaningful parts.</li>
                            </ul>
                            
                            <p>By the end of this lesson, you will be able to:</p>
                            <ul>
                                <li>Determine the meaning of unfamiliar words using context clues</li>
                                <li>Understand relationships between words with similar or opposite meanings</li>
                                <li>Use prefixes, suffixes, and root words to determine word meanings</li>
                                <li>Expand your vocabulary with commonly used academic words</li>
                            </ul>
                        </div>
                    </div>

                    <div class="lesson-section">
                        <h2 class="section-title">B. Understanding Words Through Context</h2>
                        <div class="lesson-text">
                            <p><strong>Context is key to understanding new words.</strong><br>
                            The words and sentences around an unfamiliar word can help you figure out its meaning.</p>
                            
                            <h3>1. Types of Context Clues</h3>
                            <p>Here are different ways context can help you understand new words:</p>
                            
                            <h4>Definition Clues</h4>
                            <p>The sentence defines the word directly or gives a synonym.</p>
                            <div class="example-box">
                                <p><em>Example:</em> The <strong>archaeologist</strong>, a scientist who studies ancient cultures, discovered a 2,000-year-old vase.</p>
                            </div>
                            
                            <h4>Example Clues</h4>
                            <p>The sentence gives examples that help explain the word's meaning.</p>
                            <div class="example-box">
                                <p><em>Example:</em> <strong>Amphibians</strong> like frogs, toads, and salamanders can live both on land and in water.</p>
                            </div>
                            
                            <h4>Contrast Clues</h4>
                            <p>The word is contrasted with its opposite.</p>
                            <div class="example-box">
                                <p><em>Example:</em> Unlike her <strong>gregarious</strong> sister, Maria was shy and preferred to be alone.</p>
                            </div>
                            
                            <h3>2. Practice with Context Clues</h3>
                            <p>Let's practice using context clues with these sentences from Philippine literature:</p>
                            
                            <div class="activity-box">
                                <p><strong>Activity 1:</strong> Determine the meaning of the bolded word in each sentence.</p>
                                <ol>
                                    <li>The old man's <strong>gaunt</strong> face showed that he hadn't eaten in days.
                                        <div class="hint">(Hint: What would someone's face look like if they haven't eaten?)</div>
                                    </li>
                                    <li>She spoke in a <strong>monotone</strong> voice, making the lecture difficult to stay awake through.
                                        <div class="hint">(Hint: What kind of voice would make something boring?)</div>
                                    </li>
                                    <li>The <strong>aroma</strong> of adobo cooking in the kitchen made everyone's mouth water.
                                        <div class="hint">(Hint: What sense is being described here?)</div>
                                    </li>
                                </ol>
                            </div>
                            
                            <h3>3. Common Academic Vocabulary</h3>
                            <p>Here are some important academic words you'll encounter in Grade 7:</p>
                            
                            <div class="vocab-list">
                                <div class="vocab-card">
                                    <div class="vocab-word">Analyze</div>
                                    <div class="vocab-definition">To examine in detail to understand better</div>
                                    <div class="vocab-example">We will <em>analyze</em> the story's main characters.</div>
                                </div>
                                
                                <div class="vocab-card">
                                    <div class="vocab-word">Convey</div>
                                    <div class="vocab-definition">To make an idea or feeling known</div>
                                    <div class="vocab-example">The author uses similes to <em>convey</em> the character's emotions.</div>
                                </div>
                                
                                <div class="vocab-card">
                                    <div class="vocab-word">Significant</div>
                                    <div class="vocab-definition">Important or meaningful</div>
                                    <div class="vocab-example">The discovery was <em>significant</em> for the research team.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                                <li>Explaining character traits and motivations</li>
                                <li>Identifying the author's point of view and style</li>
                            </ul>
                            
                            <h3>3. Drama</h3>
                            <p>Drama is a story meant to be performed by actors for an audience.</p>
                            
                            <h4>Traditional Philippine drama:</h4>
                            <ul>
                                <li><strong>Sarsuwela</strong> – musical play with songs, dialogue, and dances</li>
                                <li><strong>Moro-moro and senakulo</strong> – plays with historical or religious themes</li>
                            </ul>
                            
                            <h4>Modern Philippine drama:</h4>
                            <p>Stage plays in English or Filipino that discuss social issues or personal struggles.</p>
                            
                            <h4>Skills to practice in Drama:</h4>
                            <ul>
                                <li>Recognizing dialogue, stage directions, and acts / scenes</li>
                                <li>Interpreting characters' emotions and motivations</li>
                                <li>Understanding how performance elements (tone of voice, gesture, costume, setting) help convey meaning</li>
                            </ul>
                            
                            <h4>Key Points about Literature</h4>
                            <ul>
                                <li>Reflects Filipino values, traditions, and history</li>
                                <li>Helps us understand human experiences across time and place</li>
                                <li>Inspires creativity and empathy</li>
                                <li>Strengthens skills in reading, listening, and interpreting texts</li>
                            </ul>
                        </div>
                    </div>

                    <div class="lesson-section">
                        <h2 class="section-title">C. Informational Texts</h2>
                        <div class="lesson-text">
                            <p>Informational texts provide factual details, explanations, and instructions about real-world topics.</p>
                            <p>These texts are part of daily learning in school and life.</p>
                            
                            <h4>Examples:</h4>
                            <ul>
                                <li>Newspaper and magazine articles</li>
                                <li>Textbook chapters</li>
                                <li>Pamphlets and brochures (e.g., about health, environment, tourism)</li>
                                <li>Charts, graphs, diagrams in educational materials</li>
                                <li>Informational websites and digital resources</li>
                            </ul>
                            
                            <h4>Skills to practice in Informational Texts:</h4>
                            <ul>
                                <li>Finding the main idea and key details</li>
                                <li>Understanding text structures such as description, sequence, cause-and-effect, problem-solution</li>
                                <li>Interpreting charts, tables, captions, and visuals</li>
                                <li>Identifying the author's purpose – to inform, explain, or describe</li>
                                <li>Evaluating the reliability of sources in print and online</li>
                            </ul>
                            
                            <p><strong>Informational texts help us gain knowledge and make better decisions.</strong></p>
                        </div>
                    </div>

                    <div class="lesson-section">
                        <h2 class="section-title">D. Transactional Texts</h2>
                        <div class="lesson-text">
                            <p>Transactional texts are used for direct communication and practical purposes.</p>
                            <p>We encounter them in school, at home, and online.</p>
                            
                            <h4>Examples:</h4>
                            <ul>
                                <li>Personal and formal letters</li>
                                <li>Emails and messaging for school or official communication</li>
                                <li>Announcements and notices</li>
                                <li>Schedules, instructions, and guidelines</li>
                                <li>Application forms, requests, and receipts</li>
                                <li>Social media posts that share official information (e.g., government alerts, school reminders)</li>
                            </ul>
                            
                            <h4>Skills to practice in Transactional Texts:</h4>
                            <ul>
                                <li>Identifying the purpose (to inform, request, invite, instruct)</li>
                                <li>Recognizing the audience (friend, teacher, official, community)</li>
                                <li>Using the correct format and polite tone for different situations</li>
                                <li>Understanding instructions and details accurately</li>
                                <li>Communicating in a clear, concise, and respectful way</li>
                            </ul>
                            
                            <p><strong>Mastering transactional texts helps students participate confidently in real-life communication.</strong></p>
                        </div>
                    </div>

                    <div class="lesson-section">
                        <h2 class="section-title">E. Reading Focus Strategies</h2>
                        <div class="lesson-text">
                            <p>For all kinds of texts, Grade 7 learners should ask:</p>
                            <ul>
                                <li><strong>Purpose:</strong> Why was the text written?</li>
                                <li><strong>Audience:</strong> Who is it for?</li>
                                <li><strong>Structure:</strong> How is the text organized (stanzas, paragraphs, dialogue, lists)?</li>
                                <li><strong>Language Features:</strong> Are there figurative expressions, technical terms, or formal / informal tone?</li>
                                <li><strong>Message:</strong> What idea, feeling, or action should I take away from the text?</li>
                            </ul>
                            
                            <p><strong>These strategies help students become active, thoughtful readers.</strong></p>
                        </div>
                    </div>

                    <div class="lesson-section">
                        <h2 class="section-title">F. Summary of the Lesson</h2>
                        <div class="lesson-text">
                            <ul>
                                <li><strong>Philippine Literary Texts</strong> – poetry, prose, drama – express the culture and emotions of the Filipino people.</li>
                                <li><strong>Informational Texts</strong> – explain facts, ideas, and processes.</li>
                                <li><strong>Transactional Texts</strong> – help us communicate and function in everyday situations.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="back-button" onclick="goBack()">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Grade Selection</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="toast-overlay" id="logoutModal" style="display: none;">
        <div class="toast" id="logoutConfirmation">
            <h3 style="margin-bottom: 1rem; color: #ff6b6b;">Logout Confirmation</h3>
            <p style="margin-bottom: 1.5rem; color: rgba(255, 255, 255, 0.8);">Are you sure you want to logout?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="confirmLogout()" style="background: #ff6b6b; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Yes, Logout</button>
                <button onclick="hideLogoutModal()" style="background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-family: 'Press Start 2P', cursive; font-size: 0.8rem;">Cancel</button>
            </div>
        </div>
    </div>

    <script src="learnvocabmenu.js"></script>
    <script src="../../../navigation/shared/profile-dropdown.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.userData = {
            userId: <?php echo $user_id; ?>,
            username: '<?php echo addslashes($user['username']); ?>',
            gradeLevel: '<?php echo addslashes($user['grade_level']); ?>',
            shards: <?php echo $user_shards; ?>,
            characterData: <?php echo $character_data ? json_encode($character_data) : 'null'; ?>,
            averagePercentage: <?php echo $average_percentage; ?>,
            totalSessions: <?php echo $total_sessions; ?>
        };

        // Logout functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                modal.style.display = 'block';
                modal.classList.add('show');
                confirmation.classList.remove('hide');
                confirmation.classList.add('show');
            }
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            const confirmation = document.getElementById('logoutConfirmation');
            
            if (modal && confirmation) {
                confirmation.classList.remove('show');
                confirmation.classList.add('hide');
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        }

        function confirmLogout() {
            window.location.href = '../../../onboarding/logout.php';
        }

        // Go back to grade selection
        function goBack() {
            window.location.href = 'learn.php';
        }

        // Initialize shard count display
        function initializeShardDisplay() {
            const shardCountEl = document.getElementById('shard-count');
            if (shardCountEl && window.userData) {
                shardCountEl.textContent = window.userData.shards || 0;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeShardDisplay();
        });
    </script>
</body>
</html>
