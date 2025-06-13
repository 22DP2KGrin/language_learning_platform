// Exercises page functionality

document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                tabButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Hide all tab contents
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show the corresponding tab content
                const level = this.getAttribute('data-level');
                document.getElementById(`${level}-content`).classList.add('active');
            });
        });
    }
    
    // Load exercises data
    loadExercises();
});

// Load exercises data
function loadExercises() {
    // Get language from URL
    const pathParts = window.location.pathname.split('/');
    const language = pathParts[pathParts.length - 1].split('.')[0];
    
    // Mock exercises data
    const exercisesData = generateExercisesForLanguage(language);
    
    // Populate exercise cards
    populateExerciseCards('beginner', exercisesData.Beginner);
    populateExerciseCards('intermediate', exercisesData.Intermediate);
    populateExerciseCards('advanced', exercisesData.Advanced);
}

// Populate exercise cards
function populateExerciseCards(level, exercises) {
    const container = document.getElementById(`${level}-exercises`);
    if (!container) return;
    
    let html = '';
    
    exercises.forEach(exercise => {
        html += `
            <div class="exercise-card">
                <div class="exercise-header">
                    <div>
                        <h3 class="exercise-title">${exercise.title}</h3>
                    </div>
                    <span class="exercise-badge ${exercise.completed ? 'completed' : ''}">${exercise.completed ? 'Completed' : 'New'}</span>
                </div>
                <div class="exercise-content">
                    <p class="exercise-description">${exercise.description}</p>
                    <div class="exercise-meta">
                        <div class="exercise-type">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                            ${getExerciseTypeTitle(exercise.type)}
                        </div>
                        <div class="exercise-stats">
                            ${exercise.questions} questions • ${exercise.estimatedTime}
                        </div>
                    </div>
                </div>
                <div class="exercise-footer">
                    <a href="${getExerciseUrl(exercise.id)}" class="btn btn-primary btn-block">
                        ${exercise.completed ? 'Review Exercise' : 'Start Exercise'}
                    </a>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Get exercise type title
function getExerciseTypeTitle(type) {
    const types = {
        vocabulary: 'Vocabulary',
        grammar: 'Grammar',
        reading: 'Reading',
        writing: 'Writing',
        listening: 'Listening',
        speaking: 'Speaking',
        quiz: 'Quiz',
        games: 'Games'
    };
    
    return types[type] || 'Exercise';
}

// Get exercise URL
function getExerciseUrl(id) {
    // Extract language from ID
    const language = id.split('-')[0];
    return `${language}/${id}.html`;
}

// Generate exercises for a language
function generateExercisesForLanguage(language) {
    const baseExercises = {
        Beginner: [
            {
                id: `${language}-beg-1`,
                title: "Basic Vocabulary",
                type: "vocabulary",
                description: "Learn essential words for everyday conversations",
                questions: 20,
                estimatedTime: "15 min",
                completed: false
            },
            {
                id: `${language}-beg-2`,
                title: "Present Simple",
                type: "grammar",
                description: "Master the present simple tense",
                questions: 15,
                estimatedTime: "20 min",
                completed: false
            },
            {
                id: `${language}-beg-3`,
                title: "My Daily Routine",
                type: "reading",
                description: "Practice reading about daily activities",
                questions: 10,
                estimatedTime: "15 min",
                completed: false
            }
        ],
        Intermediate: [
            {
                id: `${language}-int-1`,
                title: "Past Tense",
                type: "grammar",
                description: "Practice using past tense forms",
                questions: 20,
                estimatedTime: "25 min",
                completed: false
            },
            {
                id: `${language}-int-2`,
                title: "Business Communication",
                type: "vocabulary",
                description: "Learn business-related vocabulary",
                questions: 25,
                estimatedTime: "30 min",
                completed: false
            }
        ],
        Advanced: [
            {
                id: `${language}-adv-1`,
                title: "Complex Grammar",
                type: "grammar",
                description: "Master advanced grammatical structures",
                questions: 20,
                estimatedTime: "25 min",
                completed: false
            },
            {
                id: `${language}-adv-2`,
                title: "Academic Writing",
                type: "writing",
                description: "Practice academic writing skills",
                questions: 5,
                estimatedTime: "40 min",
                completed: false
            }
        ]
    };

    // Add language-specific exercises
    if (language === "english") {
        baseExercises.Intermediate.push({
            id: `${language}-int-3`,
            title: "Phrasal Verbs",
            type: "vocabulary",
            description: "Master common English phrasal verbs",
            questions: 25,
            estimatedTime: "20 min",
            completed: false
        });
    }

    return baseExercises;
}