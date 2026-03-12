<?php
// $items should be passed from the shortcode
// $atts['timer'] contains timer seconds
$TIMER_DURATION = isset($atts['timer']) ? (int)$atts['timer'] : 15;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
.answer-status-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.answer-btn {
    min-width: 160px;
    position: relative;
    border: 3px solid transparent;
    font-weight: 500;
}

.answer-btn.selected {
    border-color: #212529;
    border-width: 4px;
    transform: scale(1.02); 
    font-weight: 700;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.answer-btn.btn-danger.selected {
    background-color: #dc3545;
    color: white;
}

.answer-btn.btn-warning.selected {
    background-color: #ffc107;
    color: #212529;
}

.answer-btn.btn-success.selected {
    background-color: #28a745;
    color: white;
}

.answer-btn.selected::after {
    content: '✓';
    position: absolute;
    top: -8px;
    right: -8px;
    background: #212529;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    z-index: 10;
}

.score-display {
    font-size: 2.5rem;
    font-weight: bold;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    margin: 20px 0;
    /* transition: all 0.3s ease; */
}

.score-excellent {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
}

.score-good {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: white;
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
}

.score-poor {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

#nextButton:disabled:hover {
    cursor: not-allowed;
}

.tooltip-custom {
    position: relative;
}

.tooltip-wrapper.disabled::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background: #212529;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 14px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 1000;
}

.tooltip-wrapper.disabled::after {
    content: '';
    position: absolute;
    bottom: 115%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: #212529;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
}

.tooltip-wrapper.disabled:hover::before,
.tooltip-wrapper.disabled:hover::after {
    opacity: 1;
    visibility: visible;
}

.tooltip-wrapper {
    position: relative;
    display: inline-block;
}
</style>

<div id="quizContainer">
<?php 
$items_count = count($items);
foreach ($items as $key => $post_id): 
    $post = get_post($post_id);
    if (!$post) continue; // safety
    $q_question = get_post_meta($post_id, '_interview_question', true);

    $q_content = apply_filters('the_content', $post->post_content);
?>
    <div class="question" style="display:none">
<?php /*         <h1><?= ($key + 1) ?>. <?= $q_question ?></h1> */ ?>	
		<div class="question-counter"></div>
        <h1><?= $q_question ?></h1>
      
        <div class="col-12 mt-5">
            <div class="accordion_custom accordion accordion-flush border border-secondary" id="#accordionFlush_<?php echo $key; ?>">
                <div class="accordion-item">
                    <h2 class="accordion-header text-center">
                        <button class="accordion-button collapsed text-center" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse_<?php echo $key; ?>" aria-expanded="false" aria-controls="flush-collapse_<?php echo $key; ?>">
                            Answer
                        </button>
                    </h2>
                    <div id="flush-collapse_<?php echo $key; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionFlush_<?php echo $key; ?>">
                        <div class="accordion-body  text-start fs-4"><?= $q_content ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mt-3">
            <div class="answer-status-buttons">
                <button class="btn answer-btn btn-danger" data-status="didn't-answer" data-question-id="<?php echo $key; ?>">
                    <i class="fas fa-times"></i> Didn't answer
                </button>
                <button class="btn answer-btn btn-warning" data-status="somewhat-answered" data-question-id="<?php echo $key; ?>">
                    <i class="fas fa-minus"></i> Somewhat answered
                </button>
                <button class="btn answer-btn btn-success" data-status="answered" data-question-id="<?php echo $key; ?>">
                    <i class="fas fa-check"></i> Answered
                </button>
            </div>
        </div>

    </div>
<?php endforeach; ?>

</div>

<div class="mt-3 text-center">
    <div id="timer" style="font-size:24px; font-weight:bold; margin-bottom:10px; "></div>
    <div style="display: flex; justify-content: center; flex-wrap: wrap;
    gap: 5px;">
        <div><button id="prevButton" class="btn btn-secondary mx-2">Prev question</button></div>
        <div class="tooltip-wrapper" data-tooltip="Please select an answer option to continue"><button id="nextButton" class="btn btn-primary mx-2 tooltip-custom" disabled>Next question</button></div>
        <div><button id="resetButton" class="btn btn-danger mx-2">Reset</button></div>
    </div>
</div>

<script>
const questions = Array.from(document.querySelectorAll('.question'));
const nextButton = document.getElementById('nextButton');
const prevButton = document.getElementById('prevButton');
const resetButton = document.getElementById('resetButton');
const timerEl = document.getElementById('timer');

