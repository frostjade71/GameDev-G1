// Learn Vocabulary Menu JavaScript
class LearnVocabMenu {
    constructor() {
        this.currentGrade = null;
        this.vocabularyData = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.itemsPerPage = 10;
        
        this.initializeMenu();
    }
    
    async initializeMenu() {
        // Get grade from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        this.currentGrade = urlParams.get('grade') || '7';
        
        // Mobile optimizations
        this.initializeMobileOptimizations();
        
        // Load vocabulary data for the selected grade
        await this.loadVocabularyData();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initialize display
        this.updateGradeDisplay();
        this.loadVocabularyList();
    }
    
    initializeMobileOptimizations() {
        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Optimize scrolling for mobile
        if (window.innerWidth <= 768) {
            document.body.style.webkitOverflowScrolling = 'touch';
            document.body.style.overflowScrolling = 'touch';
        }
        
        // Add mobile-specific classes
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        }
        
        // Handle orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });
        
        // Handle resize events
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }
    
    handleOrientationChange() {
        // Recalculate layout after orientation change
        const cards = document.querySelectorAll('.vocabworld-card');
        cards.forEach(card => {
            card.style.transform = 'none';
        });
        
        // Update mobile class
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        } else {
            document.body.classList.remove('mobile-device');
        }
    }
    
    handleResize() {
        // Update mobile class on resize
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        } else {
            document.body.classList.remove('mobile-device');
        }
    }
    
    setupEventListeners() {
        // Search functionality
        const vocabSearch = document.getElementById('vocab-search');
        if (vocabSearch) {
            vocabSearch.addEventListener('input', () => {
                this.filterVocabularyList();
            });
        }
        
        // Difficulty filter
        const difficultyFilter = document.getElementById('difficulty-filter');
        if (difficultyFilter) {
            difficultyFilter.addEventListener('change', () => {
                this.filterVocabularyList();
            });
        }
        
        // Back button
        const backBtn = document.querySelector('.back-btn');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                this.goBack();
            });
        }
        
        // Grade selection buttons
        const gradeButtons = document.querySelectorAll('.grade-btn');
        gradeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const grade = e.currentTarget.dataset.grade;
                this.selectGrade(grade);
            });
        });
    }
    
    async loadVocabularyData() {
        try {
            const response = await fetch(`../api/vocabulary.php?action=all&grade=${this.currentGrade}`);
            const data = await response.json();
            
            if (data.success) {
                this.vocabularyData = data.data;
                this.filteredData = [...this.vocabularyData];
            } else {
                console.error('Failed to load vocabulary data:', data.error);
                this.vocabularyData = this.getFallbackVocabularyData();
                this.filteredData = [...this.vocabularyData];
            }
        } catch (error) {
            console.error('Error loading vocabulary data:', error);
            this.vocabularyData = this.getFallbackVocabularyData();
            this.filteredData = [...this.vocabularyData];
        }
    }
    
    getFallbackVocabularyData() {
        const gradeData = {
            '7': [
                { word: "abundant", definition: "existing in large quantities", example: "The forest has abundant wildlife.", difficulty: 1, synonyms: ["plentiful", "rich"] },
                { word: "analyze", definition: "examine in detail", example: "Scientists analyze data to find patterns.", difficulty: 1, synonyms: ["study", "examine"] },
                { word: "conclude", definition: "reach a decision or end", example: "Based on the evidence, we can conclude the theory is correct.", difficulty: 1, synonyms: ["finish", "end"] },
                { word: "demonstrate", definition: "show clearly", example: "The experiment will demonstrate the chemical reaction.", difficulty: 1, synonyms: ["show", "prove"] },
                { word: "evaluate", definition: "assess or judge", example: "Teachers evaluate student performance regularly.", difficulty: 1, synonyms: ["assess", "judge"] }
            ],
            '8': [
                { word: "comprehensive", definition: "complete and thorough", example: "The report provides a comprehensive analysis.", difficulty: 2, synonyms: ["complete", "thorough"] },
                { word: "contradict", definition: "deny the truth of", example: "His statement contradicts the evidence.", difficulty: 2, synonyms: ["oppose", "deny"] },
                { word: "hypothesis", definition: "a proposed explanation", example: "The scientist's hypothesis was proven correct.", difficulty: 2, synonyms: ["theory", "assumption"] },
                { word: "methodology", definition: "system of methods used", example: "The research methodology was sound.", difficulty: 2, synonyms: ["approach", "method"] },
                { word: "synthesize", definition: "combine to form a whole", example: "Students must synthesize information from multiple sources.", difficulty: 2, synonyms: ["combine", "integrate"] }
            ],
            '9': [
                { word: "ambiguous", definition: "open to more than one interpretation", example: "The politician's statement was deliberately ambiguous.", difficulty: 3, synonyms: ["unclear", "vague"] },
                { word: "paradigm", definition: "a typical example or pattern", example: "This study represents a new paradigm in research.", difficulty: 3, synonyms: ["model", "pattern"] },
                { word: "phenomenon", definition: "a fact or situation observed", example: "Climate change is a global phenomenon.", difficulty: 3, synonyms: ["occurrence", "event"] },
                { word: "substantiate", definition: "provide evidence to support", example: "The lawyer must substantiate the claims.", difficulty: 3, synonyms: ["prove", "confirm"] },
                { word: "theoretical", definition: "concerned with theory", example: "The theoretical framework guides the research.", difficulty: 3, synonyms: ["conceptual", "abstract"] }
            ],
            '10': [
                { word: "epistemology", definition: "theory of knowledge", example: "Epistemology examines how we acquire knowledge.", difficulty: 4, synonyms: ["knowledge theory"] },
                { word: "metamorphosis", definition: "a change of form or nature", example: "The butterfly's metamorphosis is remarkable.", difficulty: 4, synonyms: ["transformation", "change"] },
                { word: "paradoxical", definition: "seemingly contradictory", example: "The situation is paradoxical but true.", difficulty: 4, synonyms: ["contradictory", "ironic"] },
                { word: "sophisticated", definition: "complex and refined", example: "The software uses sophisticated algorithms.", difficulty: 4, synonyms: ["advanced", "complex"] },
                { word: "ubiquitous", definition: "present everywhere", example: "Smartphones are ubiquitous in modern society.", difficulty: 4, synonyms: ["omnipresent", "widespread"] }
            ]
        };
        
        return gradeData[this.currentGrade] || [];
    }
    
    updateGradeDisplay() {
        const gradeTitle = document.querySelector('.learn-header h2');
        if (gradeTitle) {
            gradeTitle.textContent = `Learn Vocabulary - Grade ${this.currentGrade}`;
        }
    }
    
    loadVocabularyList() {
        const vocabularyList = document.getElementById('vocabulary-list');
        if (!vocabularyList) return;
        
        this.filterVocabularyList();
    }
    
    filterVocabularyList() {
        const vocabularyList = document.getElementById('vocabulary-list');
        if (!vocabularyList) return;
        
        const searchTerm = document.getElementById('vocab-search')?.value.toLowerCase() || '';
        const difficultyFilter = document.getElementById('difficulty-filter')?.value || '';
        
        // Filter data
        this.filteredData = this.vocabularyData.filter(word => {
            const matchesSearch = !searchTerm || 
                word.word.toLowerCase().includes(searchTerm) ||
                word.definition.toLowerCase().includes(searchTerm) ||
                word.example.toLowerCase().includes(searchTerm) ||
                (word.synonyms && word.synonyms.some(syn => syn.toLowerCase().includes(searchTerm)));
            
            const matchesDifficulty = !difficultyFilter || word.difficulty === parseInt(difficultyFilter);
            
            return matchesSearch && matchesDifficulty;
        });
        
        this.displayVocabularyList();
    }
    
    displayVocabularyList() {
        const vocabularyList = document.getElementById('vocabulary-list');
        if (!vocabularyList) return;
        
        vocabularyList.innerHTML = '';
        
        if (this.filteredData.length === 0) {
            vocabularyList.innerHTML = `
                <div class="no-results">
                    <h3>No vocabulary words found</h3>
                    <p>Try adjusting your search terms or difficulty filter.</p>
                </div>
            `;
            return;
        }
        
        // Group words by difficulty
        const groupedWords = this.groupWordsByDifficulty(this.filteredData);
        
        Object.keys(groupedWords).forEach(difficulty => {
            if (groupedWords[difficulty].length === 0) return;
            
            const difficultySection = document.createElement('div');
            difficultySection.className = 'difficulty-section';
            difficultySection.innerHTML = `
                <h3 class="difficulty-title">${difficulty}</h3>
                <div class="vocab-grid">
                    ${groupedWords[difficulty].map(word => this.createVocabCard(word)).join('')}
                </div>
            `;
            vocabularyList.appendChild(difficultySection);
        });
    }
    
    createVocabCard(word) {
        return `
            <div class="vocab-card" data-word="${word.word}">
                <div class="vocab-header">
                    <h4 class="vocab-word">${word.word}</h4>
                    <span class="difficulty-badge difficulty-${word.difficulty}">Level ${word.difficulty}</span>
                </div>
                <div class="vocab-content">
                    <p class="vocab-definition">${word.definition}</p>
                    <p class="vocab-example"><strong>Example:</strong> ${word.example}</p>
                    ${word.synonyms ? `<p class="vocab-synonyms"><strong>Synonyms:</strong> ${word.synonyms.join(', ')}</p>` : ''}
                </div>
                <div class="vocab-actions">
                    <button class="btn-favorite" onclick="learnMenu.toggleFavorite('${word.word}')">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button class="btn-practice" onclick="learnMenu.practiceWord('${word.word}')">
                        <i class="fas fa-dumbbell"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    groupWordsByDifficulty(words) {
        const groups = {
            'Beginner': [],
            'Intermediate': [],
            'Advanced': [],
            'Expert': []
        };
        
        words.forEach(word => {
            if (word.difficulty <= 1) {
                groups['Beginner'].push(word);
            } else if (word.difficulty <= 2) {
                groups['Intermediate'].push(word);
            } else if (word.difficulty <= 3) {
                groups['Advanced'].push(word);
            } else {
                groups['Expert'].push(word);
            }
        });
        
        return groups;
    }
    
    selectGrade(grade) {
        // Add loading state
        const gradeBtn = document.querySelector(`[data-grade="${grade}"]`);
        if (gradeBtn) {
            gradeBtn.classList.add('loading');
        }
        
        // Redirect to the grade-specific vocabulary page
        setTimeout(() => {
            window.location.href = `vocabulary.php?grade=${grade}`;
        }, 500);
    }
    
    toggleFavorite(word) {
        // Toggle favorite status
        const card = document.querySelector(`[data-word="${word}"]`);
        const favoriteBtn = card.querySelector('.btn-favorite');
        
        if (favoriteBtn.classList.contains('favorited')) {
            favoriteBtn.classList.remove('favorited');
            this.showToast('Removed from favorites', 'info');
        } else {
            favoriteBtn.classList.add('favorited');
            this.showToast('Added to favorites', 'success');
        }
    }
    
    practiceWord(word) {
        // Start practice session for specific word
        this.showToast(`Starting practice session for "${word}"`, 'info');
        // TODO: Implement practice functionality
    }
    
    goBack() {
        window.location.href = 'learn.php';
    }
    
    showToast(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remove toast
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize the learn vocabulary menu when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.learnMenu = new LearnVocabMenu();
});

// Global functions for backward compatibility
function selectGrade(grade) {
    if (window.learnMenu) {
        window.learnMenu.selectGrade(grade);
    }
}

function goToMainMenu() {
    window.location.href = '../../../menu.php';
}
