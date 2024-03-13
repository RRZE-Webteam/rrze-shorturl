<?php

namespace RRZE\ShortURL;


class Shortcode
{
    public function __construct()
    {
        add_shortcode('shorturl', [$this, 'shorturl_handler']);
        add_shortcode('shorturl-list', [$this, 'list_shortcode_handler']);

        add_action('wp_ajax_nopriv_store_link_category', [$this, 'store_link_category_callback']);
        add_action('wp_ajax_store_link_category', [$this, 'store_link_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_category', [$this, 'add_shorturl_category_callback']);
        add_action('wp_ajax_add_shorturl_category', [$this, 'add_shorturl_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_tag', [$this, 'add_shorturl_tag_callback']);
        add_action('wp_ajax_add_shorturl_tag', [$this, 'add_shorturl_tag_callback']);

        add_action('wp_ajax_nopriv_update_category_label_action', [$this, 'update_category_label']);
        add_action('wp_ajax_update_category_label_action', [$this, 'update_category_label']);

        add_action('wp_ajax_nopriv_delete_link', [$this, 'delete_link_callback']);
        add_action('wp_ajax_delete_link', [$this, 'delete_link_callback']);
    }

    private function get_link_data_by_id($link_id)
    {
        global $wpdb;

        // Define the table names
        $links_table = $wpdb->prefix . 'shorturl_links';
        $categories_table = $wpdb->prefix . 'shorturl_links_categories';
        $tags_table = $wpdb->prefix . 'shorturl_links_tags';

        // Prepare the SQL query to fetch link data by ID
        $query = $wpdb->prepare("
            SELECT l.*, GROUP_CONCAT(DISTINCT lc.category_id) AS category_ids, GROUP_CONCAT(DISTINCT lt.tag_id) AS tag_ids
            FROM $links_table l
            LEFT JOIN $categories_table AS lc ON l.id = lc.link_id
            LEFT JOIN $tags_table AS lt ON l.id = lt.link_id
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
        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;

        // Delete the link from the database
        $result = $wpdb->delete(
            $wpdb->prefix . 'shorturl_links',
            array('id' => $link_id),
            array('%d')
        );

        if ($result !== false) {
            // Link deleted successfully
            wp_send_json_success('Link deleted successfully');
        } else {
            // Error deleting link
            wp_send_json_error('Error deleting link');
        }

        // Always exit to avoid further execution
        wp_die();
    }

    public function add_shorturl_tag_callback()
    {
        check_ajax_referer('add_shorturl_tag_nonce', '_ajax_nonce');

        if (isset($_POST['new_tag_name'])) {
            global $wpdb;
            $newTagName = sanitize_text_field($_POST['new_tag_name']);
            // Insert the new tag into the database
            $wpdb->insert(
                $wpdb->prefix . 'shorturl_tags',
                array('label' => $newTagName),
                array('%s')
            );
            // Return the ID of the newly inserted tag
            return wp_send_json_success(['id' => $wpdb->insert_id]);
        }
        wp_die();
    }

    public function shorturl_handler($atts = null): string
    {

        $aParams = [
            'url' => filter_var($_POST['url'] ?? '', FILTER_VALIDATE_URL),
            'uri' => sanitize_text_field($_POST['uri'] ?? ''),
            'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
            'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [],
            'tags' => !empty($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : [],
        ];

        $result_message = ''; // Initialize result message

        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Check if URL is provided
            if (!empty($aParams['url'])) {
                // Call ShortURL::shorten() and add the result if URL is given
                $result = ShortURL::shorten($aParams);
                $result_message = ($result['error'] ? 'Error: ' : 'Short URL: ') . $result['txt'];
            }
        }

        // Generate form
        $form = '';
        $form .= '<form method="post">';
        $form .= '<div class="postbox">';
        $form .= '<h2 class="hndle">Create Short URL</h2>';
        $form .= '<div class="inside">';
        $form .= '<label for="url">Long URL:</label>';
        $form .= '<input type="text" name="url" value="' . esc_attr($aParams['url']) . '">';
        $form .= '<input type="hidden" name="link_id" value="' . (!empty($result['link_id']) ? $result['link_id'] : '') . '">';
        $form .= '</div>';
        $form .= '</div>';

        $form .= '<p><a href="#" id="show-advanced-settings">Advanced Settings</a></p>';
        $form .= '<div id="div-advanced-settings" style="display: none;">';
        // $form .= '<h2 class="handle">Self-Explanatory URI</h2>';
        $form .= self::display_shorturl_uri($aParams['uri']);
        // $form .= '<h2 class="handle">Validity</h2>';
        $form .= self::display_shorturl_validity($aParams['valid_until']);
        $form .= '<h2 class="handle">Categories</h2>';
        $form .= self::display_shorturl_category($aParams['categories']);
        $form .= '<h2 class="handle">Tags</h2>';
        $form .= self::display_shorturl_tag($aParams['tags']);
        $form .= '</div>';

        $form .= '<input type="submit" id="generate" name="generate" value="Generate">';
        $form .= '</form>';

        // Display result message
        $form .= '<div><p>' . $result_message;
        $form .= '</p>';
        if (!empty($result) && !$result['error']) {
            $form .= '<canvas id="qr"></canvas>';
            $form .= '<script>';
            $form .= 'jQuery(document).ready(function ($) {';
            $form .= 'var qr = new QRious({';
            $form .= 'element: document.getElementById("qr"),';
            $form .= 'value: "' . $result['txt'] . '",';
            $form .= 'size: 200';
            $form .= '});';
            $form .= '});';
            $form .= '</script><div>';
        }


        return $form;
    }

    public static function display_shorturl_validity($val)
    {
        // Output HTML
        ob_start();

        // Output the form
        ?>
        <label for="valid_until">Valid Until:</label>
        <input type="date" id="valid_until" name="valid_until" value="<?php echo $val; ?>">
        <?php
        return ob_get_clean();

    }

    public function add_new_tag_callback()
    {
        if (isset($_POST['new_tag'])) {
            $new_tag = sanitize_text_field($_POST['new_tag']);
            // Add your logic to insert $new_tag into the shorturl_tags table
            // Replace the following line with your actual database insertion code
            echo 'New tag added: ' . $new_tag;
        } else {
            echo 'Error: No tag provided.';
        }
        wp_die();
    }



    public static function display_shorturl_category($aVal)
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
            <p><a href="#" id="add-new-shorturl-category">Add New Category</a></p>
            <div id="new-shorturl-category" style="display: none;">
                <input type="text" name="new_shorturl_category" placeholder="New Category Name">
                <select name="parent_category">
                    <option value="0">None</option>
                    <?php self::display_parent_categories_dropdown($hierarchicalCategories); ?>
                </select>
                <input type="button" value="Add Category" id="add-shorturl-category-btn">
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


    public static function getTagLabels()
    {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_tags", ARRAY_A);

    }

    private static function display_shorturl_tag($aVal)
    {
        global $wpdb;

        $tags = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_tags");

        ob_start();
        ?>
        <div id="shorturl-tag-metabox">
            <label for="tag-tokenfield">Tags:</label>
            <select id="tag-tokenfield" name="tags[]" multiple="multiple" style="width: 100%;">
                <?php foreach ($tags as $tag): ?>
                    <?php
                    // Check if the current tag ID exists in $aVal
                    $isSelected = in_array($tag->id, $aVal) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr($tag->id); ?>" <?php echo $isSelected; ?>>
                        <?php echo esc_html($tag->label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }





    public function list_shortcode_handler(): string
    {
        global $wpdb;
        $bUpdated = false;
        $message = '';

        if (isset($_POST['action']) && $_POST['action'] === 'update_link' && isset($_POST['link_id'])) {
            // UPDATE link

            $aParams = [
                'link_id' => htmlspecialchars($_POST['link_id'] ?? ''),
                'domain_id' => htmlspecialchars($_POST['domain_id'] ?? ''),
                'shortURL' => filter_var($_POST['shortURL'] ?? '', FILTER_VALIDATE_URL),
                'uri' => sanitize_text_field($_POST['uri'] ?? ''),
                'valid_until' => sanitize_text_field($_POST['valid_until'] ?? ''),
                'categories' => !empty($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [],
                'tags' => !empty($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : [],
            ];

            ShortURL::updateLink($aParams['link_id'], $aParams['domain_id'], $aParams['shortURL'], $aParams['uri'], $aParams['valid_until'], $aParams['categories'], $aParams['tags']);

            $bUpdated = true;
            $message = 'Link updated';
        }

        $links_table = $wpdb->prefix . 'shorturl_links';
        $links_categories_table = $wpdb->prefix . 'shorturl_links_categories';
        $links_tags_table = $wpdb->prefix . 'shorturl_links_tags';
        $categories_table = $wpdb->prefix . 'shorturl_categories';
        $tags_table = $wpdb->prefix . 'shorturl_tags';

        // Determine the column to sort by and sort order
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'id';
        $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

        // Prepare SQL query to fetch post IDs from wp_postmeta and their associated category names
        $query = "SELECT l.id AS link_id, 
                     l.long_url, 
                     l.short_url, 
                     l.uri, 
                     DATE_FORMAT(l.valid_until, '%d.%m.%Y') AS valid_until, 
                     GROUP_CONCAT(DISTINCT lc.category_id) AS category_ids,
                     GROUP_CONCAT(DISTINCT lt.tag_id) AS tag_ids
              FROM $links_table l
              LEFT JOIN $links_categories_table AS lc ON l.id = lc.link_id
              LEFT JOIN $links_tags_table AS lt ON l.id = lt.link_id
              GROUP BY l.id, l.long_url, l.short_url, l.uri, l.valid_until
              ORDER BY $orderby $order";

        $results = $wpdb->get_results($query, ARRAY_A);

        // update message
        $table = '<div class="updated"><p>' . $message . '</p></div>';

        // Generate table
        $table .= '<table class="wp-list-table widefat striped">';
        // Table header
        $table .= '<thead><tr>';
        $table .= '<th scope="col" class="manage-column column-long-url"><a href="?orderby=long_url&order=' . ($orderby == 'long_url' && $order == 'ASC' ? 'DESC' : 'ASC') . '">Long URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-short-url"><a href="?orderby=short_url&order=' . ($orderby == 'short_url' && $order == 'ASC' ? 'DESC' : 'ASC') . '">Short URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-uri">URI</th>';
        $table .= '<th scope="col" class="manage-column column-valid-until"><a href="?orderby=valid_until&order=' . ($orderby == 'valid_until' && $order == 'ASC' ? 'DESC' : 'ASC') . '">Valid Until</a></th>';
        $table .= '<th scope="col" class="manage-column column-categories">Categories</th>';
        $table .= '<th scope="col" class="manage-column column-tags">Tags</th>';
        $table .= '<th scope="col" class="manage-column column-actions">Actions</th>';
        $table .= '</tr></thead><tbody>';

        if (empty($results)){
            $table .= '<tr><td colspan="7">No links stored yet.</td></tr>';
        }

        foreach ($results as $row) {
            // Fetch and concatenate category names
            $category_ids = !empty($row['category_ids']) ? explode(',', $row['category_ids']) : [];
            $category_names = [];
            foreach ($category_ids as $category_id) {
                $category_name = $wpdb->get_var("SELECT label FROM $categories_table WHERE id = $category_id");
                if ($category_name) {
                    $category_names[] = $category_name;
                }
            }
            $category_names_str = implode(', ', $category_names);

            // Fetch and concatenate tag names
            $tag_ids = !empty($row['tag_ids']) ? explode(',', $row['tag_ids']) : [];
            $tag_names = [];
            foreach ($tag_ids as $tag_id) {
                $tag_name = $wpdb->get_var("SELECT label FROM $tags_table WHERE id = $tag_id");
                if ($tag_name) {
                    $tag_names[] = $tag_name;
                }
            }
            $tag_names_str = implode(', ', $tag_names);

            // Output table row
            $table .= '<tr>';
            $table .= '<td class="column-long-url">' . $row['long_url'] . '</td>';
            $table .= '<td class="column-short-url">' . $row['short_url'] . '</td>';
            $table .= '<td class="column-uri">' . $row['uri'] . '</td>';
            $table .= '<td class="column-valid-until">' . $row['valid_until'] . '</td>';
            $table .= '<td class="column-categories">' . $category_names_str . '</td>';
            $table .= '<td class="column-tags">' . $tag_names_str . '</td>';
            $table .= '<td class="column-actions"><a href="#" class="edit-link" data-link-id="' . $row['link_id'] . '">Edit</a> | <a href="#" data-link-id="' . $row['link_id'] . '" class="delete-link">Delete</a></td>';
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
        $link_id = isset($_GET['link_id']) ? intval($_GET['link_id']) : 0;

        if ($link_id <= 0) {
            return '';
        } else {
            // Load the link data from the database
            $link_data = $this->get_link_data_by_id($link_id);
            if (empty($link_data)){
                return '';
            }else{

            $aCategories = !empty($link_data['category_ids']) ? explode(',', $link_data['category_ids']) : [];
            $aTags = !empty($link_data['tag_ids']) ? explode(',', $link_data['tag_ids']) : [];

            // Display the edit form
            ob_start();
            ?>
            <div id="edit-link-form">
                <h2>Edit Link</h2>
                <form id="edit-link-form" method="post" action="">
                    <input type="hidden" name="action" value="update_link">
                    <input type="hidden" name="link_id" value="<?php echo esc_attr($link_id); ?>">
                    <input type="hidden" name="domain_id" value="<?php echo esc_attr($link_data['domain_id']); ?>">
                    <input type="hidden" name="shortURL" value="<?php echo esc_attr($link_data['shortURL']); ?>">
                    <input type="hidden" name="uri"
                        value="<?php echo !empty($link_data['uri']) ? esc_attr($link_data['uri']) : ''; ?>">
                    <?php echo self::display_shorturl_validity($link_data['valid_until']); ?>
                    <h2 class="handle">Categories</h2>
                    <?php echo self::display_shorturl_category($aCategories); ?>
                    <h2 class="handle">Tags</h2>
                    <?php echo self::display_shorturl_tag($aTags); ?>
                    <button type="submit">Update Link</button>
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
        if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'update_category_label_nonce')) {
            wp_send_json_error('Nonce verification failed.');
        }

        global $wpdb;

        // Get category ID and updated label from AJAX request
        $category_id = intval($_POST['category_id']);
        $updated_label = sanitize_text_field($_POST['updated_label']);

        // Update category label in the database
        $update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}shorturl_categories SET label = %s WHERE id = %d", $updated_label, $category_id);
        $result = $wpdb->query($update_query);

        // Check if update was successful
        if ($result !== false) {
            // Return success message
            wp_send_json_success('Category updated');
        } else {
            // Return error message
            wp_send_json_error('Error: Could not update category');
        }

        // Don't forget to exit
        wp_die();
    }



    public function add_shorturl_category_callback()
    {
        check_ajax_referer('add_shorturl_category_nonce', '_ajax_nonce');

        $category_name = isset($_POST['categoryName']) ? sanitize_text_field($_POST['categoryName']) : '';
        $parent_category = isset($_POST['parentCategory']) ? intval($_POST['parentCategory']) : 0;

        if (empty($category_name)) {
            wp_send_json_error('Category name is required.');
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
                wp_send_json_error('Failed to add category. Please try again.');
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

        $link_id = isset($_POST['linkId']) ? intval($_POST['linkId']) : 0;
        $category_id = isset($_POST['categoryId']) ? intval($_POST['categoryId']) : 0;

        if ($link_id <= 0 || $category_id <= 0) {
            wp_send_json_error('Invalid link ID or category ID.');
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'shorturl_links_categories';

        // Check if the link-category association already exists
        $existing_association = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE link_id = %d AND category_id = %d", $link_id, $category_id));

        if ($existing_association) {
            // Association already exists
            wp_send_json_success('Association already exists.');
        } else {
            // Insert new association
            $inserted = $wpdb->insert($table_name, ['link_id' => $link_id, 'category_id' => $category_id]);

            if ($inserted) {
                wp_send_json_success('Category linked to the link successfully.');
            } else {
                wp_send_json_error('Failed to link category to the link. Please try again.');
            }
        }
    }

    public static function display_shorturl_uri($val)
    {
        ob_start();
        ?>
        <div>
            <label for="self_explanatory_uri">Self-Explanatory URI:</label>
            <input type="text" id="uri" name="uri" value="<?php echo $val; ?>">
        </div>
        <?php
        return ob_get_clean();
    }

}

