async function saveTestResult(testData) {
    const sessionToken = localStorage.getItem('userSessionToken');
    if (!sessionToken) {
        throw new Error('No session token found. Please log in to save your results.');
    }

    const resultData = {
        topic_id: testData.topicId,
        score: testData.score,
        max_score: testData.maxScore,
        time_spent: testData.timeSpent,
        errors: testData.errors.map(error => ({
            question_id: error.questionId,
            user_answer: error.userAnswer,
            correct_answer: error.correctAnswer,
            question_text: error.questionText
        }))
    };

    try {
        const response = await fetch('../api/save_test_result.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Session-Token': sessionToken
            },
            body: JSON.stringify(resultData)
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server response:', errorText);
            throw new Error(`Ошибка сервера: ${response.status} ${response.statusText}`);
        }

        let data;
        try {
            data = await response.json();
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            throw new Error('Сервер вернул некорректный ответ');
        }

        if (!data.success) {
            if (data.error === 'User not authenticated') {
                localStorage.removeItem('userSessionToken');
                window.location.href = '../login.html';
                return;
            }
            throw new Error(data.error || 'Не удалось сохранить результат теста');
        }

        return data;
    } catch (error) {
        console.error('Error saving test result:', error);
        throw error;
    }
}

async function finishTest() {
    const testData = {
        topicId: getTopicId(),
        score: calculateScore().correctAnswers,
        maxScore: questions.length,
        timeSpent: calculateTotalTime(),
        errors: getTestErrors()
    };

    try {
        await saveTestResult(testData);
        displayResult(testData);
    } catch (error) {
        console.error('Error finishing test:', error);
        alert('Failed to save test result. Please try again.');
    }
}

function getTestErrors() {
    return questions.map((question, index) => {
        const userAnswer = getUserAnswer(index);
        const isCorrect = userAnswer === question.correctAnswer;
        
        return {
            questionId: question.id,
            userAnswer: userAnswer,
            correctAnswer: question.correctAnswer,
            questionText: question.question,
            isCorrect: isCorrect
        };
    }).filter(error => !error.isCorrect);
}

function getTopicId() {
    const urlParams = new URLSearchParams(window.location.search);
    const topicId = urlParams.get('topic_id');
    if (!topicId) {
        throw new Error('Topic ID not found');
    }
    return topicId;
} 