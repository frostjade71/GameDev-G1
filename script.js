document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu handling
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const sidebar = document.querySelector('.sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            playClickSound();
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }

    // Update greeting based on time of day
    function updateGreeting() {
        const greeting = document.querySelector('.greeting');
        if (greeting) {
            const hour = new Date().getHours();
            if (hour >= 0 && hour < 12) {
                greeting.textContent = "Good Morning";
            } else if (hour >= 12 && hour < 18) {
                greeting.textContent = "Good Afternoon";
            } else {
                greeting.textContent = "Good Evening";
            }
        }
    }

    // Initial greeting update
    updateGreeting();
    
    // Update greeting every minute
    setInterval(updateGreeting, 60000);
    // Game Selection Carousel Logic
    const carouselTrack = document.querySelector('.carousel-track');
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselTrack && carouselContainer) {
        const cards = Array.from(carouselTrack.children);
        const prevButton = document.querySelector('.carousel-button.prev');
        const nextButton = document.querySelector('.carousel-button.next');
        const dotsContainer = document.querySelector('.carousel-dots');
        
        let currentIndex = 0;
        
        // Create dots
        cards.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('carousel-dot');
            if (index === 0) dot.classList.add('active');
            dotsContainer.appendChild(dot);
            
            dot.addEventListener('click', () => {
                moveToSlide(index);
            });
        });
        
        const dots = Array.from(dotsContainer.children);
        
        // Update dots
        const updateDots = (index) => {
            dots.forEach(dot => dot.classList.remove('active'));
            dots[index].classList.add('active');
        };
        
        // Move to specific slide
        const moveToSlide = (index) => {
            const slideWidth = cards[0].getBoundingClientRect().width;
            const gap = 32; // 2rem gap
            carouselTrack.style.transform = `translateX(-${index * (slideWidth + gap)}px)`;
            currentIndex = index;
            updateDots(index);
            
            // Update button states
            prevButton.style.opacity = index === 0 ? '0.5' : '1';
            nextButton.style.opacity = index === cards.length - 1 ? '0.5' : '1';
        };
        
        // Button click handlers
        prevButton.addEventListener('click', () => {
            if (currentIndex > 0) {
                moveToSlide(currentIndex - 1);
                playClickSound();
            }
        });
        
        nextButton.addEventListener('click', () => {
            if (currentIndex < cards.length - 1) {
                moveToSlide(currentIndex + 1);
                playClickSound();
            }
        });
        
        // Initialize button states
        prevButton.style.opacity = '0.5';
    }
    // Handle loading screen
    const loadingScreen = document.getElementById('loading-screen');
    const pressStart = document.querySelector('.press-start');

    // Sidebar active link handling
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.id === 'logout-btn') {
                e.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    // Add logout logic here
                    window.location.href = 'index.html';
                }
            }
        });
    });

    // Notification badge handling
    const notificationIcon = document.querySelector('.notification-icon');
    let notificationCount = 0;
    const notificationBadge = document.querySelector('.notification-badge');
    
    notificationIcon.addEventListener('click', () => {
        // Add notification panel logic here
        showToast('No new notifications');
    });

    // Profile icon handling
    const profileIcon = document.querySelector('.profile-icon');
    profileIcon.addEventListener('click', () => {
        window.location.href = 'profile.html';
    });
    
    if (loadingScreen) {
        // Check if we're coming from another page or it's a refresh
        const urlParams = new URLSearchParams(window.location.search);
        const lastPage = sessionStorage.getItem('lastPage');
        const isRefresh = window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_RELOAD;
        
        if (urlParams.get('from') === 'selection' || lastPage || isRefresh) {
            // Skip loading screen if coming from another page or if it's a refresh
            loadingScreen.style.display = 'none';
            sessionStorage.setItem('lastPage', window.location.pathname);
        } else {
            // Show loading screen and handle loading completion
            setTimeout(() => {
                if (loadingScreen && pressStart) {
                    // Show the "press start" message
                    pressStart.classList.remove('hidden');
                    
                    // Add click event listener to the loading screen
                    const handleStart = () => {
                        loadingScreen.style.opacity = '0';
                        setTimeout(() => {
                            if (loadingScreen) {
                                loadingScreen.style.display = 'none';
                                
                                // Remove the event listener
                                loadingScreen.removeEventListener('click', handleStart);
                                document.removeEventListener('keydown', handleStart);
                            }
                        }, 1000);
                    };
                    
                    // Listen for both click and keydown events
                    loadingScreen.addEventListener('click', handleStart);
                    document.addEventListener('keydown', handleStart);
                }
            }, 6000);
        }
    }

    // Add entry animation for game selection page
    const menuContainer = document.querySelector('.menu-container');
    if (menuContainer) {
        // Force a reflow before adding the slide-in class
        void menuContainer.offsetWidth;
        
        // Add slide-in class to trigger the animation
        menuContainer.classList.add('slide-in');
    }

    const buttons = document.querySelectorAll('.menu-button, .play-button, .back-button');
    
    buttons.forEach(button => {
        // Add click sound and visual effect
        button.addEventListener('click', () => {
            playClickSound();
            addClickEffect(button);
            
            // Handle navigation
            if (button.textContent === 'Play') {
                window.location.href = 'game-selection.html';
            } else if (button.textContent === 'Credits') {
                window.location.href = 'credits.html';
            } else if (button.textContent === 'Settings') {
                window.location.href = 'settings/settings.html';
            } else if (button.textContent === 'High Scores') {
                window.location.href = 'highscore/highscore.html';
            }
        });
    });

    // Handle back link/button
    const backElement = document.querySelector('.back-button, .back-link');
    if (backElement) {
        backElement.addEventListener('click', (event) => {
            playClickSound();
            window.location.href = 'index.html';
        });
    }

    // Handle game cards
    const gameCards = document.querySelectorAll('.game-card');

    if (gameCards) {
        let currentBackground = null;
        
        gameCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                const gameType = card.dataset.game;
                const body = document.body;
                
                // Remove previous background class if exists
                if (currentBackground) {
                    body.classList.remove('hover-' + currentBackground);
                }
                
                // Set new background
                body.classList.add('hover-' + gameType);
                currentBackground = gameType;
            });

            card.addEventListener('mouseleave', () => {
                const body = document.body;
                if (currentBackground) {
                    body.classList.remove('hover-' + currentBackground);
                    currentBackground = null;
                }
            });

            card.addEventListener('click', () => {
                const gameType = card.dataset.game;
                const menuContainer = document.querySelector('.menu-container');
                
                if (gameType === 'grow-word' || gameType === 'letterscapes') {
                    showToast('Under Development');
                }
                playClickSound(); // Play click sound instead of game open sound
            });
        });
    }

    // Toast notification function with sound and blur overlay
    window.showToast = function(message) {
        const toast = document.getElementById('toast');
        const overlay = document.querySelector('.toast-overlay');
        
        if (toast && overlay) {
            // Play toast notification sound
            const toastSound = new Audio('assets/sounds/toast/toastnotifwarn.mp3');
            toastSound.volume = 0.5; // Set volume to 50%
            toastSound.play().catch(error => {
                console.log('Error playing toast sound:', error);
            });
            
            // Show overlay and toast
            overlay.classList.add('show');
            toast.textContent = message;
            toast.classList.remove('hide');
            toast.classList.add('show');
            
            // Hide the toast and overlay after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                toast.classList.add('hide');
                overlay.classList.remove('show');
            }, 1500);
        } else {
            console.error('Toast or overlay elements not found');
        }
    }
});

function playClickSound() {
    const clickSound = new Audio('assets/sounds/clicks/mixkit-stapling-paper-2995.wav');
    clickSound.play();
}

function addClickEffect(button) {
    button.style.transform = 'scale(0.95)';
    setTimeout(() => {
        button.style.transform = 'scale(1)';
    }, 100);
}
