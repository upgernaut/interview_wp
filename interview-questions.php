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
});

add_action('save_post', function($post_id) {
    if (isset($_POST['interview_question_meta'])) {
        // Allow HTML, do minimal sanitization
        update_post_meta($post_id, '_interview_question', wp_kses_post($_POST['interview_question_meta']));
    }
});


// Make plugin template visible in WP Page editor
add_filter('theme_page_templates', function($templates) {
    $templates['fullscreen-quiz.php'] = 'Fullscreen Quiz (Plugin)';
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

/////////////---
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=interview_question',
        'Import Questions',
        'Import Questions',
        'manage_options',
        'import-questions',
        'import_questions_page'
    );
});

function import_questions_page() {

    if (isset($_POST['import_questions_submit'])) {

        $topic_id = intval($_POST['topic']);
        $data = wp_unslash($_POST['import_data']); // keep HTML intact

        // Split by delimiter
        $posts = array_filter(array_map('trim', explode('===QUESTION===', $data)));
        $imported = 0;

        foreach ($posts as $post_block) {
            if (!$post_block) continue;

            // Split into lines
            $lines = array_map('trim', explode("\n", $post_block));

            // First non-empty line = question/title
            $question = '';
            $answer_lines = [];
            foreach ($lines as $i => $line) {
                if ($line === '') continue;
                if ($question === '') {
                    $question = $line;
                } else {
                    $answer_lines[] = $line;
                }
            }

            if (!$question) continue;

$answer = html_entity_decode(implode("\n", $answer_lines), ENT_QUOTES | ENT_HTML5);

            $post_id = wp_insert_post([
                'post_type'    => 'interview_question',
                'post_title'   => wp_strip_all_tags($question),
                'post_content' => $answer,
                'post_status'  => 'publish',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_interview_question', $question);
                if ($topic_id) wp_set_object_terms($post_id, [$topic_id], 'topic');
                $imported++;
            }
        }

        echo '<div class="notice notice-success"><p>Imported '.$imported.' questions.</p></div>';
    }

    // Get all topics
    $topics = get_terms(['taxonomy' => 'topic', 'hide_empty' => false]);
    ?>
    <div class="wrap">
        <h1>Import Interview Questions</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Select Topic</th>
                    <td>
                        <select name="topic">
                            <option value="0">-- None --</option>
                            <?php foreach ($topics as $t): ?>
                                <option value="<?= esc_attr($t->term_id) ?>"><?= esc_html($t->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Questions (use ===QUESTION=== as delimiter)</th>
                    <td>
                        <textarea name="import_data" rows="20" cols="80" style="width:100%;"></textarea>
                        <p class="description">
                            Enter each post separated by <strong>===QUESTION===</strong>. <br>
                            First line = question/title (meta), remaining lines = answer/content. <br>
                            Supports HTML, Markdown, lists, code blocks, etc. <br><br>
                            <strong>Example:</strong><br>
                            ===QUESTION===<br>
                            What is PHP?<br>
                            PHP is a server-side scripting language used to build dynamic websites.<br><br>
                            ===QUESTION===<br>
                            What is WordPress?<br>
                            WordPress is a CMS built on PHP and MySQL. It allows creating websites quickly and efficiently.<br><br>
                            ===QUESTION===<br>
                            How to debug PHP?<br>
                            Common techniques include:<br>
                            1. var_dump() and print_r()<br>
                            2. Error logs<br>
                            3. Using Xdebug for step debugging
                        </p>

                    </td>
                </tr>
            </table>
            <?php submit_button('Import Questions', 'primary', 'import_questions_submit'); ?>
        </form>
    </div>
    <?php
}
