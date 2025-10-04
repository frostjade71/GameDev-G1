// VocabWorld Game Logic
class VocabWorldGame {
    constructor() {
        this.currentScreen = 'main-menu';
        this.gameState = {
            score: 0,
            level: 1,
            streak: 0,
            questionsAnswered: 0,
            correctAnswers: 0,
            currentQuestion: null,
            gameActive: false,
            points: 0
        };
        
        this.characterData = window.userData?.characterData || {
            hat: null,
            clothes: null,
            color: '#3b82f6',
            accessories: []
        };
        
        // Initialize shards from user data
        this.gameState.points = window.userData?.shards || 0;
        
        this.shopItems = this.initializeShopItems();
        this.currentShopCategory = 'hats';
        
        this.initializeGame();
    }
    
    async initializeGame() {
        this.loadShopItems();
        this.updatePointsDisplay();
        this.setupEventListeners();
        
        // Initialize shard display in header
        this.updateShardDisplay();
    }
    
    getGameWords() {
        // Basic word list for the game (simplified from the original vocabulary system)
        return [
            // Easy words (difficulty 1)
            { word: "cat", definition: "a small domesticated carnivorous mammal", example: "The cat sat on the mat.", difficulty: 1 },
            { word: "dog", definition: "a domesticated carnivorous mammal", example: "The dog barked loudly.", difficulty: 1 },
            { word: "book", definition: "a written or printed work", example: "I love reading this book.", difficulty: 1 },
            { word: "house", definition: "a building for human habitation", example: "My house is blue.", difficulty: 1 },
            { word: "tree", definition: "a woody perennial plant", example: "The tree is very tall.", difficulty: 1 },
            
            // Medium words (difficulty 2)
            { word: "beautiful", definition: "pleasing the senses or mind aesthetically", example: "The sunset was beautiful.", difficulty: 2 },
            { word: "important", definition: "of great significance or value", example: "Education is important.", difficulty: 2 },
            { word: "different", definition: "not the same as another", example: "We have different opinions.", difficulty: 2 },
            { word: "understand", definition: "perceive the intended meaning", example: "I understand the problem.", difficulty: 2 },
            { word: "remember", definition: "have in or be able to bring to one's mind", example: "I remember that day.", difficulty: 2 },
            
            // Hard words (difficulty 3)
            { word: "magnificent", definition: "extremely beautiful or impressive", example: "The view was magnificent.", difficulty: 3 },
            { word: "sophisticated", definition: "having a refined knowledge", example: "She has sophisticated tastes.", difficulty: 3 },
            { word: "extraordinary", definition: "very unusual or remarkable", example: "This is an extraordinary achievement.", difficulty: 3 },
            { word: "comprehensive", definition: "complete and thorough", example: "This is a comprehensive guide.", difficulty: 3 },
            { word: "phenomenon", definition: "a fact or situation that is observed", example: "This is a natural phenomenon.", difficulty: 3 }
        ];
    }
    
