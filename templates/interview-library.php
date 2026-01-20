<?php
/*
Template Name: Interview Library
*/
if (!defined('ABSPATH')) exit;

// Get topic from URL
$topic = sanitize_text_field($_GET['topic'] ?? 'default');

// Query all questions in this topic
$args = [
    'post_type' => 'interview_question',
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'ASC',
    'tax_query' => [
        [
            'taxonomy' => 'topic',
            'field' => 'slug',
            'terms' => $topic,
        ]
    ],
];
$query = new WP_Query($args);

// Build $titles array
$titles = [];
while ($query->have_posts()) {
    $query->the_post();

    $question = get_post_meta(get_the_ID(), '_interview_question', true);
    $answer = get_the_content(); // post content

    if ($question) {
        $titles[] = [$question, $answer];
    }
}
wp_reset_postdata();

// Fallback if no questions
if (empty($titles)) {
    echo '<h2>No questions found for this topic.</h2>';
    return;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Interview Library - <?= esc_html($topic) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>
<style>
.container { width: 85%; font-family: sans-serif; font-size: 27px; text-align: center; }
.scrollableCol { height: 100vh; overflow-y: scroll; }
.question { display: none; }
.question.show { display: block; }
.bookmarkList li { cursor: pointer; margin-bottom:5px; }
.bookmarkList li a { text-decoration: none; color: black; }
.bookmarkList li:hover { text-decoration: underline; }
.accordion_custom { border: 1px solid #ccc; border-radius: 5px; }
</style>
<?php //wp_head(); ?>
</head>
<body>
<div class="container-fluid text-start">
    <div class="row">
        <div class="col-4 fs-5 scrollableCol">
            <ol class="bookmarkList">
                <?php foreach($titles as $key => $value): ?>
                    <li id="mark_<?php echo $key+1 ?>" onclick="showQuestion(<?php echo $key; ?>)">
                        <a href="#mark_<?php echo $key+1 ?>" onclick="handleAnchorClick(event)"><?php echo $value[0]; ?></a>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="col-8 mt-5 text-center px-3">
            <?php $i=1; ?>
            <?php foreach($titles as $key => $value): ?>
                <div class="row question" id="question_<?php echo $key; ?>">
                    <div class="col-12">
                        <h1><?php echo $i; ?>. <?php echo $value[0]; ?></h1>
                    </div>
                    <div class="col-12 col-lg-8 offset-lg-2 mt-5">
                        <div class="accordion_custom accordion accordion-flush" id="accordionFlush_<?php echo $key; ?>">
                            <div class="accordion-item">
                                <h2 class="accordion-header text-center">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse_<?php echo $key; ?>" aria-expanded="false">
                                        Hint
                                    </button>
                                </h2>
                                <div id="flush-collapse_<?php echo $key; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionFlush_<?php echo $key; ?>">
                                    <div class="accordion-body text-start fs-4">
                                        <?php echo $value[1]; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php $i++; endforeach; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
jQuery(document).ready(function($) {

    function showQuestion(questionNumber) {
        $('.question').removeClass('show');
        var selected = $('#question_' + questionNumber);
        if(selected.length) {
            setTimeout(function() {
                selected.addClass('show');
            }, 0);
        }
    }

    function handleAnchorClick(event) {
        event.preventDefault();
        var targetId = $(event.currentTarget).attr('href');
        history.pushState(null, '', targetId);
    }

    // Bind click for bookmark links
    $('.bookmarkList li a').on('click', handleAnchorClick);

    // Show question if fragment exists
    var fragment = window.location.hash.substr(1);
    if(fragment) {
        var questionNumber = parseInt(fragment.replace('mark_','')) - 1;
        showQuestion(questionNumber);
    }

    // Bind click for bookmark li
    $('.bookmarkList li').on('click', function() {
        var index = $(this).index();
        showQuestion(index);
    });

});
</script>

<?php //wp_footer(); ?>
</body>
</html>
