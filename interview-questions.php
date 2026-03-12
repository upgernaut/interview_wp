<?php
/*
Plugin Name: Interview Test Engine
*/


if (!defined('ABSPATH')) exit;
// --------------------
// Register CPT & Taxonomy
// --------------------
add_action('init', function () {

    // CPT
    register_post_type('interview_question', [
        'label' => 'Interview Questions',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // We'll add menu manually
        'supports' => ['title', 'editor'],
        'has_archive' => false,
    ]);

    // Test Results CPT
    register_post_type('iq_test_result', [
        'label' => 'IQ Test Results',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // We'll add menu manually
        'supports' => ['title'],
        'has_archive' => false,
        'publicly_queryable' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'test-result'],
        'show_in_admin_bar' => true,
    ]);

    // Taxonomy
    register_taxonomy('topic', 'interview_question', [
        'label' => 'Topics',
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // attach manually
        'hierarchical' => true,
    ]);
});


add_action('admin_menu', function () {

    // Top-level menu points directly to CPT list
    add_menu_page(
        'Interview Test',        // Page title
        'Interview Test',        // Menu title
        'manage_options',        // Capability
        'edit.php?post_type=interview_question', // directly link to CPT
        '',                       // callback not needed when linking
        'dashicons-welcome-learn-more',
        25
    );

    // Submenu: Topics (taxonomy UI)
    add_submenu_page(
        'edit.php?post_type=interview_question',  // parent slug is CPT
        'Topics',
        'Topics',
        'manage_options',
        'edit-tags.php?taxonomy=topic&post_type=interview_question'
    );

    // Submenu: Test Results
    add_submenu_page(
        'edit.php?post_type=interview_question',
        'Test Results',
        'Test Results',
        'manage_options',
        'edit.php?post_type=iq_test_result'
    );

    // Submenu: Settings
    add_submenu_page(
        'edit.php?post_type=interview_question',
        'Settings',
        'Settings',
        'manage_options',
        'interview-test-settings',
        function () {
            ?>
            <div class="wrap">
                <h1>Interview Test Settings</h1>
                <p>Settings will go here.</p>
            </div>
            <?php
        }
    );
});


// Add topic filter dropdown
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'interview_question') return;

    $taxonomy = 'topic';
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

    if (empty($terms)) return;

    $current = $_GET[$taxonomy] ?? '';

    echo '<select name="'.$taxonomy.'">';
    echo '<option value="">All Topics</option>';
    foreach ($terms as $term) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($term->slug),
            selected($current, $term->slug, false),
            esc_html($term->name)
        );
    }
    echo '</select>';
});

// Apply filter to query
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('post_type') !== 'interview_question') return;

    if (!empty($_GET['topic'])) {
        $query->set('tax_query', [[
            'taxonomy' => 'topic',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['topic']),
        ]]);
    }
});

// Add rewrite rules for test results
add_action('init', function() {
    add_rewrite_rule(
        '^test-result/([^/]+)/?$',
        'index.php?post_type=iq_test_result&name=$matches[1]',
        'top'
    );
});

// Flush rewrite rules on plugin activation
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Custom template for test results
add_filter('template_include', function($template) {
    if (get_query_var('post_type') === 'iq_test_result') {
        return plugin_dir_path(__FILE__) . 'templates/test-result-view.php';
    }
    return $template;
});

// Guest tracking functionality
function get_or_create_guest_id() {
    if (!isset($_COOKIE['iq_guest_id'])) {
        $guest_id = wp_generate_uuid4();
        setcookie('iq_guest_id', $guest_id, time() + (365 * 24 * 60 * 60), '/', '', false, true); // 1 year, HTTP only, secure
        return $guest_id;
    }
    return sanitize_text_field($_COOKIE['iq_guest_id']);
}

// AJAX handler for saving test results
add_action('wp_ajax_save_test_result', 'save_test_result_handler');
add_action('wp_ajax_nopriv_save_test_result', 'save_test_result_handler');

function save_test_result_handler() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'save_test_result')) {
        wp_send_json_error('Invalid nonce');
    }

    $topic = sanitize_text_field($_POST['topic'] ?? '');
    $stats = json_decode(stripslashes($_POST['stats'] ?? '{}'), true);
    $question_details = json_decode(stripslashes($_POST['question_details'] ?? '[]'), true);
    $timer_duration = intval($_POST['timer_duration'] ?? 15);

    if (empty($topic) || empty($stats)) {
        wp_send_json_error('Missing required data');
    }

    // Create test result post
    $post_data = [
        'post_title' => 'Test Result - ' . $topic . ' - ' . date('Y-m-d H:i:s'),
        'post_status' => 'publish',
        'post_type' => 'iq_test_result',
    ];

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        // Save all test data as meta
        update_post_meta($post_id, '_test_topic', $topic);
        update_post_meta($post_id, '_test_timer_duration', $timer_duration);
        update_post_meta($post_id, '_test_stats', $stats);
        update_post_meta($post_id, '_test_question_details', $question_details);
        update_post_meta($post_id, '_test_date', current_time('mysql'));

        // User/guest tracking
        if (is_user_logged_in()) {
            update_post_meta($post_id, '_user_id', get_current_user_id());
        } else {
            update_post_meta($post_id, '_guest_id', get_or_create_guest_id());
        }

        wp_send_json_success([
            'post_id' => $post_id,
            'permalink' => get_permalink($post_id)
        ]);
    } else {
        wp_send_json_error('Failed to create test result');
    }
}

