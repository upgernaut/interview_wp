<?php
// $items should be passed from the shortcode
// $atts['timer'] contains timer seconds
$TIMER_DURATION = isset($atts['timer']) ? (int)$atts['timer'] : 15;
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

<div id="quizContainer">
<?php foreach ($items as $key => $post_id): 
    $post = get_post($post_id);
    if (!$post) continue; // safety
    $q_question = get_post_meta($post_id, '_interview_question', true);

    $q_content = apply_filters('the_content', $post->post_content);
?>
    <div class="question" style="display:none">
        <h1><?= ($key + 1) ?>. <?= $q_question ?></h1>


        <div class="accordion mt-3" id="accordion_<?= $key ?>">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $key ?>" aria-expanded="false">
                        Answer
                    </button>
                </h2>
                <div id="collapse_<?= $key ?>" class="accordion-collapse collapse" data-bs-parent="#accordion_<?= $key ?>">
                    <div class="accordion-body">
                        <?= $q_content ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

</div>

<div class="mt-3 text-center">
    <div id="timer" style="font-size:24px; font-weight:bold; margin-bottom:10px;"></div>
    <button id="prevButton" class="btn btn-secondary mx-2">Prev question</button>
    <button id="nextButton" class="btn btn-primary mx-2">Next question</button>
    <button id="resetButton" class="btn btn-danger mx-2">Reset</button>
</div>
<script>
const questions = Array.from(document.querySelectorAll('.question'));
const nextButton = document.getElementById('nextButton');
const prevButton = document.getElementById('prevButton');
const resetButton = document.getElementById('resetButton');
const timerEl = document.getElementById('timer');

const QUIZ_TOPIC = "<?= esc_js($atts['topic']) ?>";


let timerInterval;
const TIMER_DURATION = <?= $TIMER_DURATION ?>;

const STORAGE_IDX_KEY = 'quiz_idx';
const STORAGE_ORDER_KEY = 'quiz_order';

// Add data-id to each question for mapping
questions.forEach((q, i) => q.dataset.id = i);

// ----------------------
// Restore saved state
// ----------------------
let currentQuestion = parseInt(localStorage.getItem(STORAGE_IDX_KEY));
if (isNaN(currentQuestion)) currentQuestion = 0;

let questionOrder = JSON.parse(localStorage.getItem(STORAGE_ORDER_KEY) || 'null');
// If no saved order, initialize once
// alert(questions.length !== questionOrder.length);
// alert(currentQuestion);

if (!questionOrder || questionOrder.length !== questions.length) {
    questionOrder = questions.map((_, i) => i);

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
    localStorage.setItem(STORAGE_IDX_KEY, currentQuestion);
    startTimer(TIMER_DURATION);
    updateNextButtonText();
}

// ----------------------
// Next / Prev / Reset
// ----------------------
nextButton.onclick = () => {
    clearInterval(timerInterval);
    if (currentQuestion < orderedQuestions.length - 1) {
        currentQuestion++;
        showQuestion();
    } else {
        orderedQuestions.forEach(q => q.style.display = 'none');
        timerEl.textContent = '';
        nextButton.style.display = 'none';
        prevButton.style.display = 'none';
        resetButton.style.display = 'inline-block';
        document.getElementById('quizContainer').innerHTML =
            '<h1 class="text-center text-success">🎉 Congratulations! You finished the quiz! 🎉</h1>';
        // Clear storage only when quiz is finished
        localStorage.removeItem(STORAGE_IDX_KEY);
        localStorage.removeItem(STORAGE_ORDER_KEY);
    }
};

prevButton.onclick = () => {
    if (currentQuestion > 0) {
        clearInterval(timerInterval);
        currentQuestion--;
        showQuestion();
    }
};

resetButton.onclick = () => {
    clearInterval(timerInterval);

    localStorage.removeItem(STORAGE_IDX_KEY);
    localStorage.removeItem(STORAGE_ORDER_KEY);
    localStorage.clear();

    fetch("<?= admin_url('admin-ajax.php') ?>", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            action: "quiz_reset",
            topic: "<?= esc_js($atts['topic']) ?>"
        })
    }).then(() => {
        location.reload();
    });
};


// ----------------------
// Keyboard navigation
// ----------------------
document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') nextButton.click();
    if (e.key === 'ArrowLeft') prevButton.click();
});

// ----------------------
// Initial display
// ----------------------
showQuestion();
</script>