    setupEventListeners() {
        // Answer input enter key
        const answerInput = document.getElementById('answer-input');
        if (answerInput) {
            answerInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.submitAnswer();
                }
            });
        }
        
        
        // Add click sound to vocabworld cards
        const vocabworldCards = document.querySelectorAll('.vocabworld-card');
        vocabworldCards.forEach(card => {
            card.addEventListener('click', () => {
                playClickSound();
            });
        });
    }
    
    // Screen Management
    showScreen(screenId) {
        // Hide all screens
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active');
        });
        
        // Show target screen
        const targetScreen = document.getElementById(screenId);
        if (targetScreen) {
            targetScreen.classList.add('active');
            this.currentScreen = screenId;
        }
    }
    
    showMainMenu() {
        this.showScreen('main-menu');
    }
    
    showGameScreen() {
        this.showScreen('game-screen');
        this.startNewGame();
    }
    
    
    showCharacter() {
        this.showScreen('character-screen');
    }
    
    showInstructions() {
        this.showScreen('instructions-screen');
    }
    
    showGameOver() {
        this.showScreen('game-over-screen');
        this.displayFinalStats();
    }
    
    // Game Logic
    startNewGame() {
        this.gameState = {
            score: 0,
            level: 1,
            streak: 0,
            questionsAnswered: 0,
            correctAnswers: 0,
            currentQuestion: null,
            gameActive: true,
            points: this.gameState.points
        };
        
        this.updateGameDisplay();
        this.generateQuestion();
    }
    
    generateQuestion() {
        if (!this.gameState.gameActive) return;
        
        const difficulty = this.getDifficultyLevel();
        const availableWords = this.getGameWords().filter(word => word.difficulty <= difficulty);
        
        if (availableWords.length === 0) {
            this.endGame();
            return;
        }
        
        const randomWord = availableWords[Math.floor(Math.random() * availableWords.length)];
        const questionType = this.getRandomQuestionType();
        
        this.gameState.currentQuestion = {
            word: randomWord,
            type: questionType,
            correctAnswer: randomWord.word,
            options: this.generateAnswerOptions(randomWord, questionType)
        };
        
        this.displayQuestion();
    }
    
    getDifficultyLevel() {
        // Increase difficulty based on level and streak
        let baseDifficulty = Math.min(3, Math.floor(this.gameState.level / 3) + 1);
        if (this.gameState.streak >= 5) baseDifficulty = Math.min(5, baseDifficulty + 1);
        return baseDifficulty;
    }
    
    getRandomQuestionType() {
        const types = ['definition', 'synonym', 'antonym', 'scrambled'];
        return types[Math.floor(Math.random() * types.length)];
    }
    
    generateAnswerOptions(correctWord, questionType) {
        const options = [correctWord.word];
        const allWords = this.getGameWords().map(w => w.word);
        
        // Add 3 incorrect options
        while (options.length < 4) {
            const randomWord = allWords[Math.floor(Math.random() * allWords.length)];
            if (!options.includes(randomWord)) {
                options.push(randomWord);
            }
        }
        
        // Shuffle options
        return this.shuffleArray(options);
    }
    
    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
    
    displayQuestion() {
        const question = this.gameState.currentQuestion;
        const questionTypeEl = document.getElementById('question-type');
        const questionTextEl = document.getElementById('question-text');
        const questionHintEl = document.getElementById('question-hint');
        const answerInputContainer = document.getElementById('answer-input-container');
        const multipleChoiceContainer = document.getElementById('multiple-choice-container');
        const feedbackContainer = document.getElementById('feedback-container');
        
        // Hide feedback
        feedbackContainer.style.display = 'none';
        
        // Set question type
        questionTypeEl.textContent = this.getQuestionTypeText(question.type);
        
        // Set question text and hint
        switch (question.type) {
            case 'definition':
                questionTextEl.textContent = `What word means: "${question.word.definition}"?`;
                questionHintEl.textContent = `Example: ${question.word.example}`;
                break;
            case 'synonym':
                questionTextEl.textContent = `What is a synonym for "${question.word.word}"?`;
                questionHintEl.textContent = `Hint: ${question.word.definition}`;
                break;
            case 'antonym':
                questionTextEl.textContent = `What is an antonym for "${question.word.word}"?`;
                questionHintEl.textContent = `Hint: ${question.word.definition}`;
                break;
            case 'scrambled':
                const scrambled = this.scrambleWord(question.word.word);
                questionTextEl.textContent = `Unscramble this word: "${scrambled}"`;
                questionHintEl.textContent = `Hint: ${question.word.definition}`;
                break;
        }
        
        // Show appropriate answer input
        if (question.type === 'scrambled' || question.type === 'definition') {
            answerInputContainer.style.display = 'flex';
            multipleChoiceContainer.style.display = 'none';
            document.getElementById('answer-input').value = '';
            document.getElementById('answer-input').focus();
        } else {
            answerInputContainer.style.display = 'none';
            multipleChoiceContainer.style.display = 'block';
            this.displayMultipleChoiceOptions(question.options);
        }
    }
    
    getQuestionTypeText(type) {
        const typeTexts = {
            'definition': 'Definition Question',
            'synonym': 'Synonym Question',
            'antonym': 'Antonym Question',
            'scrambled': 'Word Scramble'
        };
        return typeTexts[type] || 'Vocabulary Question';
    }
    
    scrambleWord(word) {
        const letters = word.split('');
        for (let i = letters.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [letters[i], letters[j]] = [letters[j], letters[i]];
        }
        return letters.join('');
    }
    
    displayMultipleChoiceOptions(options) {
        const choiceButtons = document.getElementById('choice-buttons');
        choiceButtons.innerHTML = '';
        
        options.forEach((option, index) => {
            const button = document.createElement('button');
            button.className = 'choice-btn';
            button.textContent = option;
            button.onclick = () => this.selectAnswer(option);
            choiceButtons.appendChild(button);
        });
    }
    
    selectAnswer(answer) {
        this.checkAnswer(answer);
    }
    
    submitAnswer() {
        const answerInput = document.getElementById('answer-input');
        const answer = answerInput.value.trim().toLowerCase();
        
        if (answer) {
            this.checkAnswer(answer);
        }
    }
    
    checkAnswer(userAnswer) {
        const question = this.gameState.currentQuestion;
        const correctAnswer = question.correctAnswer.toLowerCase();
        const isCorrect = userAnswer === correctAnswer;
        
        this.gameState.questionsAnswered++;
        
        if (isCorrect) {
            this.gameState.correctAnswers++;
            this.gameState.streak++;
            this.gameState.score += this.calculatePoints();
            this.gameState.points += this.calculatePoints();
            
            // Level up every 5 correct answers
            if (this.gameState.correctAnswers % 5 === 0) {
                this.gameState.level++;
            }
            
            this.showFeedback(true, `Correct! +${this.calculatePoints()} points`);
        } else {
            this.gameState.streak = 0;
            this.showFeedback(false, `Incorrect. The answer was "${question.correctAnswer}"`);
        }
        
        this.updateGameDisplay();
        this.updateShardDisplay();
        
        // Auto-advance after 2 seconds
        setTimeout(() => {
            if (this.gameState.gameActive) {
                this.nextQuestion();
            }
        }, 2000);
    }
    
    calculatePoints() {
        const basePoints = 100;
        const streakMultiplier = Math.min(5, Math.floor(this.gameState.streak / 3) + 1);
        const levelMultiplier = Math.floor(this.gameState.level / 2) + 1;
        return basePoints * streakMultiplier * levelMultiplier;
    }
    
    showFeedback(isCorrect, message) {
        const feedbackContainer = document.getElementById('feedback-container');
        const feedbackText = document.getElementById('feedback-text');
        const nextButton = document.getElementById('next-question');
        
        feedbackContainer.style.display = 'block';
        feedbackContainer.className = `feedback-container ${isCorrect ? 'feedback-correct' : 'feedback-incorrect'}`;
        feedbackText.textContent = message;
        nextButton.style.display = 'none';
    }
    
    nextQuestion() {
        if (this.gameState.questionsAnswered >= 20) {
            this.endGame();
        } else {
            this.generateQuestion();
        }
    }
    
    endGame() {
        this.gameState.gameActive = false;
        this.saveGameProgress();
        this.showGameOver();
    }
    
    updateGameDisplay() {
        document.getElementById('current-score').textContent = this.gameState.score;
        document.getElementById('current-level').textContent = this.gameState.level;
        document.getElementById('current-streak').textContent = this.gameState.streak;
    }
    
    displayFinalStats() {
        const accuracy = this.gameState.questionsAnswered > 0 
            ? Math.round((this.gameState.correctAnswers / this.gameState.questionsAnswered) * 100)
            : 0;
        
        document.getElementById('final-score').textContent = this.gameState.score;
        document.getElementById('questions-answered').textContent = this.gameState.questionsAnswered;
        document.getElementById('accuracy').textContent = `${accuracy}%`;
        document.getElementById('points-earned').textContent = this.calculatePoints();
    }
    
    
    
    
    updatePointsDisplay() {
        const currentPointsEl = document.getElementById('current-points');
        if (currentPointsEl) {
            currentPointsEl.textContent = this.gameState.points;
        }
        
        // Update shard count in header
        this.updateShardDisplay();
    }
    
    updateShardDisplay() {
        const shardCountEl = document.getElementById('shard-count');
        if (shardCountEl) {
            shardCountEl.textContent = this.gameState.points;
        }
    }
    
    // Shop System
    showShopCategory(category) {
        this.currentShopCategory = category;
        
        // Update category buttons
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        this.loadShopItems();
    }
    
    loadShopItems() {
        const shopItems = document.getElementById('shop-items');
        if (!shopItems) return;
        
        shopItems.innerHTML = '';
        
        const categoryItems = this.shopItems[this.currentShopCategory] || [];
        
        categoryItems.forEach(item => {
            const shopItem = document.createElement('div');
            const isOwned = this.isItemOwned(item.id);
            const canAfford = this.gameState.points >= item.price;
            
            shopItem.className = `shop-item ${isOwned ? 'owned' : canAfford ? 'affordable' : 'expensive'}`;
            shopItem.innerHTML = `
                <div class="item-icon">${item.icon}</div>
                <div class="item-name">${item.name}</div>
                <div class="item-price">${isOwned ? 'Owned' : `${item.price} pts`}</div>
            `;
            
            if (!isOwned && canAfford) {
                shopItem.onclick = () => this.purchaseItem(item);
            }
            
            shopItems.appendChild(shopItem);
        });
    }
    
    isItemOwned(itemId) {
        switch (this.currentShopCategory) {
            case 'hats':
                return this.characterData.hat === itemId;
            case 'clothes':
                return this.characterData.clothes === itemId;
            case 'colors':
                return this.characterData.color === itemId;
            case 'accessories':
                return this.characterData.accessories.includes(itemId);
            default:
                return false;
        }
    }
    
    purchaseItem(item) {
        if (this.gameState.points >= item.price) {
            this.gameState.points -= item.price;
            
            // Apply item
            switch (this.currentShopCategory) {
                case 'hats':
                    this.characterData.hat = item.id;
                    break;
                case 'clothes':
                    this.characterData.clothes = item.id;
                    break;
                case 'colors':
                    this.characterData.color = item.id;
                    break;
                case 'accessories':
                    if (!this.characterData.accessories.includes(item.id)) {
                        this.characterData.accessories.push(item.id);
                    }
                    break;
            }
            
            this.updateCharacterDisplay();
            this.updatePointsDisplay();
            this.updateShardDisplay();
            this.loadShopItems();
            this.saveCharacterData();
            
            // Show success message
            this.showToast(`Purchased ${item.name}!`, 'success');
        }
    }
    
    // Data Management
    saveGameProgress() {
        // Save to database via AJAX
        fetch('save_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: window.userData.userId,
                gameType: 'vocabworld',
                score: this.gameState.score,
                level: this.gameState.level,
                questionsAnswered: this.gameState.questionsAnswered,
                correctAnswers: this.gameState.correctAnswers,
                points: this.gameState.points
            })
        }).catch(error => {
            console.error('Error saving progress:', error);
        });
    }
    
    saveCharacterData() {
        // Save character customization to database
        fetch('save_character.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: window.userData.userId,
                characterData: this.characterData
            })
        }).catch(error => {
            console.error('Error saving character data:', error);
        });
    }
    
    // Utility Functions
    showToast(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--royal-blue);
            color: var(--white);
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    
    initializeShopItems() {
        return {
            hats: [
                { id: 'crown', name: 'Royal Crown', icon: '👑', price: 500 },
                { id: 'cap', name: 'Baseball Cap', icon: '🧢', price: 200 },
                { id: 'hat', name: 'Top Hat', icon: '🎩', price: 300 },
                { id: 'helmet', name: 'Knight Helmet', icon: '⛑️', price: 400 },
                { id: 'cowboy', name: 'Cowboy Hat', icon: '🤠', price: 350 }
            ],
            clothes: [
                { id: 'suit', name: 'Business Suit', icon: '👔', price: 600 },
                { id: 'hoodie', name: 'Hoodie', icon: '👕', price: 250 },
                { id: 'dress', name: 'Elegant Dress', icon: '👗', price: 400 },
                { id: 'armor', name: 'Knight Armor', icon: '🛡️', price: 800 },
                { id: 'labcoat', name: 'Lab Coat', icon: '🥼', price: 300 }
            ],
            colors: [
                { id: '#3b82f6', name: 'Royal Blue', icon: '🔵', price: 100 },
                { id: '#ef4444', name: 'Crimson Red', icon: '🔴', price: 100 },
                { id: '#10b981', name: 'Emerald Green', icon: '🟢', price: 100 },
                { id: '#f59e0b', name: 'Golden Yellow', icon: '🟡', price: 100 },
                { id: '#8b5cf6', name: 'Purple', icon: '🟣', price: 100 }
            ],
            accessories: [
                { id: 'glasses', name: 'Smart Glasses', icon: '🤓', price: 150 },
                { id: 'sunglasses', name: 'Cool Sunglasses', icon: '😎', price: 200 },
                { id: 'watch', name: 'Luxury Watch', icon: '⌚', price: 300 },
                { id: 'necklace', name: 'Gold Necklace', icon: '📿', price: 400 },
                { id: 'ring', name: 'Magic Ring', icon: '💍', price: 250 }
            ]
        };
    }
}

// Global Functions (called from HTML)
let game;

function startGame() {
    if (!game) game = new VocabWorldGame();
    game.showGameScreen();
}


function showCharacter() {
    if (!game) game = new VocabWorldGame();
    game.showCharacter();
}

function showInstructions() {
    if (!game) game = new VocabWorldGame();
    game.showInstructions();
}

function showMainMenu() {
    if (!game) game = new VocabWorldGame();
    game.showMainMenu();
}

function submitAnswer() {
    if (game) game.submitAnswer();
}

function nextQuestion() {
    if (game) game.nextQuestion();
}

function showShopCategory(category) {
    if (game) game.showShopCategory(category);
}


// Initialize game when page loads
document.addEventListener('DOMContentLoaded', async function() {
    game = new VocabWorldGame();
    await game.initializeGame();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .character-base, .character-hat, .character-clothes, .character-accessory {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    
    .character-hat {
        top: 30%;
        font-size: 1.5rem;
    }
    
    .character-clothes {
        top: 60%;
        font-size: 1.2rem;
    }
    
    .character-accessory {
        top: 40%;
        right: 20%;
        font-size: 1rem;
    }
`;
document.head.appendChild(style);