add_action('wp_ajax_quiz_reset', 'quiz_reset_handler');
add_action('wp_ajax_nopriv_quiz_reset', 'quiz_reset_handler');

function quiz_reset_handler() {
    session_start();
    $topic = sanitize_text_field($_POST['topic'] ?? '');
    if ($topic) {
        unset($_SESSION['quiz_order_' . $topic]);
    }
    wp_send_json_success();
}

add_action('add_meta_boxes', function() {
    add_meta_box(
        'interview_question_field',
        'Question',
        function($post) {
            $content = get_post_meta($post->ID, '_interview_question', true);
            wp_editor(
                $content,                  // Current content
                'interview_question_meta',  // HTML ID & name
                [
                    'textarea_name' => 'interview_question_meta',
                    'textarea_rows' => 10,
                    'media_buttons' => true, // optional, allows adding images
                    'teeny' => false,        // full editor
                ]
            );
        },
        'interview_question',
        'normal',
        'high'
    );
    
    // Test Results meta boxes
    add_meta_box(
        'test_result_stats',
        'Test Statistics',
        function($post) {
            $stats = get_post_meta($post->ID, '_test_stats', true);
            $total = $stats['total'] ?? 0;
            $answered = $stats['answered'] ?? 0;
            $somewhat = $stats['somewhat'] ?? 0;
            $didnt = $stats['didnt'] ?? 0;
            $score = $stats['score'] ?? 0;
            
            echo '<table class="form-table">';
            echo '<tr><th>Total Questions</th><td><input type="number" name="test_stats[total]" value="' . esc_attr($total) . '" min="0" /></td></tr>';
            echo '<tr><th>Answered</th><td><input type="number" name="test_stats[answered]" value="' . esc_attr($answered) . '" min="0" /></td></tr>';
            echo '<tr><th>Somewhat Answered</th><td><input type="number" name="test_stats[somewhat]" value="' . esc_attr($somewhat) . '" min="0" /></td></tr>';
            echo '<tr><th>Didn\'t Answer</th><td><input type="number" name="test_stats[didnt]" value="' . esc_attr($didnt) . '" min="0" /></td></tr>';
            echo '<tr><th>Score (%)</th><td><input type="number" name="test_stats[score]" value="' . esc_attr($score) . '" min="0" max="100" /></td></tr>';
            echo '</table>';
            
            wp_nonce_field('save_test_result_meta', 'test_result_meta_nonce');
        },
        'iq_test_result',
        'normal',
        'default'
    );
    
    add_meta_box(
        'test_result_details',
        'Question Details',
        function($post) {
            $details = get_post_meta($post->ID, '_test_question_details', true);
            echo '<div id="question-details-container">';
            echo '<button type="button" id="add-question-detail" class="button">Add Question</button>';
            echo '<table class="wp-list-table widefat fixed striped" id="question-details-table">';
            echo '<thead><tr><th>Question #</th><th>Question Text</th><th>Status</th><th>Time (s)</th><th>Actions</th></tr></thead>';
            echo '<tbody>';
            
            if (!empty($details) && is_array($details)) {
                foreach ($details as $index => $detail) {
                    $question_text = $detail['question'] ?? '';
                    $status = $detail['status'] ?? "didn't-answer";
                    $timing = $detail['timing'] ?? 0;
                    $timer_duration = $detail['timer_duration'] ?? 15;
                    
                    echo '<tr>';
                    echo '<td>' . ($index + 1) . '</td>';
                    echo '<td><input type="text" name="question_details[' . $index . '][question]" value="' . esc_attr($question_text) . '" class="regular-text" /></td>';
                    echo '<td>';
                    echo '<select name="question_details[' . $index . '][status]">';
                    echo '<option value="answered" ' . selected($status, 'answered', false) . '>Answered</option>';
                    echo '<option value="somewhat-answered" ' . selected($status, 'somewhat-answered', false) . '>Somewhat Answered</option>';
                    echo '<option value="didn\'t-answer" ' . selected($status, "didn't-answer", false) . '>Didn\'t Answer</option>';
                    echo '</select>';
                    echo '</td>';
                    echo '<td><input type="number" name="question_details[' . $index . '][timing]" value="' . esc_attr($timing) . '" min="0" step="1" /></td>';
                    echo '<td><button type="button" class="button remove-question-detail">Remove</button></td>';
                    echo '</tr>';
                }
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        },
        'iq_test_result',
        'normal',
        'default'
    );
    
    add_meta_box(
        'test_result_info',
        'Test Information',
        function($post) {
            $topic = get_post_meta($post->ID, '_test_topic', true);
            $timer_duration = get_post_meta($post->ID, '_test_timer_duration', true);
            $test_date = get_post_meta($post->ID, '_test_date', true);
            $user_id = get_post_meta($post->ID, '_user_id', true);
            $guest_id = get_post_meta($post->ID, '_guest_id', true);
            
            echo '<table class="form-table">';
            echo '<tr><th>Topic</th><td><input type="text" name="test_info[topic]" value="' . esc_attr($topic) . '" class="regular-text" /></td></tr>';
            echo '<tr><th>Timer Duration (seconds)</th><td><input type="number" name="test_info[timer_duration]" value="' . esc_attr($timer_duration) . '" min="1" /></td></tr>';
            echo '<tr><th>Test Date</th><td><input type="datetime-local" name="test_info[test_date]" value="' . esc_attr(date('Y-m-d\TH:i', strtotime($test_date))) . '" /></td></tr>';
            echo '<tr><th>User ID</th><td><input type="number" name="test_info[user_id]" value="' . esc_attr($user_id) . '" min="0" /> (0 = Guest)</td></tr>';
            echo '<tr><th>Guest ID</th><td><input type="text" name="test_info[guest_id]" value="' . esc_attr($guest_id) . '" class="regular-text" /></td></tr>';
            echo '</table>';
        },
        'iq_test_result',
        'side',
        'default'
    );
});

add_action('save_post', function($post_id) {
    if (isset($_POST['interview_question_meta'])) {
        // Allow HTML, do minimal sanitization
        update_post_meta($post_id, '_interview_question', wp_kses_post($_POST['interview_question_meta']));
    }
    
    // Save test result meta fields
    if (isset($_POST['test_result_meta_nonce']) && wp_verify_nonce($_POST['test_result_meta_nonce'], 'save_test_result_meta')) {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'iq_test_result') {
            // Save statistics
            if (isset($_POST['test_stats'])) {
                $stats = array_map('intval', $_POST['test_stats']);
                
                // Calculate percentages
                $total = $stats['total'] ?? 0;
                if ($total > 0) {
                    $stats['answeredPercent'] = round(($stats['answered'] / $total) * 100);
                    $stats['somewhatPercent'] = round(($stats['somewhat'] / $total) * 100);
                    $stats['didntPercent'] = round(($stats['didnt'] / $total) * 100);
                } else {
                    $stats['answeredPercent'] = 0;
                    $stats['somewhatPercent'] = 0;
                    $stats['didntPercent'] = 0;
                }
                
                update_post_meta($post_id, '_test_stats', $stats);
            }
            
            // Save question details
            if (isset($_POST['question_details'])) {
                $question_details = [];
                foreach ($_POST['question_details'] as $index => $detail) {
                    if (!empty($detail['question'])) {
                        $question_details[] = [
                            'question' => sanitize_text_field($detail['question']),
                            'status' => sanitize_text_field($detail['status']),
                            'timing' => intval($detail['timing']),
                            'timer_duration' => 15 // Default value
                        ];
                    }
                }
                update_post_meta($post_id, '_test_question_details', $question_details);
            }
            
            // Save test information
            if (isset($_POST['test_info'])) {
                $test_info = [
                    'topic' => sanitize_text_field($_POST['test_info']['topic']),
                    'timer_duration' => intval($_POST['test_info']['timer_duration']),
                    'test_date' => sanitize_text_field($_POST['test_info']['test_date']),
                    'user_id' => intval($_POST['test_info']['user_id']),
                    'guest_id' => sanitize_text_field($_POST['test_info']['guest_id'])
                ];
                
                update_post_meta($post_id, '_test_topic', $test_info['topic']);
                update_post_meta($post_id, '_test_timer_duration', $test_info['timer_duration']);
                update_post_meta($post_id, '_test_date', $test_info['test_date']);
                update_post_meta($post_id, '_user_id', $test_info['user_id']);
                update_post_meta($post_id, '_guest_id', $test_info['guest_id']);
            }
        }
    }
});

