<?php
/*
Template Name: Test Result View
*/
if (!defined('ABSPATH')) exit;

global $post;

// Get test result data
$topic = get_post_meta($post->ID, '_test_topic', true);
$timer_duration = get_post_meta($post->ID, '_test_timer_duration', true);
$stats = get_post_meta($post->ID, '_test_stats', true);
$question_details = get_post_meta($post->ID, '_test_question_details', true);
$test_date = get_post_meta($post->ID, '_test_date', true);

// Get user/guest info
$user_id = get_post_meta($post->ID, '_user_id', true);
$guest_id = get_post_meta($post->ID, '_guest_id', true);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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

#nextButton:disabled:hover {
    cursor: not-allowed;
}
</style>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Test Results: <?= esc_html($topic) ?></h3>
                    <div class="text-muted">
                        <small>
                            <?php if ($user_id): ?>
                                User ID: <?= esc_html($user_id) ?>
                            <?php else: ?>
                                Guest Test
                            <?php endif; ?>
                            | <?= esc_html(date('F j, Y, g:i a', strtotime($test_date))) ?>
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($question_details)): ?>
                        <?= generateResultsHTML($question_details); ?>
                    <?php else: ?>
                        <div class="alert alert-warning">No test data available.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function generateResultsHTML($questionDetails) {
    $html = '<h1 class="text-center text-success mb-4">🎉 Congratulations! You finished the quiz! 🎉</h1>';
    
    // Calculate statistics from question details
    $total = count($questionDetails);
    $answered = 0;
    $somewhat = 0;
    $didnt = 0;
    
    if (!empty($questionDetails) && is_array($questionDetails)) {
        foreach ($questionDetails as $detail) {
            $status = $detail['status'] ?? "didn't-answer";
            if ($status === 'answered') {
                $answered++;
            } elseif ($status === 'somewhat-answered') {
                $somewhat++;
            } else {
                $didnt++;
            }
        }
    }
    
    // Calculate percentages
    $answeredPercent = $total > 0 ? round(($answered / $total) * 100) : 0;
    $somewhatPercent = $total > 0 ? round(($somewhat / $total) * 100) : 0;
    $didntPercent = $total > 0 ? round(($didnt / $total) * 100) : 0;
    
    // Calculate score
    $score = $total > 0 ? round((($answered * 100) + ($somewhat * 60)) / $total) : 0;
    
    $stats = [
        'total' => $total,
        'answered' => $answered,
        'somewhat' => $somewhat,
        'didnt' => $didnt,
        'answeredPercent' => $answeredPercent,
        'somewhatPercent' => $somewhatPercent,
        'didntPercent' => $didntPercent,
        'score' => $score
    ];
    
    // General statistics
    $html .= '<div class="card mb-4">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<h3 class="mb-0">General Statistics</h3>';
    $html .= '<span class="badge bg-primary fs-6">Total questions: ' . $stats['total'] . '</span>';
    $html .= '</div>';
    $html .= '<div class="card-body">';
    $html .= '<div class="d-flex flex-column gap-3">';
    $html .= '<div class="stat-item">';
    $html .= '<div class="d-flex justify-content-between align-items-center mb-1">';
    $html .= '<span class="fw-semibold text-success">Answered</span>';
    $html .= '<span class="text-success fw-bold">' . $stats['answered'] . ' (' . $stats['answeredPercent'] . '%)</span>';
    $html .= '</div>';
    $html .= '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    $html .= '<div class="progress-bar bg-success" style="width: ' . $stats['answeredPercent'] . '%; border-radius: 4px;"></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<div class="d-flex justify-content-between align-items-center mb-1">';
    $html .= '<span class="fw-semibold text-warning">Somewhat Answered</span>';
    $html .= '<span class="text-warning fw-bold">' . $stats['somewhat'] . ' (' . $stats['somewhatPercent'] . '%)</span>';
    $html .= '</div>';
    $html .= '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    $html .= '<div class="progress-bar bg-warning" style="width: ' . $stats['somewhatPercent'] . '%; border-radius: 4px;"></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<div class="d-flex justify-content-between align-items-center mb-1">';
    $html .= '<span class="fw-semibold text-danger">Didn\'t Answer</span>';
    $html .= '<span class="text-danger fw-bold">' . $stats['didnt'] . ' (' . $stats['didntPercent'] . '%)</span>';
    $html .= '</div>';
    $html .= '<div class="progress" style="height: 20px; background-color: #f8f9fa;">';
    $html .= '<div class="progress-bar bg-danger" style="width: ' . $stats['didntPercent'] . '%; border-radius: 4px;"></div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Add score display
    $scoreClass = ($stats['score'] >= 80) ? 'score-excellent' : (($stats['score'] >= 50) ? 'score-good' : 'score-poor');
    $scoreMessage = ($stats['score'] >= 80) ? 'Excellent!' : (($stats['score'] >= 50) ? 'Good Job!' : 'Keep Practicing!');
    
    $html .= '<div class="score-display ' . $scoreClass . '">';
    $html .= '<div>' . $stats['score'] . '%</div>';
    $html .= '<div style="font-size: 1.2rem; font-weight: normal;">' . $scoreMessage . '</div>';
    $html .= '</div>';
    
    // Add Retake Test button right after score
    global $topic, $timer_duration;
    $html .= '<div class="text-center my-4">';
    $html .= '<a href="' . get_site_url() . '/full-screen-quiz/?topic=' . esc_attr($topic) . '&timer=' . esc_attr($timer_duration) . '&random=1&reset=1" ';
    $html .= 'class="btn btn-primary btn-lg">Retake Test</a>';
    $html .= '</div>';
    
    // Detailed question list
    $html .= '<div class="card">';
    $html .= '<div class="card-header"><h3>Advanced Test Details</h3></div>';
    $html .= '<div class="card-body">';
    
    if (!empty($questionDetails)) {
        foreach ($questionDetails as $index => $detail) {
            $status = $detail['status'] ?? "didn't-answer";
            $timing = $detail['timing'] ?? 0;
            $questionText = $detail['question'] ?? 'Question ' . ($index + 1);
            $isOvertime = $timing > ($detail['timer_duration'] ?? 15);
            
            $statusBadge = '';
            $statusClass = '';
            $statusIcon = '';
            
            switch($status) {
                case 'answered':
                    $statusBadge = 'Answered';
                    $statusClass = 'bg-success';
                    $statusIcon = '<i class="fas fa-check"></i> ';
                    break;
                case 'somewhat-answered':
                    $statusBadge = 'Somewhat answered';
                    $statusClass = 'bg-warning';
                    $statusIcon = '<i class="fas fa-minus"></i> ';
                    break;
                default:
                    $statusBadge = "Didn't answer";
                    $statusClass = 'bg-danger';
                    $statusIcon = '<i class="fas fa-times"></i> ';
            }
            
            $html .= '<div class="mb-2 p-2 border rounded">';
            $html .= '<strong>' . ($index + 1) . '. ' . $questionText . '</strong> - ';
            $html .= '<span class="badge ' . $statusClass . '">' . $statusIcon . $statusBadge . '</span> - ';
            
            if ($isOvertime) {
                $html .= '<span class="badge bg-secondary">Overtime: ' . $timing . 's</span>';
            } else {
                $html .= '<span class="text-muted">' . $timing . 's</span>';
            }
            
            $html .= '</div>';
        }
    }
    
    $html .= '</div></div>';
    
    return $html;
}
?>
