<?php

namespace RRZE\ShortURL;


class Shortcode
{
    protected static $rights;

    public function __construct()
    {
        $rightsObj = new Rights();
        self::$rights = $rightsObj->getRights();

        add_shortcode('shorturl', [$this, 'shorturl_handler']);
        add_shortcode('shorturl-list', [$this, 'shortcode_list_handler']);
        add_shortcode('shorturl-categories', [$this, 'shortcode_categories_handler']);
        add_shortcode('shorturl-services', [$this, 'shortcode_services_handler']);
        add_shortcode('shorturl-customer-domains', [$this, 'shortcode_customer_domains_handler']);

        add_action('wp_ajax_nopriv_store_link_category', [$this, 'store_link_category_callback']);
        add_action('wp_ajax_store_link_category', [$this, 'store_link_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_category', [$this, 'add_shorturl_category_callback']);
        add_action('wp_ajax_add_shorturl_category', [$this, 'add_shorturl_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_tag', [$this, 'add_shorturl_tag_callback']);
        add_action('wp_ajax_add_shorturl_tag', [$this, 'add_shorturl_tag_callback']);

        add_action('wp_ajax_nopriv_update_category_label_action', [$this, 'update_category_label']);
        add_action('wp_ajax_update_category_label_action', [$this, 'update_category_label']);

        add_action('wp_ajax_nopriv_update_shorturl_category_label', [$this, 'update_category_label']);
        add_action('wp_ajax_update_shorturl_category_label', [$this, 'update_category_label']);

        add_action('wp_ajax_nopriv_delete_link', [$this, 'delete_link_callback']);
        add_action('wp_ajax_delete_link', [$this, 'delete_link_callback']);

        add_action('wp_ajax_nopriv_delete_category', [$this, 'delete_category_callback']);
        add_action('wp_ajax_delete_category', [$this, 'delete_category_callback']);

        add_action('wp_ajax_nopriv_delete_tag', [$this, 'delete_tag_callback']);
        add_action('wp_ajax_delete_tag', [$this, 'delete_tag_callback']);
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

    public function makeCategoryDropdown($category_id = 0, $parent_id = 0)
    {
        if (!self::$rights['id']) {
            return '';
        }

        // Prepare arguments to fetch categories for the current IdM
        $args = [
            'post_type' => 'shorturl_category',
            'posts_per_page' => -1,         // Fetch all categories
            'meta_query' => [
                [
                    'key' => 'idm_id',
                    'value' => self::$rights['id'],
                    'compare' => '='
                ]
            ]
        ];

        // Fetch categories using WP_Query
        $categories_query = new WP_Query($args);
        $categories = [];

        // Build the hierarchical structure for each category
        if ($categories_query->have_posts()) {
            while ($categories_query->have_posts()) {
                $categories_query->the_post();
                $category = new stdClass();
                $category->id = get_the_ID();
                $category->label = get_the_title();
                $category->parent_id = get_post_meta($category->id, 'parent_id', true);
                if ($category->id != $category_id) {
                    $category->hierarchy = $this->build_category_hierarchy_table($category, $categories_query->posts);
                    $categories[] = $category;
                }
            }
        }

        // Sort categories by hierarchy
        usort($categories, function ($a, $b) {
            return strcmp($a->hierarchy, $b->hierarchy);
        });

        // Start building the dropdown HTML output
        $output = '<label for="parent_category">' . __('Parent Category', 'rrze-shorturl') . ':</label><br>';
        $output .= '<select id="parent_category" name="parent_category">';
        $output .= '<option value="0">' . __('None', 'rrze-shorturl') . '</option>';

        foreach ($categories as $category) {
            $selected = (!empty($parent_id) && ($category->id == $parent_id)) ? 'SELECTED' : '';
            $output .= '<option value="' . $category->id . '" ' . $selected . '>' . esc_html($category->hierarchy . $category->label) . '</option>';
        }

        $output .= '</select>';

        // Reset post data
        wp_reset_postdata();

        return $output;
    }

    public function shortcode_categories_handler(): string
    {
        // Edit Category
        if (!empty($_POST['edit_category'])) {
            $category_id = (int) $_POST['category_id'];
            $category_label = sanitize_text_field($_POST['category_label']);
            $parent_category = !empty($_POST['parent_category']) ? (int) $_POST['parent_category'] : 0;

            // Update the category using wp_update_post and update_post_meta
            $post_data = [
                'ID' => $category_id,
                'post_title' => $category_label,
            ];
            wp_update_post($post_data);

            // Update the parent category as meta data
            update_post_meta($category_id, 'parent_id', $parent_category);
            update_post_meta($category_id, 'idm_id', self::$rights['id']);

            // Return to the table after editing
            return $this->display_categories_table();
        } elseif (!empty($_POST['add_category'])) {
            // Add Category
            $category_label = sanitize_text_field($_POST['category_label']);
            $parent_category = !empty($_POST['parent_category']) ? (int) $_POST['parent_category'] : 0;

            if (!empty($category_label)) {
                // Insert Category as a new post in the 'category' CPT
                $post_data = [
                    'post_title' => $category_label,
                    'post_type' => 'shorturl_category',
                    'post_status' => 'publish',
                ];

                $category_id = wp_insert_post($post_data);

                if (!is_wp_error($category_id)) {
                    // Add meta data for parent and IdM
                    update_post_meta($category_id, 'parent_id', $parent_category);
                    update_post_meta($category_id, 'idm_id', self::$rights['id']);
                }
            }

            return $this->display_categories_table();
        }

        // Check if an edit form should be displayed
        if (!empty($_GET['action']) && $_GET['action'] === 'edit_category' && !empty($_GET['category_id'])) {
            // Retrieve category details based on category ID using WP_Query
            $category_id = (int) $_GET['category_id'];
            $category = get_post($category_id);

            // If category is found, display edit form
            if ($category && $category->post_type === 'shorturl_category') {
                $category_label = esc_attr($category->post_title);
                $parent_id = get_post_meta($category_id, 'parent_id', true) ?: 0;

                // Start building the form
                $output = '<form method="post">';
                $output .= '<label for="category_label">' . __('Category Label', 'rrze-shorturl') . ':</label><br>';
                $output .= '<input type="text" id="category_label" name="category_label" value="' . esc_attr($category_label) . '"><br>';

                // Display parent category dropdown
                $output .= $this->makeCategoryDropdown($category_id, $parent_id);

                $output .= '<input type="hidden" name="category_id" value="' . esc_attr($category_id) . '">';

                $output .= '<br><input type="submit" name="edit_category" value="' . __('Save Changes', 'rrze-shorturl') . '">';
                $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';

                $output .= '</form>';

                return $output;
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'add_new_category') {
            // Display add category form
            return $this->add_category_form();
        }

        // If no editing is happening, display the categories table
        return $this->display_categories_table();
    }


    // Helper function to display the categories table
    private function display_categories_table()
    {
        // Fetch all categories for the current IdM using WP_Query
        $args = [
            'post_type' => 'shorturl_category', 
            'posts_per_page' => -1,         // Fetch all categories
            'meta_query' => [
                [
                    'key' => 'idm_id',
                    'value' => self::$rights['id'],
                    'compare' => '='
                ]
            ]
        ];

        $categories_query = new \WP_Query($args);
        $categories = $categories_query->posts;

        // Build the hierarchical structure for each category
        $categories_with_hierarchy = [];
        foreach ($categories as $category) {
            $category->hierarchy = $this->build_category_hierarchy_table($category, $categories);
            $categories_with_hierarchy[] = $category;
        }

        // Sort categories by hierarchy
        usort($categories_with_hierarchy, function ($a, $b) {
            return strcmp($a->hierarchy, $b->hierarchy);
        });

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
            $category_label = get_the_title($category_id);

            // Build the table row
            $output .= '<tr>';
            $output .= '<td class="column-label">' . esc_html($category->hierarchy . $category_label) . '</td>';
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

        // Reset post data after query
        wp_reset_postdata();

        return $output;
    }





    private function build_category_hierarchy_table($category, $categories)
    {
        $hierarchy = '';
        $parent_id = $category->parent_id;
        while ($parent_id != 0) {
            foreach ($categories as $cat) {
                if ($cat->id == $parent_id) {
                    $hierarchy .= ' - ';
                    $parent_id = $cat->parent_id;
                    break;
                }
            }
        }
        return $hierarchy;
    }


    private function add_category_form()
    {
        $output = '<h2>' . __('Add New Category', 'rrze-shorturl') . '</h2>';
        $output .= '<form method="post">';
        $output .= '<label for="category_label">' . __('Category Label', 'rrze-shorturl') . ':</label><br>';
        $output .= '<input type="text" id="category_label" name="category_label" value=""><br>';
        $output .= $this->makeCategoryDropdown();
        $output .= '<br><input type="submit" name="add_category" value="' . __('Add Category', 'rrze-shorturl') . '">';
        $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';
        $output .= '</form>';

        return $output;
    }

    private function get_link_data_by_id($link_id)
    {
        // Fetch the link post using WP_Query
        $link_query = new WP_Query([
            'post_type' => 'shorturl_link', 
            'posts_per_page' => 1,
            'p' => $link_id // Fetch by specific post ID
        ]);

        // Initialize result array
        $result = [];

        if ($link_query->have_posts()) {
            $link_query->the_post();

            // Get post meta data (assuming these are stored as meta fields)
            $result = [
                'id' => get_the_ID(),
                'long_url' => get_post_meta(get_the_ID(), 'long_url', true),
                'short_url' => get_post_meta(get_the_ID(), 'short_url', true),
                'uri' => get_post_meta(get_the_ID(), 'uri', true),
                'idm_id' => get_post_meta(get_the_ID(), 'idm_id', true),
                'created_at' => get_post_meta(get_the_ID(), 'created_at', true),
                'updated_at' => get_post_meta(get_the_ID(), 'updated_at', true),
                'deleted_at' => get_post_meta(get_the_ID(), 'deleted_at', true),
                'valid_until' => get_post_meta(get_the_ID(), 'valid_until', true),
                'active' => get_post_meta(get_the_ID(), 'active', true)
            ];

            // Fetch associated categories (assuming categories are stored in a taxonomy)
            $category_ids = wp_get_post_terms(get_the_ID(), 'link_category', ['fields' => 'ids']);
            $result['category_ids'] = implode(',', $category_ids);
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
            // Get the parent_id of the category being deleted
            $parent_id = get_post_meta($category_id, 'parent_id', true);

            // Fetch child categories that have the category being deleted as a parent
            $args = [
                'post_type' => 'shorturl_category',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'parent_id',
                        'value' => $category_id,
                        'compare' => '='
                    ]
                ]
            ];

            $child_categories = new WP_Query($args);

            // Update child categories to inherit the parent_id of the category being deleted
            if ($child_categories->have_posts()) {
                while ($child_categories->have_posts()) {
                    $child_categories->the_post();
                    $child_category_id = get_the_ID();

                    // If parent_id exists, assign it to child categories
                    update_post_meta($child_category_id, 'parent_id', $parent_id ? $parent_id : 0);
                }
            }

            // Delete the category using wp_delete_post
            $result = wp_delete_post($category_id, true); // 'true' forces permanent deletion

            if ($result !== false) {
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
            'url' => (!empty($_POST['url']) ? sanitize_text_field($_POST['url']) : (!empty($_GET['url']) ? sanitize_text_field($_GET['url']) : '')),
            'uri' => self::$rights['allow_uri'] ? sanitize_text_field($_POST['uri'] ?? '') : '',
            'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
            'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [],
            // 'tags' => !empty ($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : [],
            'utm_source' => (!empty($_POST['utm_source']) ? sanitize_text_field($_POST['utm_source']) : ''),
            'utm_medium' => (!empty($_POST['utm_medium']) ? sanitize_text_field($_POST['utm_medium']) : ''),
            'utm_campaign' => (!empty($_POST['utm_campaign']) ? sanitize_text_field($_POST['utm_campaign']) : ''),
            'utm_term' => (!empty($_POST['utm_term']) ? sanitize_text_field($_POST['utm_term']) : ''),
            'utm_content' => (!empty($_POST['utm_content']) ? sanitize_text_field($_POST['utm_content']) : ''),
        ];

        $result_message = ''; // Initialize result message
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST" || !empty($_GET['url'])) {
            // Check if URL is provided
            if (!empty($aParams['url'])) {
                $result = ShortURL::shorten($aParams);

                if ($result['error']) {
                    $result_message = $result['txt'];
                } else {
                    $result_message = '<span class="shorturl-shortened-msg"><span class="label">' . __('Short URL', 'rrze-shorturl') . ':</span> <code>' . $result['txt'] . '</code></span>';
                    $result_message .= '<button type="button" class="btn" id="copyButton" name="copyButton" data-shortened-url="' . $result['txt'] . '"><img class="shorturl-copy-img" src="data:image/svg+xml,%3Csvg height=\'1024\' width=\'896\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z\' fill=\'%23000000\' /%3E%3C/svg%3E" alt="' . __('Copy to clipboard', 'rrze-shorturl') . '"><span class="screen-reader-text">' . __('Copy to clipboard', 'rrze-shorturl') . '</span></button><span id="shorturl-tooltip" class="shorturl-tooltip">' . __('Copied to clipboard', 'rrze-shorturl') . '</span>';
                    $result_message .= '<br><span class="shorturl-validuntil"><span class="label">' . __('Valid until', 'rrze-shorturl') . ':</span> ' . $result['valid_until_formatted'] . '</span>';
                }

                $aParams['url'] = $result['long_url']; // we might have added the scheme
            }
        }

        // Generate form
        $form = '<div class="rrze-shorturl"><form id="shorturl-form" method="post">';
        $form .= '<div class="postbox">';
        $form .= '<h2 class="handle">' . __('Create Short URL', 'rrze-shorturl') . '</h2>';
        $form .= '<div class="inside">';
        $form .= '<label for="url">' . __('Your link', 'rrze-shorturl') . ':</label>';
        $form .= '<input type="text" name="url" id="url" value="' . esc_attr($aParams['url']) . '" placeholder="https://" ' . (!empty($result['error']) ? ' aria-invalid="true" aria-errormessage="shorturl-err" ' : '') . '>';
        $form .= '<input type="submit" id="generate" name="generate" value="' . __('Shorten', 'rrze-shorturl') . '">';
        $form .= '<input type="hidden" name="link_id" value="' . (!empty($result['link_id']) ? $result['link_id'] : '') . '">';
        $form .= '</div>';
        $form .= '</div>';
        $form .= '<button id="btn-show-advanced-settings" type="button" aria-haspopup="true" aria-controls="shorturl-advanced-settings" aria-expanded="false">' . __('Advanced Settings', 'rrze-shorturl') . '<span class="arrow-down"></span></button>';
        $form .= '<div id="shorturl-advanced-settings" class="shorturl-advanced-settings">';
        if (self::$rights['allow_uri']) {
            $form .= self::display_shorturl_uri($aParams['uri']);
        }
        $form .= self::display_shorturl_validity($aParams['valid_until']);
        if (self::$rights['allow_utm']) {
            $form .= self::display_shorturl_utm($aParams['utm_source'], $aParams['utm_medium'], $aParams['utm_campaign'], $aParams['utm_term'], $aParams['utm_content']);
        }
        $form .= '<h6 class="handle">' . __('Categories', 'rrze-shorturl') . '</h6>';
        $form .= self::display_shorturl_category($aParams['categories']);
        $form .= '</div>';

        // Display result message
        // notice or error msg
        $form .= '<div class="rrze-shorturl-result"><p' . (!empty($result['error']) ? ' id="shorturl-err" class="shorturl-msg-' . $result['message_type'] . '"' : '') . '>' . $result_message . '</p>';

        if (!empty($result) && !$result['error']) {
            $form .= '<input id="shortened_url" name="shortened_url" type="hidden" value="' . $result['txt'] . '">';
            $form .= '<div id="qr-container"><canvas id="qr"></canvas><button type="button" class="btn" id="downloadButton" name="downloadButton"><img class="shorturl-download-img" src="data:image/svg+xml,%3Csvg width=\'512\' height=\'512\' viewBox=\'0 0 512 512\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'%23000000\'%3E%3Cpath d=\'M376.3 304.3l-71.4 71.4V48c0-8.8-7.2-16-16-16h-48c-8.8 0-16 7.2-16 16v327.6l-71.4-71.4c-6.2-6.2-16.4-6.2-22.6 0l-22.6 22.6c-6.2 6.2-6.2 16.4 0 22.6l128 128c6.2 6.2 16.4 6.2 22.6 0l128-128c6.2-6.2 6.2-16.4 0-22.6l-22.6-22.6c-6.2-6.2-16.4-6.2-22.6 0zM464 448H48c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16z\'/%3E%3C/svg%3E" title="' . __('Download QR', 'rrze-shorturl') . '"><span class="screen-reader-text">' . __('Download QR', 'rrze-shorturl') . '</span></button></div>';
        }
        $form .= '</form></div>';

        return $form;
    }

    public static function display_shorturl_validity($val)
    {
        ob_start();

        ?>
        <label for="valid_until">
            <?php echo __('Valid until', 'rrze-shorturl'); ?>:
        </label>
        <input type="date" id="valid_until" name="valid_until" value="<?php echo $val; ?>">
        <?php
        return ob_get_clean();

    }

    public static function display_shorturl_category($aVal = [])
    {
        if (!self::$rights['id']) {
            return;
        }

        // Fetch categories for the current IdM using WP_Query
        $args = [
            'post_type' => 'shorturl_category', // Assuming 'category' is the Custom Post Type
            'posts_per_page' => -1,         // Fetch all categories
            'meta_query' => [
                [
                    'key' => 'idm_id',
                    'value' => self::$rights['id'],
                    'compare' => '='
                ]
            ]
        ];

        $categories_query = new WP_Query($args);
        $categories = $categories_query->posts;

        // Build hierarchical category structure
        $hierarchicalCategories = self::build_category_hierarchy($categories);

        // Output HTML
        ob_start();
        ?>
        <div id="shorturl-category-metabox">
            <?php self::display_hierarchical_categories($hierarchicalCategories, 0, $aVal); ?>
            <p><a href="#" id="add-new-shorturl-category">
                    <?php echo __('Add New Category', 'rrze-shorturl'); ?>
                </a></p>
            <div id="new-shorturl-category">
                <label for="new_shorturl_category"><?php echo __('New Category Name', 'rrze-shorturl'); ?>:</label>
                <input type="text" id="new_shorturl_category" name="new_shorturl_category"
                    placeholder="<?php echo __('New Category Name', 'rrze-shorturl'); ?>">
                <br>
                <label for="parent_category"><?php echo __('Parent Category', 'rrze-shorturl'); ?>:</label>
                <select id="parent_category" name="parent_category">
                    <option value="0"><?php echo __('None', 'rrze-shorturl'); ?></option>
                    <?php self::display_parent_categories_dropdown($hierarchicalCategories); ?>
                </select>

                <br><input type="button" value="<?php echo __('Add new category', 'rrze-shorturl'); ?>"
                    id="add-shorturl-category-btn">
            </div>
        </div>
        <?php
        // Reset post data after query
        wp_reset_postdata();

        return ob_get_clean();
    }

    private static function display_parent_categories_dropdown($categories, $level = 0)
    {
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->id) . '">' . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level) . esc_html($category->label) . '</option>';
            if (!empty($category->children)) {
                self::display_parent_categories_dropdown($category->children, $level + 1);
            }
        }
    }

    // Function to build hierarchical category structure
    private static function build_category_hierarchy($categories, $parent_id = 0)
    {
        $hierarchicalCategories = array();

        foreach ($categories as $category) {
            if ($category->parent_id == $parent_id) {
                $children = self::build_category_hierarchy($categories, $category->id);
                if ($children) {
                    $category->children = $children;
                }
                $hierarchicalCategories[] = $category;
            }
        }

        return $hierarchicalCategories;
    }

    // Function to display hierarchical categories
    private static function display_hierarchical_categories($categories, $level = 0, $aVal = [])
    {
        foreach ($categories as $category) {
            $isChecked = in_array($category->id, $aVal) ? 'checked' : '';

            echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level); // Indent based on level
            echo '<input type="checkbox" name="categories[]" value="' . esc_attr($category->id) . '" ' . $isChecked . ' />';
            echo esc_html($category->label) . '<br>';

            if (!empty($category->children)) {
                // Ensure $level is an integer by casting it
                self::display_hierarchical_categories($category->children, (int) $level + 1, $aVal);
            }
        }
    }


    public function shortcode_list_handler(): string
    {
        $bUpdated = false;
        $message = '';

        // Handle link update
        if (!empty($_POST['action']) && $_POST['action'] === 'update_link' && !empty($_POST['link_id'])) {
            $aParams = [
                'idm_id' => self::$rights['id'],
                'link_id' => htmlspecialchars($_POST['link_id'] ?? ''),
                'domain_id' => htmlspecialchars($_POST['domain_id'] ?? ''),
                'shortURL' => filter_var($_POST['shortURL'] ?? '', FILTER_VALIDATE_URL),
                'uri' => self::$rights['allow_uri'] ? sanitize_text_field($_POST['uri'] ?? '') : '',
                'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
                'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : []
            ];

            // Call the function to update the link (you can adapt this based on your update method)
            ShortURL::updateLink($aParams['idm_id'], $aParams['link_id'], $aParams['domain_id'], $aParams['shortURL'], $aParams['uri'], $aParams['valid_until'], $aParams['categories']);

            $bUpdated = true;
            $message = __('Link updated', 'rrze-shorturl');
        }

        // Fetch all categories using WP_Query
        $categories_query = new WP_Query([
            'post_type' => 'shorturl_category',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'idm_id',
                    'value' => self::$rights['id'],
                    'compare' => '='
                ]
            ]
        ]);
        $categories = $categories_query->posts;

        // Sort links based on the GET parameters for sorting
        $orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'ID';
        $order = !empty($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC']) ? sanitize_text_field($_GET['order']) : 'ASC';

        // Check if only own links should be displayed
        $own_links = empty($_GET) ? 1 : (int) !empty($_GET['own_links']);

        // Prepare the arguments for WP_Query to fetch the links
        $args = [
            'post_type' => 'shorturl_link',
            'posts_per_page' => -1,
            'orderby' => $orderby,
            'order' => $order,
            'meta_query' => [
                [
                    'key' => 'idm_id',
                    'value' => self::$rights['id'],
                    'compare' => '='
                ]
            ]
        ];

        if ($own_links == 1) {
            $args['meta_query'][] = [
                'key' => 'idm_id',
                'value' => self::$rights['id'],
                'compare' => '='
            ];
        }

        // Handle category filtering
        $filter_category = !empty($_GET['filter_category']) ? (int) $_GET['filter_category'] : 0;
        if ($filter_category > 0) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'link_category', // Assuming 'link_category' is the taxonomy for categories
                    'field' => 'term_id',
                    'terms' => $filter_category,
                ]
            ];
        }

        // Fetch the links
        $links_query = new WP_Query($args);
        $results = $links_query->posts;

        // Generate update message
        $table = '<div class="updated"><p>' . $message . '</p></div>';

        // Generate category filter dropdown
        $category_filter_dropdown = '<select name="filter_category">';
        $category_filter_dropdown .= '<option value="0">' . __('All Categories', 'rrze-shorturl') . '</option>';
        foreach ($categories as $category) {
            $category_filter_dropdown .= '<option value="' . esc_attr($category->ID) . '"' . ($filter_category == $category->ID ? ' selected' : '') . '>' . esc_html($category->post_title) . '</option>';
        }
        $category_filter_dropdown .= '</select>';

        // Generate filter button
        $filter_button = '<button type="submit">' . __('Filter', 'rrze-shorturl') . '</button>';

        // Generate checkbox for own links
        $checkbox = '<input type="checkbox" name="own_links" value="1" ' . ($own_links == 1 ? 'checked' : '') . '>' . __('My links only', 'rrze-shorturl');

        // Generate form for category filtering
        $category_filter_form = '<form method="get">';
        $category_filter_form .= $category_filter_dropdown;
        $category_filter_form .= '&nbsp;' . $filter_button;
        $category_filter_form .= '&nbsp;' . $checkbox;
        $category_filter_form .= '</form>';

        // Generate table
        $table .= $category_filter_form;
        $table .= '<table class="shorturl-wp-list-table widefat striped">';
        $table .= '<thead><tr>';
        $table .= '<th scope="col"><a href="?orderby=long_url&order=' . ($orderby === 'long_url' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . __('Long URL', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col"><a href="?orderby=short_url&order=' . ($orderby === 'short_url' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . __('Short URL', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col">' . __('URI', 'rrze-shorturl') . '</th>';
        $table .= '<th scope="col"><a href="?orderby=valid_until&order=' . ($orderby === 'valid_until' && $order === 'ASC' ? 'DESC' : 'ASC') . '">' . __('Valid until', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col">' . __('Categories', 'rrze-shorturl') . '</th>';
        $table .= '<th scope="col">' . __('Actions', 'rrze-shorturl') . '</th>';
        $table .= '</tr></thead><tbody>';

        if (empty($results)) {
            $table .= '<tr><td colspan="6">' . __('No links stored yet', 'rrze-shorturl') . '</td></tr>';
        } else {
            foreach ($results as $link) {
                $link_id = $link->ID;
                $long_url = get_post_meta($link_id, 'long_url', true);
                $short_url = get_post_meta($link_id, 'short_url', true);
                $uri = get_post_meta($link_id, 'uri', true);
                $valid_until = get_post_meta($link_id, 'valid_until', true);

                // Get the categories
                $category_names = wp_get_post_terms($link_id, 'link_category', ['fields' => 'names']);
                $category_names_str = implode(', ', $category_names);

                // Output table row
                $table .= '<tr>';
                $table .= '<td><a href="' . esc_url($long_url) . '">' . esc_html($long_url) . '</a></td>';
                $table .= '<td><a href="' . esc_url($short_url) . '+">' . esc_html($short_url) . '</a></td>';
                $table .= '<td>' . esc_html($uri) . '</td>';
                $table .= '<td>' . (!empty($valid_until) ? esc_html($valid_until) : __('indefinite', 'rrze-shorturl')) . '</td>';
                $table .= '<td>' . esc_html($category_names_str) . '</td>';
                $table .= '<td>' . (self::$rights['id'] == get_post_meta($link_id, 'idm_id', true) || is_user_logged_in() ? '<a href="#" class="edit-link" data-link-id="' . $link_id . '">' . __('Edit', 'rrze-shorturl') . '</a> | <a href="#" data-link-id="' . $link_id . '" class="delete-link">' . __('Delete', 'rrze-shorturl') . '</a>' : '') . '</td>';
                $table .= '</tr>';
            }
        }

        $table .= '</tbody></table>';

        if (!$bUpdated && !empty($results)) {
            $table .= $this->display_edit_link_form();
        }

        return $table;
    }


    private function display_edit_link_form()
    {
        $link_id = !empty($_GET['link_id']) ? (int) $_GET['link_id'] : 0;

        if ($link_id <= 0) {
            return '';
        } else {
            // Load the link data from the database
            $link_data = $this->get_link_data_by_id($link_id);
            if (empty($link_data)) {
                return '';
            } else {
                // check if user is allowed to edit
                if (self::$rights['id'] == $link_data['idm_id'] || is_user_logged_in()) {
                    $aCategories = !empty($link_data['category_ids']) ? explode(',', $link_data['category_ids']) : [];
                    $aTags = !empty($link_data['tag_ids']) ? explode(',', $link_data['tag_ids']) : [];

                    // Display the edit form
                    ob_start();
                    ?>
                    <div id="edit-link-form">
                        <h2>
                            <?php echo __('Edit Link', 'rrze-shorturl'); ?>
                        </h2>
                        <form id="edit-link-form" method="post" action="">
                            <input type="hidden" name="action" value="update_link">
                            <input type="hidden" name="link_id" value="<?php echo esc_attr($link_id); ?>">
                            <input type="hidden" name="domain_id" value="<?php echo esc_attr($link_data['domain_id']); ?>">
                            <input type="hidden" name="shortURL" value="<?php echo esc_attr($link_data['short_url']); ?>">
                            <input type="hidden" name="uri"
                                value="<?php echo !empty($link_data['uri']) ? esc_attr($link_data['uri']) : ''; ?>">
                            <?php echo self::display_shorturl_validity($link_data['valid_until']); ?>
                            <h2 class="handle">
                                <?php echo __('Categories', 'rrze-shorturl'); ?>
                            </h2>
                            <?php echo self::display_shorturl_category($aCategories); ?>
                            <button type="submit">
                                <?php echo __('Update Link', 'rrze-shorturl'); ?>
                            </button>
                        </form>
                    </div>
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
        if (empty($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'update_category_label_nonce')) {
            wp_send_json_error(__('Nonce verification failed.', 'rrze-shorturl'));
        }

        // Get category ID and updated label from AJAX request
        $category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $updated_label = isset($_POST['updated_label']) ? sanitize_text_field($_POST['updated_label']) : '';

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
        $category_name = !empty($_POST['categoryName']) ? sanitize_text_field($_POST['categoryName']) : '';
        $parent_category = !empty($_POST['parentCategory']) ? (int) $_POST['parentCategory'] : 0;

        if (empty($category_name)) {
            wp_send_json_error(__('Category name is required.', 'rrze-shorturl'));
        }

        // Check if the category already exists
        $existing_category = get_page_by_title($category_name, OBJECT, 'category');

        if ($existing_category) {
            // Category already exists, return its ID
            wp_send_json_success([
                'category_id' => $existing_category->ID,
                'category_list_html' => self::generate_category_list_html()
            ]);
        } else {
            // Insert new category using wp_insert_post
            $category_data = [
                'post_title' => $category_name,
                'post_type' => 'shorturl_category', // Assuming 'category' is the Custom Post Type
                'post_status' => 'publish',
            ];

            // Insert the new category and get the inserted post ID
            $category_id = wp_insert_post($category_data);

            // If the category was successfully inserted
            if (!is_wp_error($category_id)) {
                // Update the parent ID as post meta if applicable
                if ($parent_category > 0) {
                    update_post_meta($category_id, 'parent_id', $parent_category);
                }

                // Return the new category ID and updated category list HTML
                wp_send_json_success([
                    'category_id' => $category_id,
                    'category_list_html' => self::generate_category_list_html()
                ]);
            } else {
                // If the insertion failed, return an error message
                wp_send_json_error(__('Failed to add category. Please try again.', 'rrze-shorturl'));
            }
        }

        // Always terminate the script after handling the request
        wp_die();
    }

    private static function generate_category_list_html()
    {
        // Generate HTML for the updated category list
        ob_start();
        echo self::display_shorturl_category();
        return ob_get_clean();
    }


    public function store_link_category_callback()
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
        $category = get_term($category_id, 'link_category');
        if (!$category || is_wp_error($category)) {
            wp_send_json_error(__('Invalid category.', 'rrze-shorturl'));
        }

        // Get current categories assigned to the link
        $current_categories = wp_get_post_terms($link_id, 'link_category', ['fields' => 'ids']);

        // Check if the category is already assigned to the link
        if (in_array($category_id, $current_categories)) {
            wp_send_json_success(__('Category is already linked to the link.', 'rrze-shorturl'));
        }

        // Add the category to the link
        $result = wp_set_post_terms($link_id, array_merge($current_categories, [$category_id]), 'link_category');

        // Check if the category was successfully added
        if (!is_wp_error($result)) {
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
                <?php echo __('Self-Explanatory URI', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="uri" name="uri" value="<?php echo $val; ?>">
        </div>
        <?php
        return ob_get_clean();
    }


    public static function display_shorturl_utm($utm_source, $utm_medium, $utm_campaign, $utm_term, $utm_content)
    {

        ob_start();
        ?>
        <div>
            <label for="utm_source">
                <?php echo __('UTM Source', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="utm_source" name="utm_source" value="<?php echo $utm_source; ?>">
            <label for="utm_medium">
                <?php echo __('UTM Medium', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="utm_medium" name="utm_medium" value="<?php echo $utm_medium; ?>">
            <label for="utm_campaign">
                <?php echo __('UTM Campaign', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="utm_campaign" name="utm_campaign" value="<?php echo $utm_campaign; ?>">
            <label for="utm_term">
                <?php echo __('UTM Term', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="utm_term" name="utm_term" value="<?php echo $utm_term; ?>">
            <label for="utm_content">
                <?php echo __('UTM Content', 'rrze-shorturl'); ?>:
            </label>
            <input type="text" id="utm_content" name="utm_content" value="<?php echo $utm_content; ?>">
        </div>
        <?php
        return ob_get_clean();
    }

}


