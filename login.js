document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');

    if (loginForm) {
        // Use absolute URL for MAMP
        const baseUrl = 'http://localhost:8888';
        
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Clear previous messages
            if (errorMessage) errorMessage.textContent = '';
            if (successMessage) successMessage.textContent = '';
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            try {
                console.log('Attempting login to:', `${baseUrl}/api/login_process.php`);
                const response = await fetch(`${baseUrl}/api/login_process.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include', // Include cookies in the request
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });
                
                console.log('Login response status:', response.status);
                console.log('Login response headers:', Object.fromEntries(response.headers.entries()));
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Failed to parse response as JSON:', e);
                    throw new Error('Server returned invalid JSON response');
                }
                
                if (data.success) {
                    // Store session token
                    localStorage.setItem('userSessionToken', data.session.session_token);
                    
                    // Store user data
                    localStorage.setItem('user', JSON.stringify(data.user));
                    
                    if (successMessage) {
                        successMessage.textContent = data.message;
                    }
                    
                    // Redirect to main page after successful login
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 1000);
                } else {
                    if (errorMessage) {
                        errorMessage.textContent = data.message || 'Login failed. Please try again.';
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
                if (errorMessage) {
                    errorMessage.textContent = 'An error occurred during login. Please try again.';
                }
            }
        });
    }
}); 