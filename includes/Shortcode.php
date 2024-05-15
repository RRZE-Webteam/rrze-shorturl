<?php

namespace RRZE\ShortURL;


class Shortcode
{
    protected static $rights;

    public function __construct()
    {
        $rightsObj = new Rights();
        self::$rights = $rightsObj->getRights();

        add_shortcode('shorturl-test-htaccess', [$this, 'shorturl_test_shortcode']);

        add_shortcode('shorturl', [$this, 'shorturl_handler']);
        add_shortcode('shorturl-list', [$this, 'shortcode_list_handler']);
        add_shortcode('shorturl-categories', [$this, 'shortcode_categories_handler']);
        // add_shortcode('shorturl-tags', [$this, 'shortcode_tags_handler']);
        add_shortcode('shorturl-services', [$this, 'shortcode_services_handler']);

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


    public function shorturl_test_shortcode()
    {
        // Pfad zur PHP-Datei innerhalb des Plugin-Ordners
        $file_path = plugin_dir_path(__FILE__) . '../make_htaccess.php';

        // Einbinden der PHP-Datei
        if (file_exists($file_path)) {
            require_once $file_path;
            // Hier kannst du Funktionen oder Code aus make_htaccess.php verwenden
        } else {
            return 'Datei nicht gefunden ' . $file_path;
        }
    }


    // public function shortcode_tags_handler(): string
    // {
    //     global $wpdb;

    //     // Edit Tag
    //     if (!empty ($_POST['edit_tag'])) {
    //         $tag_id = (int) $_POST['tag_id'];
    //         $tag_label = sanitize_text_field($_POST['tag_label']);

    //         // Update the tag in the database
    //         $wpdb->update(
    //             "{$wpdb->prefix}shorturl_tags",
    //             array(
    //                 'label' => $tag_label,
    //             ),
    //             array('id' => $tag_id),
    //             array('%s', '%d'),
    //             array('%d')
    //         );

    //         // Return to the table after editing
    //         return $this->display_tags_table();
    //     } elseif (!empty ($_POST['add_tag'])) {
    //         // Add tag
    //         $tag_label = sanitize_text_field($_POST['tag_label']);

    //         if (!empty ($tag_label)) {
    //             // Insert tag
    //             $wpdb->insert(
    //                 "{$wpdb->prefix}shorturl_tags",
    //                 array(
    //                     'label' => $tag_label
    //                 )
    //             );
    //         }
    //         return $this->display_tags_table();
    //     }

    //     // Check if an edit form should be displayed
    //     if (!empty ($_GET['action']) && $_GET['action'] === 'edit_tag' && !empty ($_GET['tag_id'])) {
    //         // Retrieve tag details based on tag ID
    //         $tag_id = (int) $_GET['tag_id'];
    //         $tag = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shorturl_tags WHERE id = %d", $tag_id));

    //         // If tag is found, display edit form
    //         if ($tag) {
    //             // Start building the form
    //             $output = '<form method="post">';
    //             $output .= '<label for="tag_label">' . __('Tag Label', 'rrze-shorturl') . ':</label><br>';
    //             $output .= '<input type="text" id="tag_label" name="tag_label" value="' . esc_attr($tag->label) . '"><br>';

    //             // Hidden field for tag ID
    //             $output .= '<input type="hidden" name="tag_id" value="' . esc_attr($tag_id) . '">';

    //             // Submit button
    //             $output .= '<br><input type="submit" name="edit_tag" value="' . __('Save Changes', 'rrze-shorturl') . '">';
    //             $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">Cancel</a>';

    //             $output .= '</form>';

    //             return $output;
    //         }
    //     } elseif (isset ($_GET['action']) && $_GET['action'] === 'add_new_tag') {
    //         // display add tag form
    //         return $this->add_tag_form();
    //     }

    //     // If no editing is happening, display the categories table
    //     return $this->display_tags_table();
    // }

    // private function display_tags_table(): string
    // {
    //     global $wpdb;

    //     // Fetch tags along with their corresponding link counts
    //     $tags_query = "SELECT t.id, t.label, COUNT(lt.link_id) AS link_count
    //                    FROM {$wpdb->prefix}shorturl_tags t
    //                    LEFT JOIN {$wpdb->prefix}shorturl_links_tags lt ON t.id = lt.tag_id
    //                    GROUP BY t.id, t.label
    //                    ORDER BY t.label ASC";

    //     $tags = $wpdb->get_results($tags_query, ARRAY_A);

    //     // Begin HTML table
    //     $table_html = '<table class="shorturl-wp-list-table widefat">';
    //     // Table header
    //     $table_html .= '<thead><tr>';
    //     $table_html .= '<th scope="col" class="manage-column column-label">' . __('Tag', 'rrze-shorturl') . '</th>';
    //     $table_html .= '<th scope="col" class="manage-column column-link-count">' . __('Count', 'rrze-shorturl') . '</th>';
    //     $table_html .= '<th scope="col" class="manage-column column-actions">' . __('Actions', 'rrze-shorturl') . '</th>';
    //     $table_html .= '</tr></thead>';
    //     $table_html .= '<tbody>';

    //     // Iterate over each tag
    //     foreach ($tags as $tag) {
    //         $tag_id = $tag['id'];
    //         $tag_label = $tag['label'];
    //         $link_count = $tag['link_count'];

    //         // Add row for each tag
    //         $table_html .= '<tr>';
    //         $table_html .= '<td class="column-label">' . esc_html($tag_label) . '</td>';
    //         $table_html .= '<td class="column-link-count">' . esc_html($link_count) . '</td>';
    //         $table_html .= '<td class="column-actions">
    //                         <a href="?action=edit_tag&tag_id=' . esc_attr($tag_id) . '">' . __('Edit', 'rrze-shorturl') . '</a> | 
    //                         <a href="#" class="delete-tag" data-tag-id="' . esc_attr($tag_id) . '">' . __('Delete', 'rrze-shorturl') . '</a>
    //                     </td>';
    //         $table_html .= '</tr>';
    //     }

    //     // Add row for actions (add new tag)
    //     $table_html .= '<tr>';
    //     $table_html .= '<td colspan="3" class="column-actions"><a href="?action=add_new_tag">' . __('Add New Tag', 'rrze-shorturl') . '</a></td>';
    //     $table_html .= '</tr>';

    //     $table_html .= '</tbody></table>';

    //     return $table_html;
    // }

    // private function add_tag_form()
    // {
    //     $output = '<h2>' . __('Add New Tag', 'rrze-shorturl') . '</h2>';
    //     $output .= '<form method="post">';
    //     $output .= '<label for="tag_label">' . __('Tag Label', 'rrze-shorturl') . ':</label><br>';
    //     $output .= '<input type="text" id="tag_label" name="tag_label" value=""><br>';
    //     $output .= '<br><input type="submit" name="add_tag" value="' . __('Add Tag', 'rrze-shorturl') . '">';
    //     $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';
    //     $output .= '</form>';

    //     return $output;
    // }


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

    // private function display_tags_table(): string
    // {
    //     global $wpdb;

    //     // Fetch tags along with their corresponding link counts
    //     $tags_query = "SELECT t.id, t.label, COUNT(lt.link_id) AS link_count
    //                    FROM {$wpdb->prefix}shorturl_tags t
    //                    LEFT JOIN {$wpdb->prefix}shorturl_links_tags lt ON t.id = lt.tag_id
    //                    GROUP BY t.id, t.label
    //                    ORDER BY t.label ASC";

    //     $tags = $wpdb->get_results($tags_query, ARRAY_A);

    //     // Begin HTML table
    //     $table_html = '<table class="shorturl-wp-list-table widefat">';
    //     // Table header
    //     $table_html .= '<thead><tr>';
    //     $table_html .= '<th scope="col" class="manage-column column-label">' . __('Tag', 'rrze-shorturl') . '</th>';
    //     $table_html .= '<th scope="col" class="manage-column column-link-count">' . __('Count', 'rrze-shorturl') . '</th>';
    //     $table_html .= '<th scope="col" class="manage-column column-actions">' . __('Actions', 'rrze-shorturl') . '</th>';
    //     $table_html .= '</tr></thead>';
    //     $table_html .= '<tbody>';

    //     // Iterate over each tag
    //     foreach ($tags as $tag) {
    //         $tag_id = $tag['id'];
    //         $tag_label = $tag['label'];
    //         $link_count = $tag['link_count'];

    //         // Add row for each tag
    //         $table_html .= '<tr>';
    //         $table_html .= '<td class="column-label">' . esc_html($tag_label) . '</td>';
    //         $table_html .= '<td class="column-link-count">' . esc_html($link_count) . '</td>';
    //         $table_html .= '<td class="column-actions">
    //                         <a href="?action=edit_tag&tag_id=' . esc_attr($tag_id) . '">' . __('Edit', 'rrze-shorturl') . '</a> | 
    //                         <a href="#" class="delete-tag" data-tag-id="' . esc_attr($tag_id) . '">' . __('Delete', 'rrze-shorturl') . '</a>
    //                     </td>';
    //         $table_html .= '</tr>';
    //     }

    //     // Add row for actions (add new tag)
    //     $table_html .= '<tr>';
    //     $table_html .= '<td colspan="3" class="column-actions"><a href="?action=add_new_tag">' . __('Add New Tag', 'rrze-shorturl') . '</a></td>';
    //     $table_html .= '</tr>';

    //     $table_html .= '</tbody></table>';

    //     return $table_html;
    // }

    // private function add_tag_form()
    // {
    //     $output = '<h2>' . __('Add New Tag', 'rrze-shorturl') . '</h2>';
    //     $output .= '<form method="post">';
    //     $output .= '<label for="tag_label">' . __('Tag Label', 'rrze-shorturl') . ':</label><br>';
    //     $output .= '<input type="text" id="tag_label" name="tag_label" value=""><br>';
    //     $output .= '<br><input type="submit" name="add_tag" value="' . __('Add Tag', 'rrze-shorturl') . '">';
    //     $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';
    //     $output .= '</form>';

    //     return $output;
    // }


    public function makeCategoryDropdown($category_id = 0, $parent_id = 0)
    {
        global $wpdb;

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories");

        // Build the hierarchical structure for each category
        $categories_with_hierarchy = array();
        foreach ($categories as $category) {
            if ($category->id != $category_id) {
                $category->hierarchy = $this->build_category_hierarchy_table($category, $categories);
                $categories_with_hierarchy[] = $category;
            }
        }

        // Sort categories by hierarchy
        usort($categories_with_hierarchy, function ($a, $b) {
            return strcmp($a->hierarchy, $b->hierarchy);
        });

        $output = '<label for="parent_category">' . __('Parent Category', 'rrze-shorturl') . ':</label><br>';
        $output .= '<select id="parent_category" name="parent_category">';
        $output .= '<option value="0">' . __('None', 'rrze-shorturl') . '</option>';
        foreach ($categories_with_hierarchy as $category) {
            $selected = (!empty($parent_id) && ($category->id == $parent_id)) ? 'SELECTED' : '';
            $output .= '<option value="' . $category->id . '" ' . $selected . '>' . esc_html($category->hierarchy . $category->label) . '</option>';
        }
        $output .= '</select>';

        return $output;
    }

    public function shortcode_categories_handler(): string
    {
        global $wpdb;

        // Edit Category
        if (!empty($_POST['edit_category'])) {
            $category_id = (int) $_POST['category_id'];
            $category_label = sanitize_text_field($_POST['category_label']);
            $parent_category = !empty($_POST['parent_category']) ? (int) $_POST['parent_category'] : null;

            // Update the category in the database
            $wpdb->update(
                "{$wpdb->prefix}shorturl_categories",
                array(
                    'label' => $category_label,
                    'parent_id' => $parent_category,
                ),
                array('id' => $category_id),
                array('%s', '%d'),
                array('%d')
            );

            // Return to the table after editing
            return $this->display_categories_table();
        } elseif (!empty($_POST['add_category'])) {
            // Add Category
            $category_label = sanitize_text_field($_POST['category_label']);
            $parent_category = !empty($_POST['parent_category']) ? (int) $_POST['parent_category'] : null;

            if (!empty($category_label)) {
                // Insert Category
                $wpdb->insert(
                    "{$wpdb->prefix}shorturl_categories",
                    array(
                        'label' => $category_label,
                        'parent_id' => $parent_category
                    )
                );
            }
            return $this->display_categories_table();
        }

        // Check if an edit form should be displayed
        if (!empty($_GET['action']) && $_GET['action'] === 'edit_category' && !empty($_GET['category_id'])) {
            // Retrieve category details based on category ID
            $category_id = (int) $_GET['category_id'];
            $category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shorturl_categories WHERE id = %d", $category_id));

            // If category is found, display edit form
            if ($category) {
                // Start building the form
                $output = '<form method="post">';
                $output .= '<label for="category_label">' . __('Category Label', 'rrze-shorturl') . ':</label><br>';
                $output .= '<input type="text" id="category_label" name="category_label" value="' . esc_attr($category->label) . '"><br>';

                $parent_id = !empty($category->parent_id) ? $category->parent_id : 0;

                $output .= $this->makeCategoryDropdown($category_id, $parent_id);

                $output .= '<input type="hidden" name="category_id" value="' . esc_attr($category_id) . '">';

                $output .= '<br><input type="submit" name="edit_category" value="' . __('Save Changes', 'rrze-shorturl') . '">';
                $output .= '&nbsp;<a href="' . esc_url(remove_query_arg('action')) . '" class="button">' . __('Cancel', 'rrze-shorturl') . '</a>';

                $output .= '</form>';

                return $output;
            }
        } elseif (isset($_GET['action']) && $_GET['action'] === 'add_new_category') {
            // display add category form
            return $this->add_category_form();
        }

        // If no editing is happening, display the categories table
        return $this->display_categories_table();
    }

    // Helper function to display the categories table
    private function display_categories_table()
    {
        global $wpdb;

        // Get all categories from the shorturl_categories table
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories");

        // Build the hierarchical structure for each category
        $categories_with_hierarchy = array();
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
            // Build the table row
            $output .= '<tr>';
            $output .= '<td class="column-label">' . esc_html($category->hierarchy . $category->label) . '</td>';
            $output .= '<td class="column-actions">
                            <a href="?action=edit_category&category_id=' . esc_attr($category->id) . '">' . __('Edit', 'rrze-shorturl') . '</a> | 
                            <a href="" class="delete-category" data-category-id="' . esc_attr($category->id) . '">' . __('Delete', 'rrze-shorturl') . '</a>
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
        global $wpdb;

        // Define the table names
        $links_table = $wpdb->prefix . 'shorturl_links';
        $categories_table = $wpdb->prefix . 'shorturl_links_categories';
        // $tags_table = $wpdb->prefix . 'shorturl_links_tags';

        // Prepare the SQL query to fetch link data by ID
        // $query = $wpdb->prepare("
        //     SELECT l.*, GROUP_CONCAT(DISTINCT lc.category_id) AS category_ids, 
        //     GROUP_CONCAT(DISTINCT lt.tag_id) AS tag_ids
        //     FROM $links_table l
        //     LEFT JOIN $categories_table AS lc ON l.id = lc.link_id
        //     LEFT JOIN $tags_table AS lt ON l.id = lt.link_id
        //     WHERE l.id = %d
        //     GROUP BY l.id
        // ", $link_id);

        $query = $wpdb->prepare("
            SELECT l.*, GROUP_CONCAT(DISTINCT lc.category_id) AS category_ids
            FROM $links_table l
            LEFT JOIN $categories_table AS lc ON l.id = lc.link_id
            WHERE l.id = %d
            GROUP BY l.id
        ", $link_id);

        // Execute the query
        $result = $wpdb->get_row($query, ARRAY_A);

        return $result;
    }

    public function delete_link_callback()
    {
        global $wpdb;

        // Check if the request is coming from a valid source
        check_ajax_referer('delete_shorturl_link_nonce', '_ajax_nonce');

        // Get the link ID from the AJAX request
        $link_id = !empty($_POST['link_id']) ? (int) $_POST['link_id'] : 0;

        // Delete the link from the database
        $result = $wpdb->delete(
            $wpdb->prefix . 'shorturl_links',
            array('id' => $link_id),
            array('%d')
        );

        if ($result !== false) {
            // Link deleted successfully
            wp_send_json_success(__('Link deleted successfully', 'rrze-shorturl'));
        } else {
            // Error deleting link
            wp_send_json_error(__('Error deleting link', 'rrze-shorturl'));
        }

        // Always exit to avoid further execution
        wp_die();
    }

    public function delete_category_callback()
    {
        global $wpdb;

        // Check if the request is coming from a valid source
        check_ajax_referer('delete_shorturl_category_nonce', '_ajax_nonce');

        // Get the category ID from the AJAX request
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : 0;

        // Get the parent_id of the category being deleted
        $parent_id = $wpdb->get_var($wpdb->prepare("SELECT parent_id FROM {$wpdb->prefix}shorturl_categories WHERE id = %d", $category_id));

        // Update parent_id to the parent_id of the category being deleted for child categories
        if ($parent_id !== null) {
            $wpdb->update(
                $wpdb->prefix . 'shorturl_categories',
                array('parent_id' => $parent_id),
                array('parent_id' => $category_id),
                array('%d'),
                array('%d')
            );
        } else {
            // If there's no next hierarchical level, set parent_id to NULL for child categories
            $wpdb->update(
                $wpdb->prefix . 'shorturl_categories',
                array('parent_id' => null),
                array('parent_id' => $category_id),
                array('%d'),
                array('%d')
            );
        }

        // Delete the category from the database
        $result = $wpdb->delete(
            $wpdb->prefix . 'shorturl_categories',
            array('id' => $category_id),
            array('%d')
        );

        if ($result !== false) {
            // Category deleted successfully
            wp_send_json_success(__('Category deleted successfully', 'rrze-shorturl'));
        } else {
            // Error deleting category
            wp_send_json_error(__('Error deleting category', 'rrze-shorturl'));
        }

        // Always exit to avoid further execution
        wp_die();
    }


    // public function add_shorturl_tag_callback()
    // {
    //     check_ajax_referer('add_shorturl_tag_nonce', '_ajax_nonce');

    //     if (!empty ($_POST['new_tag_name'])) {
    //         global $wpdb;
    //         $newTagName = sanitize_text_field($_POST['new_tag_name']);
    //         // Insert the new tag into the database
    //         $wpdb->insert(
    //             $wpdb->prefix . 'shorturl_tags',
    //             array('label' => $newTagName),
    //             array('%s')
    //         );
    //         // Return the ID of the newly inserted tag
    //         return wp_send_json_success(['id' => $wpdb->insert_id]);
    //     }
    //     wp_die();
    // }


    // public function delete_tag_callback()
    // {
    //     global $wpdb;

    //     // Check if the request is coming from a valid source
    //     check_ajax_referer('delete_shorturl_tag_nonce', '_ajax_nonce');

    //     // Get the tag ID from the AJAX request
    //     $tag_id = !empty ($_POST['tag_id']) ? (int) $_POST['tag_id'] : 0;

    //     // Delete the tag from the database
    //     $result = $wpdb->delete(
    //         $wpdb->prefix . 'shorturl_tags',
    //         array('id' => $tag_id),
    //         array('%d')
    //     );

    //     if ($result !== false) {
    //         wp_send_json_success(__('Tag deleted successfully', 'rrze-shorturl'));
    //     } else {
    //         wp_send_json_error(__('Error deleting tag', 'rrze-shorturl'));
    //     }

    //     // Always exit to avoid further execution
    //     wp_die();
    // }


    public function shorturl_handler($atts = null): string
    {
        $idm = '';

        $aParams = [
            'url' => sanitize_text_field($_POST['url']),
            'uri' => self::$rights['uri_allowed'] ? sanitize_text_field($_POST['uri'] ?? '') : '',
            'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
            'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [],
            // 'tags' => !empty ($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : [],
        ];

        $result_message = ''; // Initialize result message
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Check if URL is provided
            if (!empty($aParams['url'])) {
                $result = ShortURL::shorten($aParams);
                $result_message = ($result['error'] ? 'Error: ' : __('Short URL', 'rrze-shorturl')) . ': ' . $result['txt'];
                $result_message .= (!$result['error'] ? '&nbsp;&nbsp;<button type="button" class="btn" id="copyButton" name="copyButton" data-shortened-url="' . $result['txt'] . '"><img class="shorturl-copy-img" src="data:image/svg+xml,%3Csvg height=\'1024\' width=\'896\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z\' fill=\'%23FFFFFF\' /%3E%3C/svg%3E" alt="' . __('Copy to clipboard', 'rrze-shorturl') . '"></button>&nbsp;&nbsp;<span id="shorturl-tooltip" class="shorturl-tooltip">' . __('Copied to clipboard', 'rrze-shorturl') . '</span>' : '');
                $aParams['url'] = $result['long_url']; // we might have added the scheme
            }
        }

        // Generate form
        $form = '<form id="shorturl-form" method="post">';
        $form .= '<div class="postbox">';
        $form .= '<h2 class="handle">' . __('Create Short URL', 'rrze-shorturl') . '</h2>';
        $form .= '<div class="inside">';
        $form .= '<label for="url">' . __('Your link', 'rrze-shorturl') . ':</label>&nbsp;';
        $form .= '<input type="text" name="url" id="url" value="' . esc_attr($aParams['url']) . '" placeholder="https://">';
        $form .= '<input type="submit" id="generate" name="generate" value="' . __('Shorten', 'rrze-shorturl') . '">';
        $form .= '<input type="hidden" name="link_id" value="' . (!empty($result['link_id']) ? $result['link_id'] : '') . '">';
        $form .= '</div>';
        $form .= '</div>';
        $form .= '<div id="shorturl-advanced-settings" class="shorturl-advanced-settings" popover>';
        if (self::$rights['uri_allowed']) {
            $form .= self::display_shorturl_uri($aParams['uri']);
        }
        $form .= self::display_shorturl_validity($aParams['valid_until']);
        $form .= '<h6 class="handle">' . __('Categories', 'rrze-shorturl') . '</h6>';
        $form .= self::display_shorturl_category($aParams['categories']);
        $form .= '</div>';
        $form .= '<button id="btn-show-advanced-settings" type="button" popovertarget="shorturl-advanced-settings">' . __('Advanced Settings', 'rrze-shorturl') . '</button>';

        // Display result message
        $form .= '<div><p>' . $result_message;
        $form .= '</p>';
        if (!empty($result) && !$result['error']) {
            $form .= '<input id="shortened_url" name="shortened_url" type="hidden" value="' . $result['txt'] . '">';
            // $form .= '<div id="qr-container"><canvas id="qr"></canvas><img src="' . plugins_url('../', __FILE__) . 'assets/img/FAU.svg' . '" id="qr-logo"></div>';
            $form .= '<div id="qr-container"><canvas id="qr"></canvas><button type="button" class="btn" id="downloadButton" name="downloadButton"><img class="shorturl-download-img" src="data:image/svg+xml,%3Csvg width=\'512\' height=\'512\' viewBox=\'0 0 512 512\' xmlns=\'http://www.w3.org/2000/svg\' fill=\'%23FFFFFF\'%3E%3Cpath d=\'M376.3 304.3l-71.4 71.4V48c0-8.8-7.2-16-16-16h-48c-8.8 0-16 7.2-16 16v327.6l-71.4-71.4c-6.2-6.2-16.4-6.2-22.6 0l-22.6 22.6c-6.2 6.2-6.2 16.4 0 22.6l128 128c6.2 6.2 16.4 6.2 22.6 0l128-128c6.2-6.2 6.2-16.4 0-22.6l-22.6-22.6c-6.2-6.2-16.4-6.2-22.6 0zM464 448H48c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h416c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16z\'/%3E%3C/svg%3E" alt="' . __('Download QR', 'rrze-shorturl') . '"></button></div>';
        }
        $form .= '</form>';

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
        global $wpdb;

        // Retrieve categories from the shorturl_categories table
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories");

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
        global $wpdb;
        $bUpdated = false;
        $message = '';

        if (!empty($_POST['action']) && $_POST['action'] === 'update_link' && !empty($_POST['link_id'])) {
            // UPDATE link

            $aParams = [
                'idm_id' => self::$rights['id'],
                'link_id' => htmlspecialchars($_POST['link_id'] ?? ''),
                'domain_id' => htmlspecialchars($_POST['domain_id'] ?? ''),
                'shortURL' => filter_var($_POST['shortURL'] ?? '', FILTER_VALIDATE_URL),
                'uri' => self::$rights['uri_allowed'] ? sanitize_text_field($_POST['uri'] ?? '') : '',
                'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
                'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : []
            ];

            // ShortURL::updateLink($aParams['idm_id'], $aParams['link_id'], $aParams['domain_id'], $aParams['shortURL'], $aParams['uri'], $aParams['valid_until'], $aParams['categories'], $aParams['tags']);
            ShortURL::updateLink($aParams['idm_id'], $aParams['link_id'], $aParams['domain_id'], $aParams['shortURL'], $aParams['uri'], $aParams['valid_until'], $aParams['categories']);

            $bUpdated = true;
            $message = __('Link updated', 'rrze-shorturl');
        }

        $links_table = $wpdb->prefix . 'shorturl_links';
        $links_categories_table = $wpdb->prefix . 'shorturl_links_categories';
        // $links_tags_table = $wpdb->prefix . 'shorturl_links_tags';
        $categories_table = $wpdb->prefix . 'shorturl_categories';
        // $tags_table = $wpdb->prefix . 'shorturl_tags';

        // Fetch all categories
        $categories = $wpdb->get_results("SELECT id, label FROM $categories_table", ARRAY_A);

        // Determine the column to sort by and sort order
        $orderby = !empty($_GET['orderby']) ? $_GET['orderby'] : 'id';
        $order = !empty($_GET['order']) ? $_GET['order'] : 'ASC';

        $own_links = !empty($_GET['own_links']) ? $_GET['own_links'] : 0;

        // Prepare SQL query to fetch post IDs from wp_postmeta and their associated category names
        $query = "SELECT l.id AS link_id, 
                 l.idm_id,
                 l.long_url, 
                 l.short_url, 
                 l.uri, 
                 DATE_FORMAT(l.valid_until, '%d.%m.%Y') AS valid_until, 
                 GROUP_CONCAT(DISTINCT lc.category_id) AS category_ids
                --  , GROUP_CONCAT(DISTINCT lt.tag_id) AS tag_ids
          FROM $links_table l
          LEFT JOIN $links_categories_table AS lc ON l.id = lc.link_id";

        // Additional filter based on $own_links checkbox
        if ($own_links == 1) {
            $query .= " WHERE l.idm_id = " . self::$rights['id'];
        }

        $query .= " GROUP BY l.id, l.long_url, l.short_url, l.uri, l.valid_until";

        // Handle filtering by category or tag
        $filter_category = !empty($_GET['filter_category']) ? (int) $_GET['filter_category'] : 0;
        // $filter_tag = !empty ($_GET['filter_tag']) ? (int) $_GET['filter_tag'] : 0;

        if ($filter_category > 0) {
            $query .= " HAVING FIND_IN_SET('$filter_category', category_ids) > 0";
        }

        // if ($filter_tag > 0) {
        //     $query .= " HAVING FIND_IN_SET('$filter_tag', tag_ids) > 0";
        // }

        $query .= " ORDER BY $orderby $order";

        $results = $wpdb->get_results($query, ARRAY_A);

        // Update message
        $table = '<div class="updated"><p>' . $message . '</p></div>';

        // Generate category filter dropdown
        $category_filter_dropdown = '<select name="filter_category">';
        $category_filter_dropdown .= '<option value="0">' . __('All Categories', 'rrze-shorturl') . '</option>';
        foreach ($categories as $category) {
            $category_filter_dropdown .= '<option value="' . $category['id'] . '"' . ($filter_category == $category['id'] ? ' selected' : '') . '>' . $category['label'] . '</option>';
        }
        $category_filter_dropdown .= '</select>';

        // Generate filter button
        $filter_button = '<button type="submit">Filter</button>';

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
        // Table header
        $table .= '<thead><tr>';
        $table .= '<th scope="col" class="manage-column column-long-url"><a href="?orderby=long_url&order=' . ($orderby == 'long_url' && $order == 'ASC' ? 'DESC' : 'ASC') . '">' . __('Long URL', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col" class="manage-column column-short-url"><a href="?orderby=short_url&order=' . ($orderby == 'short_url' && $order == 'ASC' ? 'DESC' : 'ASC') . '">' . __('Short URL', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col" class="manage-column column-uri">URI</th>';
        $table .= '<th scope="col" class="manage-column column-valid-until"><a href="?orderby=valid_until&order=' . ($orderby == 'valid_until' && $order == 'ASC' ? 'DESC' : 'ASC') . '">' . __('Valid until', 'rrze-shorturl') . '</a></th>';
        $table .= '<th scope="col" class="manage-column column-categories">' . __('Categories', 'rrze-shorturl') . '</th>';
        // $table .= '<th scope="col" class="manage-column column-tags">' . __('Tags', 'rrze-shorturl') . '</th>';
        $table .= '<th scope="col" class="manage-column column-actions">' . __('Actions', 'rrze-shorturl') . '</th>';
        $table .= '</tr></thead><tbody>';

        if (empty($results)) {
            $table .= '<tr><td colspan="7">' . __('No links stored yet', 'rrze-shorturl') . '</td></tr>';
        }

        foreach ($results as $row) {
            // Fetch and concatenate category names with links
            $category_ids = !empty($row['category_ids']) ? explode(',', $row['category_ids']) : [];
            $category_names = [];
            foreach ($category_ids as $category_id) {
                $category_name = $wpdb->get_var("SELECT label FROM $categories_table WHERE id = $category_id");
                if ($category_name) {
                    $category_names[] = '<a href="?filter_category=' . $category_id . '">' . $category_name . '</a>';
                }
            }
            $category_names_str = implode(', ', $category_names);

            // Fetch and concatenate tag names with links
            // $tag_ids = !empty ($row['tag_ids']) ? explode(',', $row['tag_ids']) : [];
            // $tag_names = [];
            // foreach ($tag_ids as $tag_id) {
            //     $tag_name = $wpdb->get_var("SELECT label FROM $tags_table WHERE id = $tag_id");
            //     if ($tag_name) {
            //         $tag_names[] = '<a href="?filter_tag=' . $tag_id . '">' . $tag_name . '</a>';
            //     }
            // }
            // $tag_names_str = implode(', ', $tag_names);

            // Output table row
            $table .= '<tr>';
            $table .= '<td class="column-long-url">' . $row['long_url'] . '</td>';
            $table .= '<td class="column-short-url">' . $row['short_url'] . '</td>';
            $table .= '<td class="column-uri">' . $row['uri'] . '</td>';
            $table .= '<td class="column-valid-until">' . $row['valid_until'] . '</td>';
            $table .= '<td class="column-categories">' . $category_names_str . '</td>';
            // $table .= '<td class="column-tags">' . $tag_names_str . '</td>';
            $table .= '<td class="column-actions"><a href="#" class="edit-link" data-link-id="' . $row['link_id'] . '">' . __('Edit', 'rrze-shorturl') . '</a>' . (self::$rights['id'] == $row['idm_id'] ? ' | <a href="#" data-link-id="' . $row['link_id'] . '" class="delete-link">' . __('Delete', 'rrze-shorturl') . '</a>' : '') . '</td>';
            $table .= '</tr>';
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
            }
        }
    }

    public static function update_category_label()
    {
        // Verify nonce
        if (!!empty($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'update_category_label_nonce')) {
            wp_send_json_error('Nonce verification failed.');
        }

        global $wpdb;

        // Get category ID and updated label from AJAX request
        $category_id = (int) $_POST['category_id'];
        $updated_label = sanitize_text_field($_POST['updated_label']);

        // Update category label in the database
        $update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}shorturl_categories SET label = %s WHERE id = %d", $updated_label, $category_id);
        $result = $wpdb->query($update_query);

        // Check if update was successful
        if ($result !== false) {
            // Return success message
            wp_send_json_success(__('Category updated', 'rrze-shorturl'));
        } else {
            // Return error message
            wp_send_json_error(__('Error: Could not update category', 'rrze-shorturl'));
        }

        // Don't forget to exit
        wp_die();
    }



