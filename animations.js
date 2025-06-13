// Animations for the home page

document.addEventListener('DOMContentLoaded', function() {
    // Animated background text
    const animatedBgText = document.getElementById('animatedBgText');
    if (animatedBgText) {
        const words = ["Learn", "Explore", "Grow", "Connect", "Achieve"];
        let currentIndex = 0;
        
        // Change word every 3 seconds
        setInterval(() => {
            // Fade out
            animatedBgText.style.opacity = '0';
            animatedBgText.style.transform = 'translateY(-50px)';
            
            setTimeout(() => {
                // Change word
                currentIndex = (currentIndex + 1) % words.length;
                animatedBgText.textContent = words[currentIndex];
                
                // Fade in
                animatedBgText.style.opacity = '0.1';
                animatedBgText.style.transform = 'translateY(0)';
            }, 500);
        }, 3000);
    }
    
    // Animate hero content
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        // Set initial state
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(30px)';
        
        // Animate in after a short delay
        setTimeout(() => {
            heroContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 300);
    }
    
    // Animate feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    if (featureCards.length > 0) {
        featureCards.forEach((card, index) => {
            // Set initial state
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            
            // Animate in with staggered delay
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 300 + (index * 100));
        });
    }
    
    // Animate language cards
    const languageCards = document.querySelectorAll('.language-card');
    if (languageCards.length > 0) {
        languageCards.forEach((card, index) => {
            // Set initial state
            card.style.opacity = '0';
            card.style.transform = 'scale(0.8)';
            
            // Animate in with staggered delay
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
            }, 300 + (index * 100));
        });
    }
});