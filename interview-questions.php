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


require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';
