<?php

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

        // Split by <h2> tags
        preg_match_all('/<h2>(.*?)<\/h2>/is', $data, $matches, PREG_OFFSET_CAPTURE);

        $imported = 0;

        for ($i = 0; $i < count($matches[0]); $i++) {
            $title = trim($matches[1][$i][0]);
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $end = isset($matches[0][$i+1]) ? $matches[0][$i+1][1] : strlen($data);
            $content = trim(substr($data, $start, $end - $start));

            if (!$title) continue;

            $post_id = wp_insert_post([
                'post_type'    => 'interview_question',
                'post_title'   => wp_strip_all_tags($title),
                'post_content' => $content,
                'post_status'  => 'publish',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_interview_question', $title);
                if ($topic_id) wp_set_object_terms($post_id, [$topic_id], 'topic');
                $imported++;
            }
        }

        echo '<div class="notice notice-success"><p>Imported '.$imported.' questions.</p></div>';
    }

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
                    <th scope="row">Questions (use &lt;h2&gt; as delimiter)</th>
                    <td>
                        <textarea name="import_data" rows="20" cols="80" style="width:100%;"></textarea>
                        <p class="description">
                            Each question starts with <strong>&lt;h2&gt;</strong>Title&lt;/h2&gt;. <br>
                            The heading becomes the post title, rest is content until next <strong>&lt;h2&gt;</strong>. <br>
                            Example:<br>
                            &lt;h2&gt;What is PHP?&lt;/h2&gt;<br>
                            PHP is a server-side scripting language.<br><br>
                            &lt;h2&gt;What is WordPress?&lt;/h2&gt;<br>
                            WordPress is a CMS built on PHP and MySQL.
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Import Questions', 'primary', 'import_questions_submit'); ?>
        </form>
    </div>
    <?php
}
