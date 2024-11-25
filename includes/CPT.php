<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;
use RRZE\ShortURL\ShortURL;

class CPT
{

    public function __construct()
    {
        add_action('init', [$this, 'register_idm_cpt']);
        add_action('init', [$this, 'register_domain_cpt']);
        add_action('init', [$this, 'register_service_cpt']);
        add_action('init', [$this, 'register_link_cpt']);
        add_action('init', [$this, 'register_category_cpt']);

        add_filter('manage_shorturl_link_posts_columns', [$this, 'add_shorturl_link_custom_columns']);
        add_action('manage_shorturl_link_posts_custom_column', [$this, 'display_shorturl_link_custom_columns'], 10, 2);
        add_filter('manage_edit-shorturl_link_sortable_columns', [$this, 'make_shorturl_link_columns_sortable']);
        add_action('pre_get_posts', [$this, 'sort_shorturl_link_columns']);
        add_action('pre_get_posts', [$this, 'filter_shorturl_link_admin_query']);

        add_action('add_meta_boxes', [$this, 'register_shorturl_link_metabox']);
        add_action('save_post_shorturl_link', [$this, 'save_shorturl_link_metabox_data']);  
        add_action('admin_notices', [$this, 'display_shorturl_admin_notices']);
        add_filter('redirect_post_location', [$this, 'suppress_default_post_updated_notice']);

      
    }

