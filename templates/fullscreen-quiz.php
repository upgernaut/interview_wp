<?php
/*
Template Name: Fullscreen Quiz
*/
if (!defined('ABSPATH')) exit;

$topic = sanitize_text_field($_GET['topic'] ?? '');
$timer = intval($_GET['timer'] ?? 15);
$random = intval($_GET['random'] ?? 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz - <?= esc_html($topic) ?></title>
<style>
html, body {
    height: 100%;
    margin: 0;
    background: #f5f5f5;
    font-family: Arial, sans-serif;
}
#quiz-wrapper {
    max-width: 800px;
    width: 100%;
    margin: 0 auto; /* top margin 100px, horizontally centered */
    padding: 20px;
    /* background: #fff; */
    /* box-shadow: 0 5px 20px rgba(0,0,0,0.1); */
    border-radius: 12px;
}
.accordion-flush .accordion-item .accordion-button, .accordion-flush .accordion-item .accordion-button.collapsed {
    border-radius: 0;
}
</style>
<?php // wp_head(); ?>
</head>
<body>

<div id="quiz-wrapper">
    <?php
    // Display the quiz
    echo do_shortcode("[interview_test topic='{$topic}' timer='{$timer}' random='{$random}']");
    ?>
</div>

<?php // wp_footer(); ?>
</body>
</html>
