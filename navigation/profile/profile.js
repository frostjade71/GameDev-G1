// This file is no longer used - form handling moved to inline JavaScript in profile.php
// Keeping only the non-form functionality

document.addEventListener('DOMContentLoaded', () => {
    // Skip form submission handling - now done inline in profile.php
    const settingsForm = null; // Disable form handling
    if (settingsForm) {
        settingsForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const aboutMeValue = document.querySelector('textarea[name="about_me"]').value;
            
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('username', document.querySelector('input[name="username"]').value);
            formData.append('email', document.querySelector('input[name="email"]').value);
            formData.append('about_me', aboutMeValue);
            
            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    
                    // Update the username in the header (show only first name)
                    const usernameElements = document.querySelectorAll('.username');
                    usernameElements.forEach(el => {
                        el.textContent = data.username.split(' ')[0];
                    });
                    
                    // Update the about me text in the header using the server response
                    const aboutMeElement = document.querySelector('.about-me-text');
                    if (aboutMeElement) {
                        const newText = (data.about_me && data.about_me.trim() !== '') ? data.about_me : 'Tell us something about yourself...';
                        aboutMeElement.textContent = newText;
                    }
                    
                    // Also update the global userData for consistency
                    if (window.userData) {
                        window.userData.username = data.username;
                        window.userData.aboutMe = data.about_me;
                    }
                    
                    // If the About Me element didn't update, reload the page as fallback
                    setTimeout(() => {
                        const currentText = document.querySelector('.about-me-text')?.textContent;
                        if (data.about_me && data.about_me.trim() !== '' && currentText === 'Tell us something about yourself...') {
                            window.location.reload();
                        }
                    }, 100);
                } else {
                    showToast('Error: ' + data.message);
                }
            })
            .catch(error => {
                showToast('Error updating profile');
            });
        });
    }

    // Handle avatar change button
    const changeAvatarBtn = document.querySelector('.change-avatar-btn');
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', () => {
            // This would typically open a file picker
            showToast('Avatar change feature coming soon!');
        });
    }

    // Add hover effect to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.borderColor = 'rgba(0, 255, 135, 0.8)';
            card.style.transition = 'all 0.3s ease';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.borderColor = 'rgba(96, 239, 255, 0.2)';
        });
    });
});
