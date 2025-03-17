<?php
/*
Plugin Name: User Tags Plugin
Description: Adds a custom taxonomy "User Tags" to categorize users in WordPress.
Version: 1.3
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Register Custom Taxonomy for Users
function register_user_tags_taxonomy() {
    $args = array(
        'labels'            => array(
            'name'              => 'User Tags',
            'singular_name'     => 'User Tag',
            'menu_name'         => 'User Tags',
        ),
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => false,
        'rewrite'           => false,
    );
    register_taxonomy('user_tags', 'user', $args);
}
add_action('init', 'register_user_tags_taxonomy');

// Add "User Tags" Management Menu Under Users
function add_user_tags_menu() {
    add_users_page('User Tags', 'User Tags', 'manage_options', 'edit-tags.php?taxonomy=user_tags');
}
add_action('admin_menu', 'add_user_tags_menu');

// Add "User Tags" Field to User Profile
function add_user_tags_field($user) {
    $terms = get_terms(['taxonomy' => 'user_tags', 'hide_empty' => false]);
    $user_terms = wp_get_object_terms($user->ID, 'user_tags', ['fields' => 'ids']);
    ?>
    <h3>User Tags</h3>
    <table class="form-table">
        <tr>
            <th><label for="user_tags">Tags :</label></th>
            <td>
                <select name="user_tags[]" id="user_tags" multiple="multiple" style="width: 300px;">
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo $term->term_id; ?>" <?php selected(in_array($term->term_id, $user_terms)); ?>>
                            <?php echo esc_html($term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_user_tags_field');
add_action('edit_user_profile', 'add_user_tags_field');

// Save User Tags
function save_user_tags($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    check_admin_referer('update-user_' . $user_id); // Security check

    if (isset($_POST['user_tags'])) {
        $user_tags = array_map('intval', $_POST['user_tags']);
        wp_set_object_terms($user_id, $user_tags, 'user_tags', false);
    } else {
        wp_set_object_terms($user_id, [], 'user_tags', false);
    }
}
add_action('personal_options_update', 'save_user_tags');
add_action('edit_user_profile_update', 'save_user_tags');

// Filter Users by Tags in Users List
function filter_users_by_tags() {
    if (!is_admin()) return;
    $tags = get_terms(['taxonomy' => 'user_tags', 'hide_empty' => false]);
    ?>
    <div class="filter-container" style="float: right;margin-left:13px;">
    <select id="user_tag_filter" class="user_tag_filter" style="width: 200px;">
        <option value="">Filter by User Tags</option>
        <?php foreach ($tags as $tag) : ?>
            <option value="<?php echo $tag->term_id; ?>">
                <?php echo esc_html($tag->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="button" class="filter_users_btn button button-primary">Filter</button>
    </div>
    <?php
}
add_action('restrict_manage_users', 'filter_users_by_tags');

// AJAX Filter Users by Tag
function filter_users_by_tag_ajax() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access']);
    }

    $user_tag = isset($_POST['user_tag']) ? intval($_POST['user_tag']) : 0;
    
    if ($user_tag) {
        $user_ids = get_objects_in_term($user_tag, 'user_tags');
    } else {
        $user_ids = get_users(['fields' => 'ID']);
    }

    if (empty($user_ids)) {
        wp_send_json_success('<tr><td colspan="5">No users found.</td></tr>');
    }

    $users = get_users([
        'include' => $user_ids,
        'role__in' => ['administrator', 'editor', 'author', 'subscriber'],
    ]);

    ob_start();
    foreach ($users as $user) {
        echo '<tr>
        <td></td>
        <td>' . esc_html($user->user_login) . '</td>
        <td>' . esc_html($user->display_name) . '</td>
        <td>' . esc_html($user->user_email) . '</td>
        <td>' . implode(', ', $user->roles) . '</td>
        <td>' . count_user_posts($user->ID) . '</td>
        </tr>';
    }
    
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_filter_users_by_tag', 'filter_users_by_tag_ajax');

// Enqueue Scripts
function enqueue_scripts($hook) {
    if (!in_array($hook, ['profile.php', 'user-edit.php', 'users.php'])) return;
    
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
    
    wp_enqueue_script('user-tags-js', plugin_dir_url(__FILE__) . 'user-tags.js', array('jquery', 'select2-js'), null, true);
    wp_localize_script('user-tags-js', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
}
add_action('admin_enqueue_scripts', 'enqueue_scripts');