const QUIZ_TOPIC = "<?= esc_js($atts['topic']) ?>";
const TIMER_DURATION = <?= $TIMER_DURATION ?>;
const QUIZ_RANDOM = <?= $atts['random'] ?>;

// Check for reset parameter and clear state
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('reset') === '1') {
    localStorage.removeItem('quiz_idx_' + QUIZ_TOPIC);
    localStorage.removeItem('quiz_order_' + QUIZ_TOPIC);
    localStorage.removeItem('quiz_answers_' + QUIZ_TOPIC);
    localStorage.removeItem('quiz_timing_' + QUIZ_TOPIC);
    // Remove reset parameter from URL
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]reset=1/, '');
    window.history.replaceState({}, '', newUrl);
}

const STORAGE_IDX_KEY   = 'quiz_idx_' + QUIZ_TOPIC;
const STORAGE_ORDER_KEY = 'quiz_order_' + QUIZ_TOPIC;
const STORAGE_ANSWERS_KEY = 'quiz_answers_' + QUIZ_TOPIC;
const STORAGE_TIMING_KEY = 'quiz_timing_' + QUIZ_TOPIC;

let timerInterval;
let questionStartTime;
let answerStatus = {};
let questionTiming = {};

// Add data-id to each question for mapping
questions.forEach((q, i) => q.dataset.id = i);

// ----------------------
// Restore saved state
// ----------------------
let currentQuestion = parseInt(localStorage.getItem(STORAGE_IDX_KEY)) || 0;
let questionOrder   = JSON.parse(localStorage.getItem(STORAGE_ORDER_KEY));
answerStatus = JSON.parse(localStorage.getItem(STORAGE_ANSWERS_KEY)) || {};
questionTiming = JSON.parse(localStorage.getItem(STORAGE_TIMING_KEY)) || {};

// First visit or after reset
if (!questionOrder || questionOrder.length !== questions.length) {
    questionOrder = questions.map((_, i) => i);       // 0..N-1
    
    if (QUIZ_RANDOM === 1) {
        questionOrder.sort(() => Math.random() - 0.5);    // shuffle once
    }
    localStorage.setItem(STORAGE_ORDER_KEY, JSON.stringify(questionOrder));
}

// Reorder DOM according to saved order
const container = document.getElementById('quizContainer');
questionOrder.forEach(i => container.appendChild(questions[i]));
const orderedQuestions = questionOrder.map(i => questions[i]);

// ----------------------
// Timer
// ----------------------
function startTimer(duration) {
    clearInterval(timerInterval);
    let remaining = duration;
    questionStartTime = Date.now();
    timerEl.style.color = 'green';
    timerEl.style.fontSize = '24px';
    timerEl.textContent = remaining + 's';

    timerInterval = setInterval(() => {
        remaining--;
        timerEl.textContent = remaining + 's';
        if (remaining <= 10) timerEl.style.color = 'red';
        if (remaining <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = 'BOOM!';
            timerEl.style.fontSize = '36px';
            timerEl.style.fontWeight = 'bold';
        }
    }, 1000);
}

function recordQuestionTiming(questionId) {
    if (questionStartTime) {
        const timeSpent = Math.round((Date.now() - questionStartTime) / 1000);
        questionTiming[questionId] = timeSpent;
        localStorage.setItem(STORAGE_TIMING_KEY, JSON.stringify(questionTiming));
    }
}

// ----------------------
// Show question
// ----------------------
function updateNextButtonText() {
    nextButton.textContent = (currentQuestion === orderedQuestions.length - 1) ? 'Finish Quiz' : 'Next question';
}

function showQuestion() {
    questions.forEach(q => q.style.display = 'none');
    if (!orderedQuestions[currentQuestion]) return;

    orderedQuestions[currentQuestion].style.display = 'block';

    // Update counter: 1-based
    const counterEl = orderedQuestions[currentQuestion].querySelector('.question-counter');
    counterEl.textContent = (currentQuestion + 1) + '/' + orderedQuestions.length;

    localStorage.setItem(STORAGE_IDX_KEY, currentQuestion);
    startTimer(TIMER_DURATION);
    updateNextButtonText();
    updateAnswerButtons();
    updateNextButtonState();
}

function updateAnswerButtons() {
    const questionId = orderedQuestions[currentQuestion].dataset.id;
    const buttons = orderedQuestions[currentQuestion].querySelectorAll('.answer-btn');
    
    buttons.forEach(btn => {
        btn.classList.remove('selected');
        if (answerStatus[questionId] === btn.dataset.status) {
            btn.classList.add('selected');
        }
    });
}

