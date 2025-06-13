// Authentication functionality

document.addEventListener('DOMContentLoaded', function() {
    // Login form handling
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginButton = document.getElementById('loginButton');
            const errorMessage = document.getElementById('errorMessage');
            
            // Validate inputs
            if (!email || !password) {
                errorMessage.textContent = 'Please fill in all fields';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Show loading state
            loginButton.textContent = 'Signing in...';
            loginButton.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // In a real app, you would make an API call to your backend
                // This is just a simulation
                
                // Create mock user
                const user = {
                    id: 'user-123',
                    email: email,
                    username: email.split('@')[0]
                };
                
                // Store user in localStorage
                localStorage.setItem('user', JSON.stringify(user));
                
                // Redirect to home page
                window.location.href = 'index.html';
            }, 1000);
        });
    }
    
    // Register form handling
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const registerButton = document.getElementById('registerButton');
            const errorMessage = document.getElementById('errorMessage');
            
            // Validate inputs
            if (!username || !email || !password || !confirmPassword) {
                errorMessage.textContent = 'Please fill in all fields';
                errorMessage.style.display = 'block';
                return;
            }
            
            if (password !== confirmPassword) {
                errorMessage.textContent = 'Passwords do not match';
                errorMessage.style.display = 'block';
                return;
            }
            
            // Show loading state
            registerButton.textContent = 'Signing up...';
            registerButton.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // In a real app, you would make an API call to your backend
                // This is just a simulation
                
                // Create mock user
                const user = {
                    id: 'user-' + Math.floor(Math.random() * 1000),
                    email: email,
                    username: username
                };
                
                // Store user in localStorage
                localStorage.setItem('user', JSON.stringify(user));
                
                // Redirect to home page
                window.location.href = 'index.html';
            }, 1000);
        });
    }
});