<?php

namespace RRZE\ShortURL;

class Taxonomy
{
    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_taxonomies']);

        add_filter('manage_shorturl_links_posts_columns', [$this, 'custom_shorturl_links_columns']);
        add_action('manage_shorturl_links_posts_custom_column', [$this, 'custom_shorturl_links_column'], 10, 2);
        add_filter('manage_edit-shorturl_links_columns', [$this, 'remove_default_columns']);

        add_filter('manage_edit-shorturl_links_sortable_columns', [$this, 'custom_shorturl_links_sortable_columns']);

        add_action('add_meta_boxes', [$this, 'custom_shorturl_links_metabox']);
        add_action('save_post', [$this, 'custom_shorturl_links_save_valid_until']);

    }

    public function register_cpt()
    {
        $labels = array(
            'name' => __('Short URL Links', 'rrze-shorturl'),
            'singular_name' => __('Short URL Link', 'rrze-shorturl'),
            'menu_name' => __('Short URL Links', 'rrze-shorturl'),
            'name_admin_bar' => __('Short URL Link', 'rrze-shorturl'),
            'add_new' => __('Add New', 'rrze-shorturl'),
            'add_new_item' => __('Add New Short URL Link', 'rrze-shorturl'),
            'new_item' => __('New Short URL Link', 'rrze-shorturl'),
            'edit_item' => __('Edit Short URL Link', 'rrze-shorturl'),
            'view_item' => __('View Short URL Link', 'rrze-shorturl'),
            'all_items' => __('All Short URL Links', 'rrze-shorturl'),
            'search_items' => __('Search Short URL Links', 'rrze-shorturl'),
            'parent_item_colon' => __('Parent Short URL Links:', 'rrze-shorturl'),
            'not_found' => __('No short URL links found.', 'rrze-shorturl'),
            'not_found_in_trash' => __('No short URL links found in Trash.', 'rrze-shorturl')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'capability_type' => 'shorturl_link', // Define a custom capability
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-admin-links',
            'query_var' => true,
            'rewrite' => array('slug' => 'shorturl_links'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'taxonomies' => array('shorturl_category', 'shorturl_tag'), // Assign custom taxonomies here
            'capabilities' => array(
                'edit_post' => 'edit_shorturl_link', // Custom capability for editing
                'read_post' => 'read_shorturl_link', // Custom capability for reading
                'delete_post' => 'delete_shorturl_link', // Custom capability for deleting
                'create_posts' => 'do_not_allow', // Disallow creation of new posts
            ),
        );
        register_post_type('shorturl_links', $args);
    }

    public function register_taxonomies()
    {
        $labels = array(
            'name' => _x('ShortURL Categories', 'taxonomy general name', 'textdomain'),
            'singular_name' => _x('ShortURL Category', 'taxonomy singular name', 'textdomain'),
            'search_items' => __('Search ShortURL Categories', 'textdomain'),
            'popular_items' => __('Popular ShortURL Categories', 'textdomain'),
            'all_items' => __('All ShortURL Categories', 'textdomain'),
            'edit_item' => __('Edit ShortURL Category', 'textdomain'),
            'update_item' => __('Update ShortURL Category', 'textdomain'),
            'add_new_item' => __('Add New ShortURL Category', 'textdomain'),
            'new_item_name' => __('New ShortURL Category name', 'textdomain'),
            'separate_items_with_commas' => __('Separate ShortURL Categories with commas', 'textdomain'),
            'add_or_remove_items' => __('Add or remove ShortURL Categories', 'textdomain'),
            'choose_from_most_used' => __('Choose from the most used ShortURL Categories', 'textdomain'),
            'not_found' => __('No ShortURL Categories found', 'textdomain'),
            'menu_name' => __('ShortURL Categories', 'textdomain'),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'show_in_rest' => true, // Enable REST API support
            'rewrite' => array('slug' => 'shorturl_category'), // Change 'shorturl_category' to desired slug
        );

        register_taxonomy('shorturl_category', 'shorturl_links', $args);

        $labels = array(
            'name' => __('ShortURL Tags', 'text-domain'),
            'singular_name' => __('ShortURL Tag', 'text-domain'),
            'search_items' => __('Search ShortURL Tags', 'text-domain'),
            'popular_items' => __('Popular ShortURL Tags', 'text-domain'),
            'all_items' => __('All ShortURL Tags', 'text-domain'),
            'edit_item' => __('Edit ShortURL Tag', 'text-domain'),
            'update_item' => __('Update ShortURL Tag', 'text-domain'),
            'add_new_item' => __('Add New ShortURL Tag', 'text-domain'),
            'new_item_name' => __('New ShortURL Tag Name', 'text-domain'),
            'separate_items_with_commas' => __('Separate ShortURL tags with commas', 'text-domain'),
            'add_or_remove_items' => __('Add or remove ShortURL tags', 'text-domain'),
            'choose_from_most_used' => __('Choose from the most used ShortURL tags', 'text-domain'),
            'not_found' => __('No ShortURL tags found', 'text-domain'),
            'menu_name' => __('ShortURL Tags', 'text-domain'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false, // Set to false for non-hierarchical taxonomy (like tags)
            'show_in_rest' => true, // Enable REST API support
            'rewrite' => array('slug' => 'shorturl_tag'), // Set the slug to 'shorturl_tag'
        );

        register_taxonomy('shorturl_tag', array('shorturl_links'), $args); // 'shorturl_links' is the post type to which the taxonomy will be associated
    }

    public function remove_default_columns($columns)
    {
        // Unset the 'author' column
        unset($columns['author']);
        unset($columns['title']);
        return $columns;
    }

    public function custom_shorturl_links_columns($columns)
    {
        // Define the order of columns
        $new_columns = array(
            'cb' => $columns['cb'], // Leave the checkbox column first
            'shorturl_id' => __('ShortURL ID'), // Add ShortURL ID column
            'long_url' => __('Long URL'), // Add Long URL column
            'short_url' => __('Short URL'), // Add Short URL column
            'valid_until' => __('Valid Until'), // Add Valid Until column
            'shorturl_category' => __('Categories'), // Add Categories column
            'shorturl_tag' => __('Tags'), // Add Tags column
        );

        return $new_columns;
    }

    public function custom_shorturl_links_sortable_columns($columns)
    {
        $columns['shorturl_id'] = 'shorturl_id';
        $columns['long_url'] = 'long_url';
        $columns['short_url'] = 'short_url';
        $columns['valid_until'] = 'valid_until';

        return $columns;
    }

    // Display data in custom column
    public function custom_shorturl_links_column($column, $post_id)
    {
        switch ($column) {
            case 'shorturl_id':
                $shorturl_id = get_post_meta($post_id, 'shorturl_id', true);
                echo esc_html($shorturl_id);
                break;

            case 'long_url':
                $long_url = get_post_meta($post_id, 'long_url', true);
                echo esc_html($long_url);
                break;

            case 'short_url':
                $short_url = get_post_meta($post_id, 'short_url', true);
                echo esc_html($short_url);
                break;

            case 'valid_until':
                $valid_until = get_post_meta($post_id, 'valid_until', true);
                if (!empty($valid_until)) {
                    $timestamp = strtotime($valid_until);
                    $formatted_date = date('d.m.Y H:i', $timestamp); // Adjust the format as needed
                    echo esc_html($formatted_date);
                } else {
                    echo 'No valid date'; // Or any message you want to show if the date is empty
                }
                break;
            case 'shorturl_category':
                $categories = get_the_terms($post_id, 'shorturl_category');
                if ($categories && !is_wp_error($categories)) {
                    $category_names = array();
                    foreach ($categories as $category) {
                        $category_names[] = $category->name;
                    }
                    echo esc_html(implode(', ', $category_names));
                } else {
                    echo '';
                }
                break;
            case 'shorturl_tag':
                $tags = get_the_terms($post_id, 'shorturl_tag');
                if ($tags && !is_wp_error($tags)) {
                    $tag_names = array();
                    foreach ($tags as $tag) {
                        $tag_names[] = $tag->name;
                    }
                    echo esc_html(implode(', ', $tag_names));
                } else {
                    echo '';
                }
                break;
            default:
                // Handle other column cases if needed
                break;
        }
    }


    // Register the metabox
    function custom_shorturl_links_metabox()
    {
        add_meta_box(
            'shorturl_links_valid_until_metabox',
            __('Validation Date'),
            [$this, 'custom_shorturl_links_valid_until_metabox_content'], // Callback function
            'shorturl_links',
            'side',
            'default'
        );
    }

    // Display the metabox content
    public function custom_shorturl_links_valid_until_metabox_content($post)
    {
        // Retrieve the current value of the valid_until meta field
        $valid_until = get_post_meta($post->ID, 'valid_until', true);

        // Convert the date string to a format compatible with datetime-local input
        $datetime_local_value = date('Y-m-d\TH:i', strtotime($valid_until));

        ?>
        <label for="valid_until">
            <?php _e('Validation Date:'); ?>
        </label>
        <br>
        <input type="datetime-local" id="valid_until" name="valid_until" value="<?php echo esc_attr($datetime_local_value); ?>">
        <?php
        wp_nonce_field('custom_shorturl_links_save_valid_until', 'custom_shorturl_links_valid_until_nonce');
    }

    // Save the metabox data
    public function custom_shorturl_links_save_valid_until($post_id)
    {
        // Check if nonce is set
        if (!isset($_POST['custom_shorturl_links_valid_until_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['custom_shorturl_links_valid_until_nonce'], 'custom_shorturl_links_save_valid_until')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save the data
        if (isset($_POST['valid_until'])) {
            update_post_meta($post_id, 'valid_until', sanitize_text_field($_POST['valid_until']));
        }
    }


}