    // Register Custom Post Type for IDMs
    public function register_idm_cpt()
    {
        try {
            register_post_type('shorturl_idm', array(
                'labels' => array(
                    'name' => __('IDMs'),
                    'singular_name' => __('IDM'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title', 'editor', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_idm_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Domains
    public function register_domain_cpt()
    {
        try {
            register_post_type('shorturl_domain', array(
                'labels' => array(
                    'name' => __('Domains'),
                    'singular_name' => __('Domain'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_domain_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Services
    public function register_service_cpt()
    {
        try {
            register_post_type('shorturl_service', array(
                'labels' => array(
                    'name' => __('Services'),
                    'singular_name' => __('Service'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => true,
                'rewrite' => false,
                'supports' => array('title', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_service_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Links
    public function register_link_cpt()
    {
        try {
            register_post_type('shorturl_link', array(
                'labels' => array(
                    'name' => __('Links'),
                    'singular_name' => __('Link'),
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_link_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Categories
    public function register_category_cpt()
    {
        try {
            register_post_type('shorturl_category', array(
                'labels' => array(
                    'name' => __('Short URL Categories'),
                    'singular_name' => __('Short URL Categories'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'hierarchical' => true, // Makes this CPT hierarchical
                'supports' => array('title', 'editor', 'custom-fields', 'page-attributes'),
            ));

        } catch (CustomException $e) {
            error_log("Error in register_shorturl_category_cpt: " . $e->getMessage());
        }
    }



    // Add columns to table of shorturl_link
    public function add_shorturl_link_custom_columns($columns)
    {
        $columns = [
            'cb' => $columns['cb'],
            'title' => __('Short URL'),
            'long_url' => __('Long URL'),
            'idm' => __('IdM'),
            'date' => $columns['date'],
        ];
        return $columns;
    }

    // return values for columns of shorturl_link
    public function display_shorturl_link_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'long_url':
                echo esc_html(get_post_meta($post_id, 'long_url', true));
                break;
            case 'idm':
                echo esc_html(get_post_meta($post_id, 'idm', true));
                break;
        }
    }

    public function make_shorturl_link_columns_sortable($columns)
    {
        $columns['title'] = 'title';
        $columns['long_url'] = 'long_url';
        $columns['idm'] = 'idm';
        return $columns;
    }

    public function sort_shorturl_link_columns($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'long_url') {
            $query->set('meta_key', 'long_url');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'idm') {
            $query->set('meta_key', 'idm');
            $query->set('orderby', 'meta_value');
        }
    }

    public function register_shorturl_link_metabox()
{
    remove_post_type_support('shorturl_link', 'editor');
    remove_post_type_support('shorturl_link', 'title');

    add_meta_box(
        'shorturl_link_metabox',
        __('Short URL Details', 'rrze-shorturl'),
        [$this, 'render_shorturl_link_metabox'],
        'shorturl_link',
        'normal',
        'high'
    );
}


public function render_shorturl_link_metabox($post)
{
    $long_url = get_post_meta($post->ID, 'long_url', true);
    $short_url = get_post_meta($post->ID, 'short_url', true);
    $uri = get_post_meta($post->ID, 'uri', true);
    $idm = get_post_meta($post->ID, 'idm', true);
    $valid_until = get_post_meta($post->ID, 'valid_until', true);
    $active = get_post_meta($post->ID, 'active', true);

    wp_nonce_field('save_shorturl_link_metabox_data', 'shorturl_link_metabox_nonce');

    echo '<label for="long_url">' . __('Long URL', 'rrze-shorturl') . ':</label>';
    echo '<input type="text" id="short_url" name="long_url" value="' . esc_attr($long_url) . '" readonly><br>';

    echo '<label for="short_url">' . __('Short URL', 'rrze-shorturl') . ':</label>';
    echo '<input type="text" id="short_url" name="short_url" value="' . esc_attr($short_url) . '" readonly><br>';

    echo '<label for="uri">' . __('URI', 'rrze-shorturl') . ':</label>';
    echo '<input type="text" id="uri" name="uri" value="' . esc_attr($uri) . '"><br>';

    echo '<label for="idm">' . __('IdM', 'rrze-shorturl') . ':</label>';
    echo '<input type="text" id="idm" name="idm" value="' . esc_attr($idm) . '" readonly><br>';

    echo '<label for="valid_until">' . __('Valid Until', 'rrze-shorturl') . ':</label>';
    echo '<input type="date" id="valid_until" name="valid_until" value="' . esc_attr($valid_until) . '"><br>';

    echo '<label for="active">' . __('Active', 'rrze-shorturl') . ':</label>';
    echo '<input type="checkbox" id="active" name="active" value="1" ' . checked($active, 1, false) . '>';
}

public function save_shorturl_link_metabox_data($post_id)
{
    // Verify the nonce to ensure the request is valid
    if (!isset($_POST['shorturl_link_metabox_nonce']) || !wp_verify_nonce($_POST['shorturl_link_metabox_nonce'], 'save_shorturl_link_metabox_data')) {
        return;
    }

    // Prevent saving during an autosave routine
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check if the current user has permission to edit this post
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Prepare the parameters to pass to the ShortURL shortening logic
    $shortenParams = [
        'long_url' => get_post_meta($post_id, 'long_url', true),
        'uri' => sanitize_text_field($_POST['uri'] ?? ''),
        'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
    ];

    // Set the 'active' status based on the checkbox
    if (isset($_POST['active'])) {
        $shortenParams['active'] = 1;
    } else {
        $shortenParams['active'] = 0;
    }

    // Call the ShortURL shortening function
    $result = ShortURL::shorten($shortenParams);

    echo '<pre>';
    var_dump($result);
    exit;

    // Handle error scenarios
    if ($result['error']) {
        // Store the error message in a transient
        set_transient('shorturl_error_notice_' . $post_id, $result['txt'], 30);

        return; // Stop further processing if there's an error
    }

    // Store a success message in a transient
    set_transient('shorturl_success_notice_' . $post_id, $result['txt'], 30);

    // Update the metadata for the post with the new ShortURL details
    update_post_meta($post_id, 'uri', $result['uri']);
    update_post_meta($post_id, 'valid_until', $result['valid_until_formatted']);
    update_post_meta($post_id, 'active', $shortenParams['active']);
    update_post_meta($post_id, 'short_url', $result['txt']);
}

// Add admin notices
public function display_shorturl_admin_notices()
{
    global $post;

    if (!$post) {
        return;
    }

    // Check for a success notice
    $success_notice = get_transient('shorturl_success_notice_' . $post->ID);
    if ($success_notice) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html__('ShortURL saved successfully:', 'text-domain') . ' ' . esc_html($success_notice) . '</p>';
        echo '</div>';
        delete_transient('shorturl_success_notice_' . $post->ID);
    }

    // Check for an error notice
    $error_notice = get_transient('shorturl_error_notice_' . $post->ID);
    if ($error_notice) {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . esc_html__('Error saving ShortURL:', 'text-domain') . ' ' . esc_html($error_notice) . '</p>';
        echo '</div>';
        delete_transient('shorturl_error_notice_' . $post->ID);
    }
}

public function suppress_default_post_updated_notice($location)
{
    // Check if the current post type is 'shorturl_link'
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : (isset($_POST['post_ID']) ? absint($_POST['post_ID']) : 0);

    if ($post_id) {
        $post_type = get_post_type($post_id);

        if ($post_type === 'shorturl_link') {
            // Remove the 'message' parameter from the redirect URL
            $location = remove_query_arg('message', $location);
        }
    }

    return $location;
}


public function filter_shorturl_link_admin_query($query)
{
    // Check if this is the main query and in the admin area
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'shorturl_link') {
        // Ensure only posts with 'publish' status are shown
        $query->set('post_status', 'publish');
    }
}


}
