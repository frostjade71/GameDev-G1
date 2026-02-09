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
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initialize display
        this.updateGradeDisplay();
    }
    
    // ... [existing mobile optimizations] ...

    setupEventListeners() {
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

    updateGradeDisplay() {
        const gradeTitle = document.querySelector('.learn-header h2');
        if (gradeTitle) {
            gradeTitle.textContent = `Learn Vocabulary - Grade ${this.currentGrade}`;
        }
    }

    // Removed obsolete loadVocabularyData, filterVocabularyList, displayVocabularyList methods

    selectGrade(grade) {
        // Redirect to the grade-specific lessons page
        window.location.href = `grade${grade}.php`;
    }
    
    goBack() {
        window.location.href = '../index.php';
    }
    
    showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        }
    }
}

// Initialize the learn vocabulary menu when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.learnMenu = new LearnVocabMenu();
});

function goToMainMenu() {
    window.location.href = '../index.php';
}
