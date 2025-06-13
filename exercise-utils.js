// Utility functions for handling exercise results

// Get exercise ID from URL
function getExerciseId() {
    const pathParts = window.location.pathname.split('/');
    const exerciseName = pathParts[pathParts.length - 1].replace('.html', '');
    return exerciseName;
}

// Calculate total time spent on exercise
function calculateTotalTime(startTime) {
    if (!startTime) return 0;
    return Math.floor((Date.now() - startTime) / 1000);
}

// Save exercise results to server
async function saveExerciseResults(exerciseData) {
    try {
        const response = await fetch('save_exercise_result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                exercise_id: getExerciseId(),
                exercise_type: 'grammar',
                level: 'beginner',
                ...exerciseData
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data.success) {
            console.error('Error saving exercise results:', data.error);
            return false;
        }
        return true;
    } catch (error) {
        console.error('Error submitting exercise results:', error);
        return false;
    }
}

// Save multiple choice test results
async function saveMultipleChoiceResults(questions, answers, startTime) {
    const correctAnswers = questions.reduce((count, question, index) => {
        return count + (answers[index] === question.correctAnswer ? 1 : 0);
    }, 0);

    const answersData = questions.map((question, index) => ({
        question_id: index + 1,
        answer: answers[index] !== null ? question.options[answers[index]] : null,
        is_correct: answers[index] === question.correctAnswer,
        time_spent: question.timeSpent || 0
    }));

    return await saveExerciseResults({
        answers: answersData,
        total_questions: questions.length,
        correct_answers: correctAnswers,
        time_taken: calculateTotalTime(startTime)
    });
}

// Save essay results
async function saveEssayResults(essayText, topic, startTime) {
    return await saveExerciseResults({
        essay_text: essayText,
        topic: topic,
        time_taken: calculateTotalTime(startTime),
        type: 'essay'
    });
}

// Save introduction results
async function saveIntroductionResults(introductionText, startTime) {
    return await saveExerciseResults({
        introduction_text: introductionText,
        time_taken: calculateTotalTime(startTime),
        type: 'introduction'
    });
}

// Show success notification
function showSuccessNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Show error notification
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }

    .notification.success {
        background-color: #10b981;
    }

    .notification.error {
        background-color: #ef4444;
    }

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
`;
document.head.appendChild(style); 