function updateNextButtonState() {
    const questionId = orderedQuestions[currentQuestion].dataset.id;
    const isDisabled = !answerStatus.hasOwnProperty(questionId);
    nextButton.disabled = isDisabled;
    
    // Add/remove disabled class for tooltip on wrapper
    const wrapper = nextButton.parentElement;
    if (isDisabled) {
        wrapper.classList.add('disabled');
    } else {
        wrapper.classList.remove('disabled');
    }
}


// ----------------------
// Next / Prev / Reset
// ----------------------
nextButton.onclick = () => {
    clearInterval(timerInterval);
    recordQuestionTiming(orderedQuestions[currentQuestion].dataset.id);
    
    if (currentQuestion < orderedQuestions.length - 1) {
        currentQuestion++;
        showQuestion();
    } else {
        showQuizResults();
    }
};

prevButton.onclick = () => {
    if (currentQuestion > 0) {
        clearInterval(timerInterval);
        recordQuestionTiming(orderedQuestions[currentQuestion].dataset.id);
        currentQuestion--;
        showQuestion();
    }
};

resetButton.onclick = () => {
    clearInterval(timerInterval);
    if(!confirm('Are you sure you want to reset the quiz?')) return;
    localStorage.removeItem(STORAGE_IDX_KEY);
    localStorage.removeItem(STORAGE_ORDER_KEY);
    localStorage.removeItem(STORAGE_ANSWERS_KEY);
    localStorage.removeItem(STORAGE_TIMING_KEY);
    location.reload();
};

// Answer button click handlers
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('answer-btn') || e.target.closest('.answer-btn')) {
        const button = e.target.classList.contains('answer-btn') ? e.target : e.target.closest('.answer-btn');
        const questionId = button.dataset.questionId;
        const status = button.dataset.status;
        
        answerStatus[questionId] = status;
        localStorage.setItem(STORAGE_ANSWERS_KEY, JSON.stringify(answerStatus));
        
        updateAnswerButtons();
        updateNextButtonState();
    }
});

function showQuizResults() {
    orderedQuestions.forEach(q => q.style.display = 'none');
    timerEl.textContent = '';
    nextButton.style.display = 'none';
    prevButton.style.display = 'none';
    resetButton.style.display = 'none'; // Hide reset button too
    
    const stats = calculateStatistics();
    const questionDetails = generateQuestionDetails();
    
    // Save test result via AJAX
    saveTestResult(stats, questionDetails);
}

function generateQuestionDetails() {
    const details = [];
    orderedQuestions.forEach((question, index) => {
        const questionId = question.dataset.id;
        const questionText = question.querySelector('h1').textContent;
        const status = answerStatus[questionId] || "didn't-answer";
        const timing = questionTiming[questionId] || 0;
        
        details.push({
            question: questionText,
            status: status,
            timing: timing,
            timer_duration: TIMER_DURATION
        });
    });
    return details;
}

function saveTestResult(stats, questionDetails) {
    // Create nonce
    const nonce = '<?php echo wp_create_nonce("save_test_result"); ?>';
    
    const data = {
        action: 'save_test_result',
        nonce: nonce,
        topic: QUIZ_TOPIC,
        stats: JSON.stringify(stats),
        question_details: JSON.stringify(questionDetails),
        timer_duration: TIMER_DURATION
    };
    
    // Send AJAX request
    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Redirect to the test result page
            window.location.href = result.data.permalink;
        } else {
            console.error('Failed to save test result:', result.data);
            // Fallback: show results locally
            const resultsHTML = generateResultsHTML(stats);
            document.getElementById('quizContainer').innerHTML = resultsHTML;
        }
    })
    .catch(error => {
        console.error('Error saving test result:', error);
        // Fallback: show results locally
        const resultsHTML = generateResultsHTML(stats);
        document.getElementById('quizContainer').innerHTML = resultsHTML;
    });
}

function calculateStatistics() {
    const total = orderedQuestions.length;
    const answered = Object.values(answerStatus).filter(s => s === 'answered').length;
    const somewhat = Object.values(answerStatus).filter(s => s === 'somewhat-answered').length;
    const didnt = Object.values(answerStatus).filter(s => s === "didn't-answer").length;
    
    // Calculate score: answered = 100%, somewhat = 60%, didn't answer = 0%
    const score = total > 0 ? Math.round(((answered * 100) + (somewhat * 60)) / total) : 0;
    
    return {
        total,
        answered,
        somewhat,
        didnt,
        score,
        answeredPercent: total > 0 ? Math.round((answered / total) * 100) : 0,
        somewhatPercent: total > 0 ? Math.round((somewhat / total) * 100) : 0,
        didntPercent: total > 0 ? Math.round((didnt / total) * 100) : 0
    };
}