    public function add_shorturl_category_callback()
    {
        check_ajax_referer('add_shorturl_category_nonce', '_ajax_nonce');

        $category_name = !empty($_POST['categoryName']) ? sanitize_text_field($_POST['categoryName']) : '';
        $parent_category = !empty($_POST['parentCategory']) ? (int) $_POST['parentCategory'] : null;

        if (empty($category_name)) {
            wp_send_json_error(__('Category name is required.', 'rrze-shorturl'));
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'shorturl_categories';

        // Check if category already exists
        $existing_category = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE label = %s", $category_name));

        if ($existing_category) {
            // Category already exists, return its ID
            wp_send_json_success(['category_id' => $existing_category->id, 'category_list_html' => self::generate_category_list_html()]);
        } else {
            // Insert new category
            $inserted = $wpdb->insert($table_name, ['label' => $category_name, 'parent_id' => $parent_category]);

            if ($inserted) {
                $category_id = $wpdb->insert_id;
                wp_send_json_success(['category_id' => $category_id, 'category_list_html' => self::generate_category_list_html()]);
            } else {
                wp_send_json_error(__('Failed to add category. Please try again.', 'rrze-shorturl'));
            }
        }
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
        check_ajax_referer('store-link-category', '_ajax_nonce');

        $link_id = !empty($_POST['linkId']) ? (int) $_POST['linkId'] : 0;
        $category_id = !empty($_POST['categoryId']) ? (int) $_POST['categoryId'] : 0;

        if ($link_id <= 0 || $category_id <= 0) {
            wp_send_json_error(__('Invalid link ID or category ID.', 'rrze-shorturl'));
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'shorturl_links_categories';

        // Check if the link-category association already exists
        $existing_association = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE link_id = %d AND category_id = %d", $link_id, $category_id));

        if ($existing_association) {
            // Association already exists
            wp_send_json_success(__('Association already exists.', 'rrze-shorturl'));
        } else {
            // Insert new association
            $inserted = $wpdb->insert($table_name, ['link_id' => $link_id, 'category_id' => $category_id]);

            if ($inserted) {
                wp_send_json_success(__('Category linked to the link successfully.', 'rrze-shorturl'));
            } else {
                wp_send_json_error(__('Failed to link category to the link. Please try again.', 'rrze-shorturl'));
            }
        }
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

}


