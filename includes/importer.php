<?php

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
