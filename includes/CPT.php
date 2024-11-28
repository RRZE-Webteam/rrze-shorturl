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

        add_filter('post_row_actions', [$this, 'modify_post_row_actions_for_shorturl_link'], 10, 2);
        add_filter('manage_shorturl_link_posts_columns', [$this, 'add_shorturl_link_custom_columns']);
        add_action('manage_shorturl_link_posts_custom_column', [$this, 'display_shorturl_link_custom_columns'], 10, 2);
        add_filter('manage_edit-shorturl_link_sortable_columns', [$this, 'make_shorturl_link_columns_sortable']);
        add_action('pre_get_posts', [$this, 'sort_shorturl_link_columns']);
        // add_action('pre_get_posts', [$this, 'filter_shorturl_link_admin_query']);

        add_action('add_meta_boxes', [$this, 'register_shorturl_link_metabox']);
        add_action('save_post_shorturl_link', [$this, 'save_shorturl_link_metabox_data']);
        // add_action('admin_notices', [$this, 'display_shorturl_admin_notices']);
        // add_filter('redirect_post_location', [$this, 'suppress_default_post_updated_notice']);

        add_action('add_meta_boxes', [$this, 'customize_publish_metabox_for_shorturl']);
        add_filter('wp_insert_post_data', [$this, 'enforce_publish_status_for_shorturl'], 10, 2);

        add_filter('bulk_actions-edit-shorturl_link', [$this, 'modify_bulk_actions_for_shorturl_link']);
        add_filter('handle_bulk_actions-edit-shorturl_link', [$this, 'handle_delete_permanently_action'], 10, 3);
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
            'post_id' => __('post_id', 'rrze-shorturl'),
            'post_status' => __('post_status', 'rrze-shorturl'),
            'active' => __('Active', 'rrze-shorturl'),
            'title' => __('Long URL', 'rrze-shorturl'),
            'shorturl_generated' => __('ShortURL generated', 'rrze-shorturl'),
            'shorturl_custom' => __('ShortURL custom', 'rrze-shorturl'),
            'uri' => __('URI', 'rrze-shorturl'),
            'date' => $columns['date'],
            'idm' => __('IdM', 'rrze-shorturl'),
            'valid_until' => __('Valid until', 'rrze-shorturl'),
        ];
        return $columns;
    }

    // return values for columns of shorturl_link
    public function display_shorturl_link_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'active':
                echo get_post_meta($post_id, 'active', true) == '1' ? '&#10004;' : '&#10008;';
                break;
            case 'post_id':
                echo $post_id;
                break;
            case 'post_status':
                echo get_post_status($post_id);
                break;
            case 'shorturl_generated':
                echo esc_html(get_post_meta($post_id, 'shorturl_generated', true));
                break;
            case 'shorturl_custom':
                echo esc_html(get_post_meta($post_id, 'shorturl_custom', true));
                break;
            case 'uri':
                echo esc_html(get_post_meta($post_id, 'uri', true));
                break;
            case 'idm':
                echo esc_html(get_post_meta($post_id, 'idm', true));
                break;
            case 'valid_until':
                $valid_until = esc_html(get_post_meta($post_id, 'valid_until', true));
                $valid_until_formatted = (!empty($valid_until) ? date_format(date_create($valid_until), 'd.m.Y') : __('indefinite', 'rrze-shorturl'));
                echo $valid_until_formatted;
                break;
        }
    }

    public function make_shorturl_link_columns_sortable($columns)
    {
        $columns['active'] = 'active';
        $columns['post_id'] = 'post_id';
        $columns['long_url'] = 'long_url';
        $columns['title'] = 'title';
        $columns['shorturl_generated'] = 'shorturl_generated';
        $columns['shorturl_custom'] = 'shorturl_custom';
        $columns['uri'] = 'uri';
        $columns['idm'] = 'idm';
        $columns['valid_until'] = 'valid_until';
        return $columns;
    }

    public function sort_shorturl_link_columns($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'active') {
            $query->set('meta_key', 'active');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'long_url') {
            $query->set('meta_key', 'long_url');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'shorturl_generated') {
            $query->set('meta_key', 'shorturl_generated');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'shorturl_custom') {
            $query->set('meta_key', 'shorturl_custom');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'uri') {
            $query->set('meta_key', 'uri');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'idm') {
            $query->set('meta_key', 'idm');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'valid_until') {
            $query->set('meta_key', 'valid_until');
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
        $long_url = get_the_title($post->ID); 
        $shorturl_generated = get_post_meta($post->ID, 'shorturl_generated', true);
        $shorturl_custom = get_post_meta($post->ID, 'shorturl_custom', true);
        $uri = get_post_meta($post->ID, 'uri', true);
        $idm = get_post_meta($post->ID, 'idm', true);
        $valid_until = get_post_meta($post->ID, 'valid_until', true);

        wp_nonce_field('save_shorturl_link_metabox_data', 'shorturl_link_metabox_nonce');

        echo '<label for="long_url">' . __('Long URL', 'rrze-shorturl') . ':</label>';
        echo '<input type="text" id="short_url" name="long_url" value="' . esc_attr($long_url) . '" readonly><br>';

        echo '<label for="shorturl_generated">' . __('Short URL generated', 'rrze-shorturl') . ':</label>';
        echo '<input type="text" id="shorturl_generated" name="shorturl_generated" value="' . esc_attr($shorturl_generated) . '" readonly><br>';

        echo '<label for="shorturl_custom">' . __('Short URL custom', 'rrze-shorturl') . ':</label>';
        echo '<input type="text" id="shorturl_custom" name="shorturl_custom" value="' . esc_attr($shorturl_custom) . '" readonly><br>';

        echo '<label for="uri">' . __('URI', 'rrze-shorturl') . ':</label>';
        echo '<input type="text" id="uri" name="uri" value="' . esc_attr($uri) . '"><br>';

        echo '<label for="idm">' . __('IdM', 'rrze-shorturl') . ':</label>';
        echo '<input type="text" id="idm" name="idm" value="' . esc_attr($idm) . '" readonly><br>';

        echo '<label for="valid_until">' . __('Valid until', 'rrze-shorturl') . ':</label>';
        echo '<input type="date" id="valid_until" name="valid_until" value="' . esc_attr($valid_until) . '"><br>';
    }

    public function save_shorturl_link_metabox_data($post_id)
    {
        error_log('NEW : in save_shorturl_link_metabox_data() START');

        // Prevent repeated calls using a static flag
        static $is_executing = false;
        if ($is_executing) {
            return;
        }
        $is_executing = true;

        // Ensure this is the correct post type even though we use the hook "save_post_shorturl_link"
        if ('shorturl_link' !== get_post_type($post_id)) {
            return;
        }

        // Skip saving if this is a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Skip saving during an autosave routine
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check if the current user has permission to edit this post
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Verify the nonce to ensure the request is valid
        if (!isset($_POST['shorturl_link_metabox_nonce']) || !wp_verify_nonce($_POST['shorturl_link_metabox_nonce'], 'save_shorturl_link_metabox_data')) {
            return;
        }

        error_log('NEW : in save_shorturl_link_metabox_data() - Validated');

        // Prepare the parameters to pass to the ShortURL shortening logic
        $shortenParams = [
            'customer_idm' => get_post_meta($post_id, 'idm', true),
            'long_url' => get_post_meta($post_id, 'long_url', true),
            'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
            'aCategory' => [],
            'uri' => sanitize_text_field($_POST['uri'] ?? ''),
        ];

        error_log('NEW : $shortenParams = ' . print_r($shortenParams, true));

        // Call the ShortURL shortening function
        $result = ShortURL::shorten($shortenParams);

        error_log('$result = ' . print_r($result, true));

        // Handle error scenarios
        if ($result['error']) {
            // Stop further processing if there's an error
            return;
        }

        error_log('NEW : in save_shorturl_link_metabox_data() - DONE');
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
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'shorturl_link') {
            $query->set('post_status', 'publish');
        }
    }


    public function modify_post_row_actions_for_shorturl_link($actions, $post)
    {
        if ('shorturl_link' === $post->post_type) {
            // remove "Quick Edit"
            unset($actions['inline hide-if-no-js']);
            // remove "Move to Trash"
            unset($actions['trash']);
        }
        return $actions;
    }


    public function customize_publish_metabox_for_shorturl()
    {
        global $post;

        if ('shorturl_link' === get_post_type($post)) {
            remove_meta_box('submitdiv', 'shorturl_link', 'side'); // Entferne die Standard-Metabox
            add_meta_box(
                'custom_submitdiv',
                __('Save'),
                [$this, 'render_custom_submit_meta_box_for_shorturl'],
                'shorturl_link',
                'side',
                'high'
            );
        }
    }

    public function render_custom_submit_meta_box_for_shorturl($post)
    {
        // We must bypass trash and delete permanently => get_delete_post_link($post->ID, '', true);
        // Reason: We want to avoid customers or admins reserving custom or generated URIs unnecessarily.
        // Additionally, some short_links may be inactive (post_meta "active" = 0) because the associated long_url might temporarily return a 404.
        // Such cases are handled by CleanupDB::cleanInvalidLinks().
        ?>
        <div class="submitbox" id="submitpost">
            <div id="major-publishing-actions">
                <div id="publishing-action">
                    <span class="spinner"></span>
                    <input name="original_publish" type="hidden" id="original_publish" value="Save">
                    <input type="submit" name="publish" id="publish" class="button button-primary button-large"
                        value="<?php _e('Save'); ?>">
                </div>
                <div id="delete-action">
                    <a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID, '', true); ?>">
                        <?php _e('Delete Permanently'); ?>
                    </a>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <?php
    }

    // Status must always be "publish" to ensure URI uniqueness.
    // Reason: We want to avoid customers or admins reserving custom or generated URIs unnecessarily.
    // Additionally, some short_links may be inactive (post_meta "active" = 0) because the associated long_url might temporarily return a 404.
    // Such cases are handled by CleanupDB::cleanInvalidLinks().
    public function enforce_publish_status_for_shorturl($data, $postarr)
    {
        if ('shorturl_link' === $data['post_type']) {
            $current_post = get_post($postarr['ID']);
            if ($current_post && $current_post->post_status !== 'trash') {
                $data['post_status'] = 'publish';
            }
        }
        return $data;
    }

    public function modify_bulk_actions_for_shorturl_link($bulk_actions)
    {
        // Remove "Move to Trash" option
        unset($bulk_actions['trash']);

        // Add "Delete Permanently" option
        $bulk_actions['delete_permanently'] = __('Delete Permanently', 'textdomain');

        return $bulk_actions;
    }


    public function handle_delete_permanently_action($redirect_to, $action, $post_ids)
    {
        if ($action !== 'delete_permanently') {
            return $redirect_to;
        }

        // Delete posts permanently
        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true); // true = permanently delete
        }

        // Add query parameter to the redirect URL for user feedback
        $redirect_to = add_query_arg('deleted', count($post_ids), $redirect_to);
        return $redirect_to;
    }

}
