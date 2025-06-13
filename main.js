// Main JavaScript file for common functionality

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    
    if (themeToggle) {
        // Check if user has a saved preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
            themeToggle.checked = true;
        }
        
        // Toggle theme when switch is clicked
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        });
    }
    
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user'));
    
    // Update navigation based on authentication status
    updateNavigation(user);
});

// Update navigation based on authentication status
function updateNavigation(user) {
    const mainNav = document.querySelector('.main-nav');
    
    if (!mainNav) return;
    
    if (user) {
        // User is logged in
        const authLinks = `
            <a href="profile.html" class="nav-link">My Profile</a>
            <button id="signOutBtn" class="nav-link register-btn">Sign Out</button>
        `;
        
        // Replace login/register links with profile and sign out
        const loginLink = mainNav.querySelector('.login-btn');
        const registerLink = mainNav.querySelector('.register-btn');
        
        if (loginLink && registerLink) {
            loginLink.remove();
            registerLink.remove();
            
            // Append new links
            mainNav.innerHTML += authLinks;
            
            // Add event listener to sign out button
            const signOutBtn = document.getElementById('signOutBtn');
            if (signOutBtn) {
                signOutBtn.addEventListener('click', function() {
                    // Remove user from localStorage
                    localStorage.removeItem('user');
                    // Redirect to home page
                    window.location.href = 'index.html';
                });
            }
        }
    }
}