// Add JavaScript for question details management
add_action('admin_footer', function() {
    global $post;
    if ($post && $post->post_type === 'iq_test_result') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            let questionIndex = $('#question-details-table tbody tr').length;
            
            // Add new question row
            $('#add-question-detail').on('click', function() {
                let newRow = '<tr>' +
                    '<td>' + (questionIndex + 1) + '</td>' +
                    '<td><input type="text" name="question_details[' + questionIndex + '][question]" value="" class="regular-text" /></td>' +
                    '<td>' +
                        '<select name="question_details[' + questionIndex + '][status]">' +
                            '<option value="answered">Answered</option>' +
                            '<option value="somewhat-answered">Somewhat Answered</option>' +
                            '<option value="didn\'t-answer">Didn\'t Answer</option>' +
                        '</select>' +
                    '</td>' +
                    '<td><input type="number" name="question_details[' + questionIndex + '][timing]" value="0" min="0" step="1" /></td>' +
                    '<td><button type="button" class="button remove-question-detail">Remove</button></td>' +
                    '</tr>';
                
                $('#question-details-table tbody').append(newRow);
                questionIndex++;
            });
            
            // Remove question row
            $(document).on('click', '.remove-question-detail', function() {
                $(this).closest('tr').remove();
                // Renumber questions
                $('#question-details-table tbody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            });
        });
        </script>
        <?php
    }
});

