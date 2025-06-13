class ExerciseHandler {
    constructor() {
        this.startTime = null;
        this.exerciseData = null;
        this.currentExercise = null;
    }

    // Инициализация упражнения
    initExercise(exerciseId, exerciseType, level, exerciseData) {
        this.startTime = Date.now();
        this.exerciseData = exerciseData;
        this.currentExercise = {
            id: exerciseId,
            type: exerciseType,
            level: level
        };
    }

    // Сохранение результата упражнения
    async saveResult(score, maxScore, details = null) {
        if (!this.currentExercise || !this.startTime) {
            throw new Error('Exercise not initialized');
        }

        const timeSpent = Math.floor((Date.now() - this.startTime) / 1000); // в секундах

        const resultData = {
            exercise_id: this.currentExercise.id,
            exercise_type: this.currentExercise.type,
            level: this.currentExercise.level,
            score: score,
            max_score: maxScore,
            time_spent: timeSpent,
            details: details
        };

        try {
            const response = await fetch('save_exercise_result.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(resultData)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to save exercise result');
            }

            const result = await response.json();
            return result;

        } catch (error) {
            console.error('Error saving exercise result:', error);
            throw error;
        }
    }

    // Получение прогресса пользователя
    async getProgress(exerciseType, level) {
        try {
            const response = await fetch(`get_exercise_progress.php?type=${exerciseType}&level=${level}`);
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Failed to get progress');
            }

            return await response.json();

        } catch (error) {
            console.error('Error getting progress:', error);
            throw error;
        }
    }

    // Отображение результата упражнения
    displayResult(result, container) {
        const score = (result.score / result.max_score) * 100;
        const scoreClass = score >= 70 ? 'high' : score >= 50 ? 'medium' : 'low';
        
        const html = `
            <div class="result-container">
                <div class="result-summary">
                    <h3>Test Results</h3>
                    <div class="score-display ${scoreClass}">
                        ${score.toFixed(1)}%
                    </div>
                    <div class="result-details">
                        <p>Correct answers: ${result.score} out of ${result.max_score}</p>
                        <p>Time spent: ${Math.floor(result.time_spent / 60)} minutes</p>
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }
}

// Экспортируем класс для использования в других файлах
window.ExerciseHandler = ExerciseHandler; 