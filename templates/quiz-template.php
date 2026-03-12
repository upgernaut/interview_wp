<?php
// $items should be passed from the shortcode
// $atts['timer'] contains timer seconds
$TIMER_DURATION = isset($atts['timer']) ? (int)$atts['timer'] : 15;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

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
                <button class="btn btn-danger answer-btn" data-status="didn't-answer" data-question-id="<?php echo $key; ?>">Didn't answer</button>
                <button class="btn btn-warning answer-btn" data-status="somewhat-answered" data-question-id="<?php echo $key; ?>">Somewhat answered</button>
                <button class="btn btn-success answer-btn" data-status="answered" data-question-id="<?php echo $key; ?>">Answered</button>
            </div>
        </div>

    </div>
<?php endforeach; ?>

</div>

<div class="mt-3 text-center">
    <div id="timer" style="font-size:24px; font-weight:bold; margin-bottom:10px;"></div>
    <button id="prevButton" class="btn btn-secondary mx-2">Prev question</button>
    <button id="nextButton" class="btn btn-primary mx-2" disabled>Next question</button>
    <button id="resetButton" class="btn btn-danger mx-2">Reset</button>
</div>

<script>
const questions = Array.from(document.querySelectorAll('.question'));
const nextButton = document.getElementById('nextButton');
const prevButton = document.getElementById('prevButton');
const resetButton = document.getElementById('resetButton');
const timerEl = document.getElementById('timer');

const QUIZ_TOPIC = "<?= esc_js($atts['topic']) ?>";
const TIMER_DURATION = <?= $TIMER_DURATION ?>;
const QUIZ_RANDOM = <?= (int)$atts['random'] ?>;

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
        btn.classList.remove('btn-outline-danger', 'btn-outline-warning', 'btn-outline-success');
        if (answerStatus[questionId] === btn.dataset.status) {
            btn.classList.add('btn-outline-' + btn.classList[1].replace('btn-', ''));
        }
    });
}

function updateNextButtonState() {
    const questionId = orderedQuestions[currentQuestion].dataset.id;
    nextButton.disabled = !answerStatus.hasOwnProperty(questionId);
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
    if (e.target.classList.contains('answer-btn')) {
        const questionId = e.target.dataset.questionId;
        const status = e.target.dataset.status;
        
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
    
    const stats = calculateStatistics();
    const resultsHTML = generateResultsHTML(stats);
    
    document.getElementById('quizContainer').innerHTML = resultsHTML;
    
    // Clear storage only when quiz is finished
    localStorage.removeItem(STORAGE_IDX_KEY);
    localStorage.removeItem(STORAGE_ORDER_KEY);
    localStorage.removeItem(STORAGE_ANSWERS_KEY);
    localStorage.removeItem(STORAGE_TIMING_KEY);
}

function calculateStatistics() {
    const total = orderedQuestions.length;
    const answered = Object.values(answerStatus).filter(s => s === 'answered').length;
    const somewhat = Object.values(answerStatus).filter(s => s === 'somewhat-answered').length;
    const didnt = Object.values(answerStatus).filter(s => s === "didn't-answer").length;
    
    return {
        total,
        answered,
        somewhat,
        didnt,
        answeredPercent: total > 0 ? Math.round((answered / total) * 100) : 0,
        somewhatPercent: total > 0 ? Math.round((somewhat / total) * 100) : 0,
        didntPercent: total > 0 ? Math.round((didnt / total) * 100) : 0
    };
}

function generateResultsHTML(stats) {
    let html = '<h1 class="text-center text-success mb-4">🎉 Congratulations! You finished the quiz! 🎉</h1>';
    
    // General statistics
    html += '<div class="card mb-4">';
    html += '<div class="card-header"><h3>General Statistics</h3></div>';
    html += '<div class="card-body">';
    html += '<div class="row">';
    html += '<div class="col-md-3 text-center"><div class="badge bg-success fs-6">' + stats.answered + '</div><br>Answered<br><small>' + stats.answeredPercent + '%</small></div>';
    html += '<div class="col-md-3 text-center"><div class="badge bg-warning fs-6">' + stats.somewhat + '</div><br>Somewhat answered<br><small>' + stats.somewhatPercent + '%</small></div>';
    html += '<div class="col-md-3 text-center"><div class="badge bg-danger fs-6">' + stats.didnt + '</div><br>Didn\'t answer<br><small>' + stats.didntPercent + '%</small></div>';
    html += '<div class="col-md-3 text-center"><div class="badge bg-primary fs-6">' + stats.total + '</div><br>Total questions</div>';
    html += '</div></div></div>';
    
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
        
        switch(status) {
            case 'answered':
                statusBadge = 'Answered';
                statusClass = 'bg-success';
                break;
            case 'somewhat-answered':
                statusBadge = 'Somewhat answered';
                statusClass = 'bg-warning';
                break;
            default:
                statusBadge = "Didn't answer";
                statusClass = 'bg-danger';
        }
        
        html += '<div class="mb-2 p-2 border rounded">';
        html += '<strong>' + (index + 1) + '. ' + questionText + '</strong> - ';
        html += '<span class="badge ' + statusClass + '">' + statusBadge + '</span> - ';
        
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