// Make plugin template visible in WP Page editor
add_filter('theme_page_templates', function($templates) {
    $templates['fullscreen-quiz.php'] = 'Fullscreen Quiz (Plugin)';
    $templates['test-result-view.php'] = 'Test Result View (Plugin)';
    return $templates;
});

// Load the plugin template when selected
add_filter('template_include', function($template) {
    global $post;
    if (!$post) return $template;

    $selected_template = get_post_meta($post->ID, '_wp_page_template', true);
    if ($selected_template === 'fullscreen-quiz.php') {
        return plugin_dir_path(__FILE__) . 'templates/fullscreen-quiz.php';
    }
    if ($selected_template === 'test-result-view.php') {
        return plugin_dir_path(__FILE__) . 'templates/test-result-view.php';
    }

    return $template;
});


// Register the new template so WP can see it
add_filter('theme_page_templates', function($templates){
    $templates['interview-library.php'] = 'Interview Library (Plugin)';
    return $templates;
});

// Load the template if assigned
add_filter('template_include', function($template){
    global $post;
    if(!$post) return $template;
    if(get_post_meta($post->ID, '_wp_page_template', true) === 'interview-library.php'){
        return plugin_dir_path(__FILE__) . 'templates/interview-library.php';
    }
    return $template;
});


require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/importer.php';



add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=interview_question',
        'Documentation',
        'Documentation',
        'manage_options',
        'documentation',
        'documentation_page'
    );
});

function documentation_page() {
    // Get here all the topics, explicitly verbose that these are topics that can be used so far in the system
    $topics = get_terms([
        'taxonomy' => 'topic',
        'hide_empty' => false,
    ]); ?>
    <div class="wrap">
        <h1>Documentation</h1>
        <p>
            This plugin allows you to create and manage interview questions and topics.
        </p>
        <p>
            Topics can be used to group questions together.
        </p>
        <p>
            <h2>Current Topics:</h2>
            <ul>
                <?php foreach($topics as $topic) { ?>
                    <li><?php echo $topic->name; ?></li>
                <?php } ?>
            </ul>
        </p>
    </div>
    <hr>
    <h2>Shortcodes:</h2>
    <p>Shortcode that can be used is like this [interview_test topic="project-management" timer="5" random="1"]
    </p>
<p>So with the topics we have these shortcodes are applicable 
    <?php foreach($topics as $topic) { ?>
        <div>[interview_test topic="<?= $topic->slug ?>" timer="5" random="1"]</div>
    <?php } ?>
</p><hr>
<h2>Clean pages without shortcodes</h2>
<p>Those are without any header and footer, just clean pages with test</p>
<?php echo get_site_url(); ?>/full-screen-quiz/?topic=project-management&timer=20&random=1
<p>So applicable URL links are</p>
<?php 
foreach($topics as $topic) { ?>
    <div><a target="_blank"  href="<?php echo get_site_url(); ?>/full-screen-quiz/?topic=<?= $topic->slug ?>&timer=20&random=1"><?php echo get_site_url(); ?>/full-screen-quiz/?topic=<?= $topic->slug ?>&timer=20&random=1</a></div>

    <?php } ?>
<hr>
    <h2>Library pages:</h2>

    <p>There are also library pages that can be entered directly</p>
    <p>As an example: <?php echo get_site_url(); ?>/interview-library/?topic=php</p>
    <p>The library page will be automatically generated based on the topic. Here are applicable URLs for library pages:</p>
    <?php foreach($topics as $topic) { ?>
        <div><a target="_blank" href="<?php echo get_site_url(); ?>/interview-library/?topic=<?= $topic->slug ?>"><?php echo get_site_url(); ?>/interview-library/?topic=<?= $topic->slug ?></a></div>
    <?php } ?>

    <?php

}