function generateResultsHTML(stats) {
    let html = '<h1 class="text-center text-success mb-4">🎉 Congratulations! You finished the quiz! 🎉</h1>';
    
    // General statistics
    html += '<div class="card mb-4">';
    html += '<div class="card-header d-flex justify-content-between align-items-center">';
    html += '<h3 class="mb-0">General Statistics</h3>';
    html += '<span class="badge bg-primary fs-6">Total questions: ' + stats.total + '</span>';
    html += '</div>';
    html += '<div class="card-body">';
    html += '<div class="d-flex flex-column gap-3">';
    html += '<div class="stat-item">';
    html += '<div class="d-flex justify-content-between align-items-center mb-1">';
    html += '<span class="fw-semibold text-success">Answered</span>';
    html += '<span class="text-success fw-bold">' + stats.answered + ' (' + stats.answeredPercent + '%)</span>';
    html += '</div>';
    html += '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    html += '<div class="progress-bar bg-success" style="width: ' + stats.answeredPercent + '%; border-radius: 4px;"></div>';
    html += '</div>';
    html += '</div>';
    html += '<div class="stat-item">';
    html += '<div class="d-flex justify-content-between align-items-center mb-1">';
    html += '<span class="fw-semibold text-warning">Somewhat Answered</span>';
    html += '<span class="text-warning fw-bold">' + stats.somewhat + ' (' + stats.somewhatPercent + '%)</span>';
    html += '</div>';
    html += '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    html += '<div class="progress-bar bg-warning" style="width: ' + stats.somewhatPercent + '%; border-radius: 4px;"></div>';
    html += '</div>';
    html += '</div>';
    html += '<div class="stat-item">';
    html += '<div class="d-flex justify-content-between align-items-center mb-1">';
    html += '<span class="fw-semibold text-danger">Didn\'t Answer</span>';
    html += '<span class="text-danger fw-bold">' + stats.didnt + ' (' + stats.didntPercent + '%)</span>';
    html += '</div>';
    html += '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    html += '<div class="progress-bar bg-danger" style="width: ' + stats.didntPercent + '%; border-radius: 4px;"></div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';

    // Add score display
    const scoreClass = stats.score >= 80 ? 'score-excellent' : stats.score >= 50 ? 'score-good' : 'score-poor';
    const scoreMessage = stats.score >= 80 ? 'Excellent!' : stats.score >= 50 ? 'Good Job!' : 'Keep Practicing!';
    
    html += '<div class="score-display ' + scoreClass + '">';
    html += '<div>' + stats.score + '%</div>';
    html += '<div style="font-size: 1.2rem; font-weight: normal;">' + scoreMessage + '</div>';
    html += '</div>';
    
    html += '</div></div>';
    
    // Detailed question list
    html += '<div class="card">';
    html += '<div class="card-header"><h3>Question Details</h3></div>';
    html += '<div class="card-body">';
    
    orderedQuestions.forEach((question, index) => {
        const questionId = question.dataset.id;
        const questionText = question.querySelector('h1').textContent;
        const status = answerStatus[questionId] || "didn't-answer";
        const timing = questionTiming[questionId] || 0;
        const isOvertime = timing > TIMER_DURATION;
        
        let statusBadge = '';
        let statusClass = '';
        let statusIcon = '';
        
        switch(status) {
            case 'answered':
                statusBadge = 'Answered';
                statusClass = 'bg-success';
                statusIcon = '<i class="fas fa-check"></i> ';
                break;
            case 'somewhat-answered':
                statusBadge = 'Somewhat answered';
                statusClass = 'bg-warning';
                statusIcon = '<i class="fas fa-minus"></i> ';
                break;
            default:
                statusBadge = "Didn't answer";
                statusClass = 'bg-danger';
                statusIcon = '<i class="fas fa-times"></i> ';
        }
        
        html += '<div class="mb-2 p-2 border rounded">';
        html += '<strong>' + (index + 1) + '. ' + questionText + '</strong> - ';
        html += '<span class="badge ' + statusClass + '">' + statusIcon + statusBadge + '</span> - ';
        
        if (isOvertime) {
            html += '<span class="badge bg-secondary">Overtime: ' + timing + 's</span>';
        } else {
            html += '<span class="text-muted">' + timing + 's</span>';
        }
        
        html += '</div>';
    });
    
    html += '</div></div>';
    
    return html;
}
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') nextButton.click();
    if (e.key === 'ArrowLeft') prevButton.click();
});

// ----------------------
// Initial display
// ----------------------
showQuestion();
</script>
