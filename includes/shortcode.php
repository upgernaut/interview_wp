<?php
session_start();
// Shortcode
add_shortcode('interview_test', function ($atts) {

    $atts = shortcode_atts([
        'topic' => '',
        'timer' => 15,
        'random' => 0, // default: order
    ], $atts);

    $q = new WP_Query([
        'post_type' => 'interview_question',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'tax_query' => [[
            'taxonomy' => 'topic',
            'field' => 'slug',
            'terms' => $atts['topic'],
        ]]
    ]);

    $items = [];
    while ($q->have_posts()) {
        $q->the_post();
        $items[] = get_the_ID(); // only save the post ID now
    }
    wp_reset_postdata();

    // Include template
    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/quiz-template.php';
    return ob_get_clean();
});
