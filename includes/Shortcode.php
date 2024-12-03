<?php

namespace RRZE\ShortURL;


class Shortcode
{
    protected static $rights;
    protected static $update_message;

    public function __construct($rights)
    {
        self::$rights = $rights;

        add_filter('wp_kses_allowed_html', [$this, 'my_custom_allowed_html'], 10, 2);

        add_shortcode('shorturl', [$this, 'shorturl_handler']);
        add_shortcode('shorturl-list', [$this, 'shortcode_list_handler']);
        add_shortcode('shorturl-categories', [$this, 'shortcode_categories_handler']);
        add_shortcode('shorturl-services', [$this, 'shortcode_services_handler']);
        add_shortcode('shorturl-customer-domains', [$this, 'shortcode_customer_domains_handler']);

        add_action('wp_ajax_nopriv_store_shorturl_link_category', [$this, 'store_shorturl_link_category_callback']);
        add_action('wp_ajax_store_shorturl_link_category', [$this, 'store_shorturl_link_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_category', [$this, 'add_shorturl_category_callback']);
        add_action('wp_ajax_add_shorturl_category', [$this, 'add_shorturl_category_callback']);

        add_action('wp_ajax_nopriv_update_category_label_action', [$this, 'update_category_label']);
        add_action('wp_ajax_update_category_label_action', [$this, 'update_category_label']);

        add_action('wp_ajax_nopriv_update_shorturl_category_label', [$this, 'update_category_label']);
        add_action('wp_ajax_update_shorturl_category_label', [$this, 'update_category_label']);

        add_action('wp_ajax_nopriv_delete_link', [$this, 'delete_link_callback']);
        add_action('wp_ajax_delete_link', [$this, 'delete_link_callback']);

        add_action('wp_ajax_nopriv_delete_category', [$this, 'delete_category_callback']);
        add_action('wp_ajax_delete_category', [$this, 'delete_category_callback']);
    }

    public function my_custom_allowed_html($allowed_tags, $context)
    {
        if ('post' === $context) {
            // Add the <select> tag and its attributes
            $allowed_tags['select'] = array(
                'name' => true,
                'id' => true,
                'class' => true,
                'multiple' => true,
                'size' => true,
            );

            // Add the <option> tag and its attributes
            $allowed_tags['option'] = array(
                'value' => true,
                'selected' => true,
            );

            // Add the <input> tag and its attributes
            $allowed_tags['input'] = array(
                'type' => true,
                'name' => true,
                'id' => true,
                'class' => true,
                'value' => true,
                'placeholder' => true,
                'checked' => true,
                'disabled' => true,
                'readonly' => true,
                'maxlength' => true,
                'size' => true,
                'min' => true,
                'max' => true,
                'step' => true,
            );
        }

        return $allowed_tags;
    }


    public function shortcode_services_handler(): string
    {
        $services = ShortURL::getServices();

        $html = '<table class="shorturl-wp-list-table widefat">';
        $html .= '<thead><tr><th>' . __('Service Name', 'text-domain') . '</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($services as $service) {
            $html .= '<tr><td>' . esc_html($service['hostname']) . '</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    public function shortcode_customer_domains_handler(): string
    {
        $domains = ShortURL::getAllowedDomains();

        $html = '<table class="shorturl-wp-list-table widefat">';
        $html .= '<thead><tr><th>' . __('Domains', 'text-domain') . '</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($domains as $domain) {
            if ($domain['active']) {
                $html .= '<tr><td>' . esc_html($domain['hostname']) . '</td></tr>';
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }

    public static function makeCategoryDropdown($category_id = 0, $parent_id = 0)
    {
        if (!self::$rights['idm']) {
            return '';
        }

        $category_list = self::get_categories_hierarchically();

        // Start building the dropdown HTML output
        $output = '<label for="parent_category">' . __('Parent Category', 'rrze-shorturl') . ':</label>';
        $output .= '<select id="parent_category" name="parent_category">';
        $output .= '<option value="0">' . __('None', 'rrze-shorturl') . '</option>';

        foreach ($category_list as $category) {
            $selected = (!empty($parent_id) && ($category->ID == $parent_id)) ? 'SELECTED' : '';
            $output .= '<option value="' . esc_attr($category->ID) . '" ' . $selected . '>' . esc_html($category->hierarchy_nbsp . $category->post_title) . '</option>';
        }

        $output .= '</select>';

        return $output;
    }


    public function shortcode_categories_handler(): string
    {
        // Edit Category
        if (!empty($_POST['edit_category']) && !empty($_POST['category_id']) && !empty($_POST['category_label'])) {
            $category_id = (int) sanitize_text_field(wp_unslash($_POST['category_id']));
            $category_label = sanitize_text_field(wp_unslash($_POST['category_label']));
            $parent_category = !empty($_POST['parent_category']) ? (int) sanitize_text_field(wp_unslash($_POST['parent_category'])) : 0;
            $idm = self::$rights['idm'];

            // Update the category using wp_update_post
            $args = [
                'ID' => $category_id,
                'post_title' => $category_label,
                'post_parent' => $parent_category,
            ];
            $result = wp_update_post($args);

            if (is_wp_error($result)) {
                return new WP_Error('update_failed', __('Failed to update the category.', 'rrze-shorturl'), array('status' => 500));
            }

            // Update idm meta field
            if (!empty($idm)) {
                update_post_meta($category_id, 'idm', $idm);
            }

            // Return to the table after editing
            return $this->display_categories_table();
        } elseif (!empty($_POST['add_category'])) {
            // Add Category
            $category_label = sanitize_text_field(wp_unslash($_POST['category_label']));
            $parent_category = !empty($_POST['parent_category']) ? (int) sanitize_text_field(wp_unslash($_POST['parent_category'])) : 0;
            $idm = self::$rights['idm'];

            if (!empty($category_label)) {
                // Insert Category as a new post in the 'shorturl_category' CPT
                $args = [
                    'post_title' => $category_label,
                    'post_name' => sanitize_title($category_label),
                    'post_type' => 'shorturl_category',
                    'post_status' => 'publish',
                    'post_parent' => $parent_category,
                ];
                $category_id = wp_insert_post($args);

                if (is_wp_error($category_id)) {
                    return new WP_Error('insert_failed', __('Failed to add category.', 'rrze-shorturl'), array('status' => 500));
                }

                // Store idm as post meta
                if (!empty($idm)) {
                    update_post_meta($category_id, 'idm', $idm);
                }
            }

            return $this->display_categories_table();
        }

        // Check if an edit form should be displayed
        if (!empty($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'edit_category' && !empty($_GET['category_id'])) {
            // Retrieve category details based on category ID using get_post
            $category_id = (int) sanitize_text_field(wp_unslash($_GET['category_id']));
            $category = get_post($category_id);

            // If category is found, display edit form
            if ($category && $category->post_type === 'shorturl_category') {
                $category_label = esc_attr($category->post_title);
                $parent_id = $category->post_parent ?: 0;

                // Start building the form
                $output = '<form method="post">';
                $output .= '<label for="category_label">' . __('Category Label', 'rrze-shorturl') . ':</label><br>';
                $output .= '<input type="text" id="category_label" name="category_label" value="' . esc_attr($category_label) . '"><br>';

                // Display parent category dropdown
                $output .= self::makeCategoryDropdown($category_id, $parent_id);
                $output .= '<input type="hidden" name="category_id" value="' . esc_attr($category_id) . '">';
                $output .= '<br><input type="submit" name="edit_category" value="' . esc_attr__('Save Changes', 'rrze-shorturl') . '">';
                $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . esc_attr__('Cancel', 'rrze-shorturl') . '</a>';

                $output .= '</form>';

                return $output;
            }
        } elseif (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'add_new_category') {
            // Display add category form
            return $this->add_category_form();
        }

        // If no editing is happening, display the categories table
        return $this->display_categories_table();
    }
    private static function get_categories_hierarchically()
    {
        $ret = [];

        // Fetch all categories for the current IdM using get_posts
        $args = [
            'post_type' => 'shorturl_category',
            'posts_per_page' => -1,  // Fetch all categories
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'idm',
                    'value' => self::$rights['idm'],
                    'compare' => '='
                ]
            ],
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],  // Sortiere nach Reihenfolge und Titel
        ];

        // WP_Query statt get_posts verwenden, um die Hierarchie besser zu verarbeiten
        $query = new \WP_Query($args);

        $ret = self::build_category_hierarchy($query->posts);

        return $ret;
    }

    private static function build_category_hierarchy($categories, $parent_id = 0, $depth = 0)
    {
        $result = [];

        foreach ($categories as $category) {
            if ($category->post_parent == $parent_id) {
                $category_data = (object) [
                    'ID' => $category->ID,
                    'post_parent' => $category->post_parent,
                    'post_title' => $category->post_title,
                    'hierarchy' => str_repeat('-', $depth),
                    'hierarchy_nbsp' => str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth),
                ];

                $result[] = $category_data;

                $children = self::build_category_hierarchy($categories, $category->ID, $depth + 1);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    // Helper function to display the categories table
    private function display_categories_table()
    {

        $categories_with_hierarchy = self::get_categories_hierarchically();

        // Start building the table
        $output = '<table class="shorturl-wp-list-table widefat">';
        $output .= '<thead><tr>';
        $output .= '<th scope="col" class="manage-column column-label">' . __('Category', 'rrze-shorturl') . '</th>';
        $output .= '<th scope="col" class="manage-column column-actions">' . __('Actions', 'rrze-shorturl') . '</th>';
        $output .= '</tr></thead>';
        $output .= '<tbody>';

        // Loop through each category
        foreach ($categories_with_hierarchy as $category) {
            // Get category ID and label
            $category_id = $category->ID;
            $category_label = $category->post_title;

            // Build the table row
            $output .= '<tr>';
            $output .= '<td class="column-label">' . esc_html($category->hierarchy_nbsp . $category_label) . '</td>';
            $output .= '<td class="column-actions">
                            <a href="?action=edit_category&category_id=' . esc_attr($category_id) . '">' . __('Edit', 'rrze-shorturl') . '</a> | 
                            <a href="" class="delete-category" data-category-id="' . esc_attr($category_id) . '">' . __('Delete', 'rrze-shorturl') . '</a>
                        </td>';
            $output .= '</tr>';
        }

        // Add row for actions (add new category)
        $output .= '<tr>';
        $output .= '<td colspan="2" class="column-actions"><a href="?action=add_new_category">' . __('Add New Category', 'rrze-shorturl') . '</a></td>';
        $output .= '</tr>';

        $output .= '</tbody></table>';

        return $output;
    }

    private function add_category_form()
    {
        $output = '<h2>' . __('Add New Category', 'rrze-shorturl') . '</h2>';
        $output .= '<form method="post">';
        $output .= '<label for="category_label">' . __('Category Label', 'rrze-shorturl') . ':</label><br>';
        $output .= '<input type="text" id="category_label" name="category_label" value=""><br>';
        $output .= self::makeCategoryDropdown();
        $output .= '<br><input type="submit" name="add_category" value="' . __('Add Category', 'rrze-shorturl') . '">';
        $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';
        $output .= '</form>';

        return $output;
    }

    // We no longer check HTTP response codes, so 'active' post_meta is unused 
    // see: https://github.com/RRZE-Webteam/rrze-shorturl/issues/123
    private function get_link_data_by_id($link_id)
    {
        // Fetch the link post using WP_Query
        $link_query = new \WP_Query([
            'post_type' => 'shorturl_link',
            'posts_per_page' => 1,
            'p' => $link_id // Fetch by specific post ID
        ]);

        // Initialize result array
        $result = [];

        if ($link_query->have_posts()) {
            $link_query->the_post();

            $post_id = get_the_ID();

            $result = [
                'id' => $post_id,
                'long_url' => get_the_title($post_id),
                'shorturl_generated' => get_post_meta($post_id, 'shorturl_generated', true),
                'shorturl_custom' => get_post_meta($post_id, 'shorturl_custom', true),
                'uri' => get_post_meta($post_id, 'uri', true),
                'idm' => get_post_meta($post_id, 'idm', true),
                'domain_id' => get_post_meta($post_id, 'domain_id', true),
                'created_at' => get_post_meta($post_id, 'created_at', true),
                'updated_at' => get_post_meta($post_id, 'updated_at', true),
                'deleted_at' => get_post_meta($post_id, 'deleted_at', true),
                'valid_until' => get_post_meta($post_id, 'valid_until', true)
                // , 'active' => get_post_meta($post_id, 'active', true)
            ];

            // Fetch associated category IDs (assuming relationships are managed by post meta)
            $category_ids = get_post_meta($post_id, 'category_id', false);
            $result['category_ids'] = $category_ids ? implode(',', (array) $category_ids) : '';
        }

        // Reset post data after query
        wp_reset_postdata();

        return $result;
    }


    public function delete_link_callback()
    {
        // Check if the request is coming from a valid source
        check_ajax_referer('delete_shorturl_link_nonce', '_ajax_nonce');

        // Get the link ID from the AJAX request
        $link_id = !empty($_POST['link_id']) ? (int) $_POST['link_id'] : 0;

        if ($link_id > 0) {
            // Delete the post using wp_delete_post
            $result = wp_delete_post($link_id, true); // 'true' forces permanent deletion

            if ($result !== false) {
                // Link deleted successfully
                wp_send_json_success(__('Link deleted successfully', 'rrze-shorturl'));
            } else {
                // Error deleting link
                wp_send_json_error(__('Error deleting link', 'rrze-shorturl'));
            }
        } else {
            // Invalid link ID
            wp_send_json_error(__('Invalid link ID', 'rrze-shorturl'));
        }

        // Always exit to avoid further execution
        wp_die();
    }

    public function delete_category_callback()
    {
        // Check if the request is coming from a valid source
        check_ajax_referer('delete_shorturl_category_nonce', '_ajax_nonce');

        // Get the category ID from the AJAX request
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : 0;

        if ($category_id > 0) {
            // Get the category post to be deleted
            $category = get_post($category_id);

            if (!$category || $category->post_type !== 'shorturl_category') {
                wp_send_json_error(__('Invalid category ID', 'rrze-shorturl'));
            }

            $parent_id = $category->post_parent;

            // Fetch child categories that have the category being deleted as a parent
            $args = [
                'post_type' => 'shorturl_category',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'post_parent' => $category_id,
            ];

            $child_categories = get_posts($args);

            // Update child categories to inherit the parent_id of the category being deleted
            foreach ($child_categories as $child_category) {
                // Update child categories to inherit the parent category
                wp_update_post([
                    'ID' => $child_category->ID,
                    'post_parent' => $parent_id ? $parent_id : 0,
                ]);
            }

            // Delete the category using wp_delete_post
            $result = wp_delete_post($category_id, true);

            if ($result) {
                // Category deleted successfully
                wp_send_json_success(__('Category deleted successfully', 'rrze-shorturl'));
            } else {
                // Error deleting category
                wp_send_json_error(__('Error deleting category', 'rrze-shorturl'));
            }
        } else {
            // Invalid category ID
            wp_send_json_error(__('Invalid category ID', 'rrze-shorturl'));
        }

        // Always exit to avoid further execution
        wp_die();
    }


    public function shorturl_handler($atts = null): string
    {
        $aParams = [
            'long_url' => (!empty($_POST['long_url']) ? sanitize_text_field(wp_unslash($_POST['long_url'])) : (!empty($_GET['long_url']) ? sanitize_text_field(wp_unslash($_GET['long_url'])) : '')),
            'uri' => self::$rights['allow_uri'] ? sanitize_text_field(wp_unslash($_POST['uri'] ?? '')) : '',
            'valid_until' => sanitize_text_field(wp_unslash($_POST['valid_until'] ?? '')),
            'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', wp_unslash($_POST['categories'])) : [],
            'utm_source' => (!empty($_POST['utm_source']) ? sanitize_text_field(wp_unslash($_POST['utm_source'])) : ''),
            'utm_medium' => (!empty($_POST['utm_medium']) ? sanitize_text_field(wp_unslash($_POST['utm_medium'])) : ''),
            'utm_campaign' => (!empty($_POST['utm_campaign']) ? sanitize_text_field(wp_unslash($_POST['utm_campaign'])) : ''),
            'utm_term' => (!empty($_POST['utm_term']) ? sanitize_text_field(wp_unslash($_POST['utm_term'])) : ''),
            'utm_content' => (!empty($_POST['utm_content']) ? sanitize_text_field(wp_unslash($_POST['utm_content'])) : ''),
        ];

        $result_message = ''; // Initialize result message
        // Check if form is submitted
        if ((isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") || !empty($_GET['long_url'])) {
            // Check if URL is provided
            if (!empty($aParams['long_url'])) {


                $result = ShortURL::shorten($aParams);

                if ($result['error']) {
                    $result_message = esc_html($result['message']);
                } else {
                    $shortened_url = (!empty($result['shorturl_custom']) ? $result['shorturl_custom'] : $result['shorturl_generated']);
                    $result_message = '<span class="shorturl-shortened-msg"><span class="label">' . esc_html__('Short URL', 'rrze-shorturl') . ':</span> <code>' . esc_html($shortened_url) . '</code></span>';
                    $result_message .= '<button type="button" class="btn" id="copyButton" name="copyButton" data-shortened-url="' . esc_attr($shortened_url) . '"><img class="shorturl-copy-img" src="data:image/svg+xml,%3Csvg height=\'1024\' width=\'896\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z\' fill=\'%23000000\' /%3E%3C/svg%3E" alt="' . esc_attr__('Copy to clipboard', 'rrze-shorturl') . '"><span class="screen-reader-text">' . esc_html__('Copy to clipboard', 'rrze-shorturl') . '</span></button><span id="shorturl-tooltip" class="shorturl-tooltip">' . esc_html__('Copied to clipboard', 'rrze-shorturl') . '</span>';
                    $result_message .= '<br><span class="shorturl-validuntil"><span class="label">' . esc_html__('Valid until', 'rrze-shorturl') . ':</span> ' . esc_html($result['valid_until_formatted']) . '</span>';
                }

                $aParams['long_url'] = $result['long_url']; // we might have added the scheme
            }
        }

        // Generate form
        $form = '<div class="rrze-shorturl"><form id="shorturl-form" method="post">';
        $form .= '<div class="postbox">';
        $form .= '<h2 class="handle">' . esc_html__('Create Short URL', 'rrze-shorturl') . '</h2>';
        $form .= '<div class="inside">';
        $form .= '<label for="long_url">' . esc_html__('Your link', 'rrze-shorturl') . ':</label>';
        $form .= '<input type="text" name="long_url" id="long_url" value="' . esc_attr($aParams['long_url']) . '" placeholder="https://" ' . (!empty($result['error']) ? ' aria-invalid="true" aria-errormessage="shorturl-err" ' : '') . '>';
        $form .= '<input type="submit" id="generate" name="generate" value="' . esc_html__('Shorten', 'rrze-shorturl') . '">';
        $form .= '<input type="hidden" name="link_id" value="' . esc_attr(!empty($result['link_id']) ? $result['link_id'] : '') . '">';
        $form .= '</div>';
        $form .= '</div>';
        $form .= '<button id="btn-show-advanced-settings" type="button" aria-haspopup="true" aria-controls="shorturl-advanced-settings" aria-expanded="false">' . esc_html__('Advanced Settings', 'rrze-shorturl') . '<span class="arrow-down"></span></button>';
        $form .= '<div id="shorturl-advanced-settings" class="shorturl-advanced-settings">';
        if (self::$rights['allow_uri']) {
            $form .= self::display_shorturl_uri($aParams['uri']);
        }
        $form .= self::display_shorturl_validity($aParams['valid_until']);
        if (self::$rights['allow_utm']) {
            $form .= self::display_shorturl_utm($aParams);
        }
        $form .= '<h6 class="handle">' . esc_html__('Categories', 'rrze-shorturl') . '</h6>';
        $form .= self::display_shorturl_category($aParams['categories']);
        $form .= '</div>';

        // Display result message
        // notice or error msg
        $form .= '<div class="rrze-shorturl-result"><p' . (!empty($result['error']) ? ' id="shorturl-err" class="shorturl-msg-' . esc_attr($result['message_type']) . '"' : '') . '>' . $result_message . '</p>';

        if (!empty($result) && !$result['error']) {
            $shortened_url = (!empty($result['shorturl_custom']) ? $result['shorturl_custom'] : $result['shorturl_generated']);
            $form .= '<input id="shortened_url" name="shortened_url" type="hidden" value="' . esc_attr($shortened_url) . '">';
            $form .= '<div id="qr-container"><canvas id="qr"></canvas><button type="button" class="btn" id="downloadButton" name="downloadButton"><img class="shorturl-download-img" src="data:image/svg+xml,%3Csvg width=\'512\' height=\'512\' viewBox=\'0 0 512 512\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'%23000000\'%3E%3Cpath d=\'M376.3 304.3l-71.4 71.4V48c0-8.8-7.2-16-16-16h-48c-8.8 0-16 7.2-16 16v327.6l-71.4-71.4c-6.2-6.2-16.4-6.2-22.6 0l-22.6 22.6c-6.2 6.2-6.2 16.4 0 22.6l128 128c6.2 6.2 16.4 6.2 22.6 0l128-128c6.2-6.2 6.2-16.4 0-22.6l-22.6-22.6c-6.2-6.2-16.4-6.2-22.6 0zM464 448H48c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16z\'/%3E%3C/svg%3E" title="' . esc_attr__('Download QR', 'rrze-shorturl') . '"><span class="screen-reader-text">' . esc_html__('Download QR', 'rrze-shorturl') . '</span></button></div>';
        }
        $form .= '</form></div>';

        return $form;
    }

    public static function display_shorturl_validity($val)
    {
        ob_start();

        ?>
        <label for="valid_until">
            <?php echo esc_html__('Valid until', 'rrze-shorturl'); ?>:
        </label>
        <input type="date" id="valid_until" name="valid_until" value="<?php echo esc_html($val); ?>">
        <?php
        return ob_get_clean();

    }

    public static function display_shorturl_category($aVal = [])
    {
        if (!self::$rights['idm']) {
            return;
        }

        // Build hierarchical category structure
        $hierarchicalCategories = self::get_categories_hierarchically();

        // Output HTML
        ob_start();
        ?>
        <div id="shorturl-category-metabox">
            <?php self::display_hierarchical_categories_checkbox($hierarchicalCategories, 0, $aVal); ?>
            <p><a href="#" id="add-new-shorturl-category">
                    <?php echo esc_html__('Add New Category', 'rrze-shorturl'); ?>
                </a></p>
            <div id="new-shorturl-category">
                <label for="new_shorturl_category"><?php echo esc_html__('New Category Name', 'rrze-shorturl'); ?>:</label>
                <input type="text" id="new_shorturl_category" name="new_shorturl_category"
                    placeholder="<?php echo esc_html__('New Category Name', 'rrze-shorturl'); ?>">
                <br><?php echo wp_kses_post(self::makeCategoryDropdown()); ?>
                <input type="hidden" name="category_ids" value="<?php echo esc_attr(implode(',', $aVal)); ?>">

                <br><input type="button" value="<?php echo esc_html__('Add new category', 'rrze-shorturl'); ?>"
                    id="add-shorturl-category-btn">
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    // Function to display hierarchical categories
    private static function display_hierarchical_categories_checkbox($categories, $level = 0, $aVal = [])
    {
        $ret = '';
        foreach ($categories as $category) {
            $isChecked = in_array($category->ID, $aVal) ? 'checked' : '';
            $ret .= '<label>' . $category->hierarchy_nbsp . '<input type="checkbox" name="categories[]" value="' . $category->ID . '" ' . $isChecked . ' />' . $category->post_title . '</label>';
        }
        if ($ret) {
            $ret .= '<br>';
        }
        echo wp_kses_post($ret);
    }

    public function shortcode_list_handler(): string
    {
        $bUpdated = false;
        self::$update_message = ['class' => '', 'txt' => ''];
        $table = '';

        // Handle link update
        if (!empty($_POST['action']) && $_POST['action'] === 'update_link' && !empty($_POST['link_id'])) {
            $aParams = [
                'long_url' => filter_var(wp_unslash($_POST['long_url'] ?? ''), FILTER_VALIDATE_URL),
                'idm' => self::$rights['idm'],
                'link_id' => sanitize_text_field(wp_unslash($_POST['link_id'] ?? '')),
                'domain_id' => sanitize_text_field(wp_unslash($_POST['domain_id'] ?? '')),
                'uri' => self::$rights['allow_uri'] ? sanitize_text_field(wp_unslash($_POST['uri'] ?? '')) : '',
                'valid_until' => sanitize_text_field(wp_unslash($_POST['valid_until'] ?? '')),
                'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', wp_unslash($_POST['categories'])) : []
            ];

            // Call the function to update the link
            $shorten_result = ShortURL::shorten($aParams);
            $bUpdated = true;

            if ($shorten_result['error']) {
                self::$update_message['error'] = true;
                self::$update_message['class'] = 'notice-error';
                self::$update_message['txt'] = $shorten_result['message'];
            } else {
                self::$update_message['error'] = false;
                self::$update_message['class'] = 'notice-success';
                self::$update_message['txt'] = __('Link updated', 'rrze-shorturl');
            }
        }

        $categories = self::get_categories_hierarchically();

        // Sort links based on the GET parameters for sorting
        $orderby = !empty($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'ID';
        $order = !empty($_GET['order']) && in_array(wp_unslash($_GET['order']), ['ASC', 'DESC']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'ASC';

        $args = [
            'post_type' => 'shorturl_link',
            'posts_per_page' => -1,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => [
                [
                    'key' => 'idm',
                    'value' => self::$rights['idm'],
                    'compare' => '='
                ]
            ],
        ];

        // Handle category filtering
        $filter_category = !empty($_GET['filter_category']) ? (int) sanitize_text_field(wp_unslash($_GET['filter_category'])) : 0;

        if ($filter_category > 0) {
            $args['meta_query'][] = [
                'key' => 'category_id',
                'value' => $filter_category,
                'compare' => '='
            ];
            $args['meta_key'] = ['category_id'];
        }

        // Fetch the links with all necessary meta fields
        $links_query = new \WP_Query($args);
        $results = $links_query->posts;

        // Generate category filter dropdown
        $category_filter_dropdown = '<select name="filter_category">';
        $category_filter_dropdown .= '<option value="0">' . esc_html__('All Categories', 'rrze-shorturl') . '</option>';
        foreach ($categories as $category) {
            $category_filter_dropdown .= '<option value="' . esc_attr($category->ID) . '"' . ($filter_category == $category->ID ? ' selected' : '') . '>' . esc_html($category->hierarchy_nbsp . $category->post_title) . '</option>';
        }
        $category_filter_dropdown .= '</select>';

        // Generate filter button
        $filter_button = '<button type="submit">' . esc_html__('Filter', 'rrze-shorturl') . '</button>';

        // Generate checkbox for own links
        // $checkbox = '<input type="checkbox" name="own_links" value="1" ' . checked(1, $own_links, false) . '>' . esc_html__('My links only', 'rrze-shorturl');

        // Generate form for category filtering
        $category_filter_form = '<form method="get">';
        $category_filter_form .= $category_filter_dropdown;
        $category_filter_form .= '&nbsp;' . $filter_button;
        // $category_filter_form .= '&nbsp;' . $checkbox;
        $category_filter_form .= '</form>';

        // Display success notice
        if ($bUpdated && !self::$update_message['error']){
            $table .= '<div class="notice ' . self::$update_message['class'] . ' is-dismissible"><p>' . self::$update_message['txt'] . '</p></div>';
        }
        
        $table .= $category_filter_form;
        $table .= '<table class="shorturl-wp-list-table widefat striped">';
        $table .= '<thead><tr>';
        $table .= '<th scope="col"><a href="?orderby=long_url&order=' . ($orderby === 'long_url' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . esc_html__('Long URL', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col"><a href="?orderby=short_url&order=' . ($orderby === 'short_url' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . esc_html__('Short URL', 'rrze-shorturl') . '</a></th>';
        // $table .= '<th scope="col">' . esc_html__('URI', 'rrze-shorturl') . '</th>';
        $table .= '<th scope="col"><a href="?orderby=valid_until&order=' . ($orderby === 'valid_until' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . esc_html__('Valid until', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col">' . esc_html__('Categories', 'rrze-shorturl') . '</th>';
        $table .= '<th scope="col">' . esc_html__('Actions', 'rrze-shorturl') . '</th>';
        $table .= '</tr></thead><tbody>';

        if (empty($results)) {
            $table .= '<tr><td colspan="6">' . esc_html__('No links stored yet', 'rrze-shorturl') . '</td></tr>';
        } else {
            foreach ($results as $link) {
                $link_id = $link->ID;

                $long_url = get_the_title($link_id);
                $shorturl_generated = get_post_meta($link_id, 'shorturl_generated', true);
                $shorturl_custom = get_post_meta($link_id, 'shorturl_custom', true);
                // $uri = get_post_meta($link_id, 'uri', true);
                $valid_until = get_post_meta($link_id, 'valid_until', true);
                $category_ids = get_post_meta($link_id, 'category_id');

                $category_names_str = '';
                if (!empty($category_ids)) {
                    $category_names = [];
                    foreach ($category_ids as $category_id) {
                        $category_post = get_post((int) $category_id);
                        if ($category_post) {
                            $category_names[] = $category_post->post_title;
                        }
                    }
                    $category_names_str = implode(', ', $category_names);
                }

                // Output table row
                $table .= '<tr>';
                $table .= '<td class="column-long-url"><a href="' . esc_url($long_url) . '">' . esc_html($long_url) . '</a></td>';
                $table .= '<td><a href="' . esc_url($shorturl_generated) . '+">' . esc_html($shorturl_generated) . '</a>'. (!empty($shorturl_custom) ? '<br><a href="' . esc_url($shorturl_custom) . '+">' . esc_html($shorturl_custom) . '</a>':'') .'</td>';
                // $table .= '<td>' . esc_html($uri) . '</td>';
                $table .= '<td>' . (!empty($valid_until) ? esc_html($valid_until) : esc_html__('indefinite', 'rrze-shorturl')) . '</td>';
                $table .= '<td>' . esc_html($category_names_str) . '</td>';
                // 2.1.24 : Admins now edit links in backend
                // $table .= '<td>' . (self::$rights['idm'] == get_post_meta($link_id, 'idm', true) || is_user_logged_in() ? '<a href="#" class="edit-link" data-link-id="' . esc_attr($link_id) . '">' . esc_html__('Edit', 'rrze-shorturl') . '</a> | <a href="#" data-link-id="' . esc_attr($link_id) . '" class="delete-link">' . esc_html__('Delete', 'rrze-shorturl') . '</a>' : '') . '</td>';
                $table .= '<td><a href="#" class="edit-link" data-link-id="' . esc_attr($link_id) . '">' . esc_html__('Edit', 'rrze-shorturl') . '</a> | <a href="#" data-link-id="' . esc_attr($link_id) . '" class="delete-link">' . esc_html__('Delete', 'rrze-shorturl') . '</a></td>';
                $table .= '</tr>';
            }
        }

        $table .= '</tbody></table>';

        if ((!$bUpdated && !empty($results)) || self::$update_message['error']) {
            $table .= $this->display_edit_link_form();
        }

        return $table;
    }

    private function display_edit_link_form()
    {
        $link_id = (!empty($_GET['link_id']) ? (int) $_GET['link_id'] : (!empty($_POST['link_id']) ? (int) $_POST['link_id'] : 0));

        if ($link_id <= 0) {
            return '';
        } else {
            // Load the link data from the database
            $link_data = $this->get_link_data_by_id($link_id);

            if (empty($link_data)) {
                return '';
            } else {
                // Check if user is allowed to edit
                if (self::$rights['idm'] == $link_data['idm'] || is_user_logged_in()) {
                    $aCategories = !empty($link_data['category_ids']) ? explode(',', $link_data['category_ids']) : [];

                    // Prepare parameters for the form
                    $aParams = [
                        'uri' => !empty($link_data['uri']) ? esc_attr($link_data['uri']) : '',
                        'valid_until' => esc_attr($link_data['valid_until']),
                        'utm_source' => !empty($link_data['utm_source']) ? esc_attr($link_data['utm_source']) : '',
                        'utm_medium' => !empty($link_data['utm_medium']) ? esc_attr($link_data['utm_medium']) : '',
                        'utm_campaign' => !empty($link_data['utm_campaign']) ? esc_attr($link_data['utm_campaign']) : '',
                        'utm_term' => !empty($link_data['utm_term']) ? esc_attr($link_data['utm_term']) : '',
                        'utm_content' => !empty($link_data['utm_content']) ? esc_attr($link_data['utm_content']) : '',
                        'categories' => $aCategories
                    ];

                    // Start output buffering
                    ob_start();
                    ?>

                    <div class="rrze-shorturl">
                        <?php

                    echo '<form id="edit-link-form" method="post">';

                        

                        // Generate update message if available
                        // if (!empty(self::$update_message['txt'])) {
                            echo '<div class="notice ' . self::$update_message['class'] . ' is-dismissible"><p>' . self::$update_message['txt'] . '</p></div>';
                        // }
                        ?>

                            <div class="postbox">  
                                <h2 class="handle"><?php echo esc_html__('Edit Link', 'rrze-shorturl'); ?></h2>
                                    <?php echo esc_html($link_data['long_url']); ?><br><br>
                                    <input type="hidden" name="long_url" value="<?php echo esc_html($link_data['long_url']); ?>">
                                    <input type="hidden" name="action" value="update_link">
                                    <input type="hidden" name="link_id" value="<?php echo esc_attr($link_id); ?>">
                                    <input type="hidden" name="domain_id" value="<?php echo esc_attr($link_data['domain_id']); ?>">
                                    <input type="text" name="shorturl_generated" value="<?php echo esc_attr($link_data['shorturl_generated']); ?>">
                                    <input type="text" name="shorturl_custom" value="<?php echo esc_attr($link_data['shorturl_custom']); ?>">
                                <?php
                                // Display URI field if allowed
                                if (self::$rights['allow_uri']) {
                                    echo self::display_shorturl_uri($aParams['uri']);
                                } else {
                                    echo '<input type="hidden" name="uri" value="' . esc_attr($aParams['uri']) . '">';
                                }

                                // Display validity field
                                echo self::display_shorturl_validity($aParams['valid_until']);

                                // Display UTM fields if allowed
                                if (self::$rights['allow_utm']) {
                                    echo self::display_shorturl_utm($aParams);
                                }

                                // Display categories
                                echo '<h6 class="handle">' . esc_html__('Categories', 'rrze-shorturl') . '</h6>';
                                echo self::display_shorturl_category($aParams['categories']);
                                ?>

                                <button type="submit" class="btn-update-link"><?php echo esc_html__('Update Link', 'rrze-shorturl'); ?></button>
                            </div>
                        </form>
                        <?php
                        return ob_get_clean();
                } else {
                    return '';
                }
            }
        }
    }

    public static function update_category_label()
    {
        // Verify nonce
        if (empty($_POST['_ajax_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])), 'update_category_label_nonce')) {
            wp_send_json_error(__('Nonce verification failed.', 'rrze-shorturl'));
        }

        // Get category ID and updated label from AJAX request
        $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $updated_label = isset($_POST['updated_label']) ? sanitize_text_field(wp_unslash($_POST['updated_label'])) : '';

        // Check if the category exists and is valid
        if ($category_id > 0 && !empty($updated_label)) {
            // Use wp_update_post to update the category label (post_title)
            $post_data = [
                'ID' => $category_id,
                'post_title' => $updated_label
            ];
            $result = wp_update_post($post_data);

            // Check if the update was successful
            if (!is_wp_error($result)) {
                wp_send_json_success(__('Category updated', 'rrze-shorturl'));
            } else {
                wp_send_json_error(__('Error: Could not update category', 'rrze-shorturl'));
            }
        } else {
            wp_send_json_error(__('Invalid category ID or label', 'rrze-shorturl'));
        }

        // Don't forget to exit
        wp_die();
    }



    public function add_shorturl_category_callback()
    {
        // Check nonce for security
        check_ajax_referer('add_shorturl_category_nonce', '_ajax_nonce');

        // Sanitize the input data
        $idm = self::$rights['idm'];
        $category_name = !empty($_POST['categoryName']) ? sanitize_text_field(wp_unslash($_POST['categoryName'])) : '';
        $parent_category = !empty($_POST['parentCategory']) ? (int) $_POST['parentCategory'] : 0;
        $aCategory = !empty($_POST['category_ids']) ? explode(',', sanitize_text_field(wp_unslash($_POST['category_ids']))) : [];

        if (empty($category_name)) {
            wp_send_json_error(__('Category name is required.', 'rrze-shorturl'));
        }

        // Check if the category already exists (using post title)
        $args = [
            'post_type' => 'shorturl_category',
            'title' => $category_name,
            'post_status' => 'publish',
            'posts_per_page' => 1,
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            $existing_category = $query->posts[0];
        } else {
            $existing_category = null;
        }

        wp_reset_postdata();

        if ($existing_category) {
            // Category already exists, return its ID
            wp_send_json_success([
                'category_id' => $existing_category->ID,
                'category_list_html' => self::generate_category_list_html($aCategory)
            ]);
        } else {
            // Insert new category using wp_insert_post
            $new_category_id = wp_insert_post(array(
                'post_title' => $category_name,
                'post_name' => sanitize_title($category_name), // Optional slug
                'post_type' => 'shorturl_category', // Custom post type name
                'post_status' => 'publish',
                'post_parent' => $parent_category, // Set parent category if applicable
            ));
            $aCategory[] = $new_category_id;

            // If the category was successfully inserted
            if (!is_wp_error($new_category_id)) {
                // Store idm as post meta for the new category
                update_post_meta($new_category_id, 'idm', $idm);

                // Return the new category ID and updated category list HTML
                wp_send_json_success([
                    'category_id' => $new_category_id,
                    'category_list_html' => self::generate_category_list_html($aCategory)
                ]);
            } else {
                error_log('Failed to insert new category: ' . $new_category_id->get_error_message());

                // If the insertion failed, return an error message
                wp_send_json_error(__('Failed to add category. Please try again.', 'rrze-shorturl'));
            }
        }

        // Always terminate the script after handling the request
        wp_die();
    }


    private static function generate_category_list_html($aCategory = [])
    {
        // Generate HTML for the updated category list
        ob_start();
        echo wp_kses_post(self::display_shorturl_category($aCategory));
        return ob_get_clean();
    }


    public function store_shorturl_link_category_callback()
    {
        // Verify nonce for security
        check_ajax_referer('store-link-category', '_ajax_nonce');

        // Sanitize and retrieve the link ID and category ID
        $link_id = !empty($_POST['linkId']) ? (int) $_POST['linkId'] : 0;
        $category_id = !empty($_POST['categoryId']) ? (int) $_POST['categoryId'] : 0;

        // Check if the provided link ID and category ID are valid
        if ($link_id <= 0 || $category_id <= 0) {
            wp_send_json_error(__('Invalid link ID or category ID.', 'rrze-shorturl'));
        }

        // Check if the link exists
        $link = get_post($link_id);
        if (!$link || $link->post_type !== 'shorturl_link') {
            wp_send_json_error(__('Invalid link.', 'rrze-shorturl'));
        }

        // Check if the category exists
        $category = get_post($category_id);
        if (!$category || $category->post_type !== 'shorturl_category') {
            wp_send_json_error(__('Invalid category.', 'rrze-shorturl'));
        }

        // Get current categories assigned to the link from post meta
        $current_categories = get_post_meta($link_id, 'category_id', true);
        if (!$current_categories) {
            $current_categories = [];
        } else {
            $current_categories = (array) $current_categories;
        }

        // Check if the category is already assigned to the link
        if (in_array($category_id, $current_categories)) {
            wp_send_json_success(__('Category is already linked to the link.', 'rrze-shorturl'));
        }

        // Add the new category ID to the current list
        $current_categories[] = $category_id;

        // Update the post meta with the new list of categories
        $result = update_post_meta($link_id, 'category_id', $current_categories);

        // Check if the category was successfully added
        if ($result !== false) {
            wp_send_json_success(__('Category linked to the link successfully.', 'rrze-shorturl'));
        } else {
            wp_send_json_error(__('Failed to link category to the link. Please try again.', 'rrze-shorturl'));
        }

        // Don't forget to terminate the script
        wp_die();
    }

    public static function display_shorturl_uri($val)
    {
        ob_start();
        ?>
            <div>
                <label for="self_explanatory_uri">
                    <?php echo esc_html__('Self-Explanatory URI', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="uri" name="uri" value="<?php echo esc_html($val); ?>">
            </div>
            <?php
            return ob_get_clean();
    }


    public static function display_shorturl_utm($aUTM)
    {
        // Define UTM keys
        $utm_source = !empty($aUTM['utm_source']) ? $aUTM['utm_source'] : '';
        $utm_medium = !empty($aUTM['utm_medium']) ? $aUTM['utm_medium'] : '';
        $utm_campaign = !empty($aUTM['utm_campaign']) ? $aUTM['utm_campaign'] : '';
        $utm_term = !empty($aUTM['utm_term']) ? $aUTM['utm_term'] : '';
        $utm_content = !empty($aUTM['utm_content']) ? $aUTM['utm_content'] : '';

        ob_start();
        ?>
            <div>
                <label for="utm_source">
                    <?php echo esc_html__('UTM Source', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="utm_source" name="utm_source" value="<?php echo esc_html($utm_source); ?>">

                <label for="utm_medium">
                    <?php echo esc_html__('UTM Medium', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="utm_medium" name="utm_medium" value="<?php echo esc_html($utm_medium); ?>">

                <label for="utm_campaign">
                    <?php echo esc_html__('UTM Campaign', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="utm_campaign" name="utm_campaign" value="<?php echo esc_html($utm_campaign); ?>">

                <label for="utm_term">
                    <?php echo esc_html__('UTM Term', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="utm_term" name="utm_term" value="<?php echo esc_html($utm_term); ?>">

                <label for="utm_content">
                    <?php echo esc_html__('UTM Content', 'rrze-shorturl'); ?>:
                </label>
                <input type="text" id="utm_content" name="utm_content" value="<?php echo esc_html($utm_content); ?>">
            </div>
            <?php
            return ob_get_clean();
    }

}


