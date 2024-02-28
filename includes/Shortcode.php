<?php

namespace RRZE\ShortURL;

use RRZE\ShortURL\Walker_Category_Checklist_Custom;

class Shortcode {
    public function __construct() {
        add_shortcode('shorturl-generate', [$this, 'generate_shortcode_handler']);
        add_shortcode('shorturl-list', [$this, 'list_shortcode_handler']);
        add_shortcode('shorturl-categories', [$this, 'display_shorturl_categories']);

        if ( ! function_exists( 'wp_terms_checklist' ) ) {
            include ABSPATH . 'wp-admin/includes/template.php';
        }        
    }

    private function display_shorturl_category() {
        $taxonomy = 'shorturl_category';
        $args = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'walker' => new Walker_Category_Checklist_Custom(),
        );
    
        ob_start();
    ?>
        <div id="shorturl-category-metabox">
            <?php wp_terms_checklist(0, $args); ?>
            <p><a href="#" id="add-new-shorturl-category">Add New Category</a></p>
            <div id="new-shorturl-category" style="display: none;">
                <input type="text" name="new_shorturl_category" placeholder="New Category Name">
                <input type="button" value="Add Category" id="add-shorturl-category-btn">
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#add-new-shorturl-category').on('click', function(e) {
                    e.preventDefault();
                    $('#new-shorturl-category').slideToggle();
                });
    
                $('#add-shorturl-category-btn').on('click', function(e) {
                    e.preventDefault();
                    var categoryName = $('input[name=new_shorturl_category]').val();
                    if (categoryName) {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'add_shorturl_category',
                                categoryName: categoryName,
                                taxonomy: '<?php echo $taxonomy; ?>',
                                _ajax_nonce: '<?php echo wp_create_nonce('add-shorturl-category'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Reload the page or update the category list dynamically
                                    alert('Category added successfully!');
                                } else {
                                    alert('Failed to add category. Please try again.');
                                }
                            }
                        });
                    } else {
                        alert('Please enter a category name.');
                    }
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    private function display_shorturl_tag() {
        $taxonomy = 'shorturl_tag';
        $tags = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
    
        ob_start();
    ?>
        <div id="shorturl-tag-metabox">
            <ul class="tagchecklist">
                <?php foreach ($tags as $tag) : ?>
                    <li>
                        <label>
                            <input type="checkbox" name="shorturl_tag[]" value="<?php echo esc_attr($tag->term_id); ?>">
                            <?php echo esc_html($tag->name); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p><a href="#" id="add-new-shorturl-tag">Add New Tag</a></p>
            <div id="new-shorturl-tag" style="display: none;">
                <input type="text" name="new_shorturl_tag" placeholder="New Tag Name">
                <input type="button" value="Add Tag" id="add-shorturl-tag-btn">
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#add-new-shorturl-tag').on('click', function(e) {
                    e.preventDefault();
                    $('#new-shorturl-tag').slideToggle();
                });
    
                $('#add-shorturl-tag-btn').on('click', function(e) {
                    e.preventDefault();
                    var tagName = $('input[name=new_shorturl_tag]').val();
                    if (tagName) {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'add_shorturl_tag',
                                tagName: tagName,
                                taxonomy: '<?php echo $taxonomy; ?>',
                                _ajax_nonce: '<?php echo wp_create_nonce('add-shorturl-tag'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Reload the page or update the tag list dynamically
                                    alert('Tag added successfully!');
                                } else {
                                    alert('Failed to add tag. Please try again.');
                                }
                            }
                        });
                    } else {
                        alert('Please enter a tag name.');
                    }
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }
    
            
    public function generate_shortcode_handler($atts = null): string {
        // If $atts is null or not an array, initialize it as an empty array
        if (!is_array($atts)) {
            $atts = [];
        }
    
        // Extract shortcode attributes
        $atts = shortcode_atts([
            'url' => (empty($_POST['url']) ? '' : filter_var($_POST['url'], FILTER_VALIDATE_URL)),
            'uri' => (empty($_POST['uri']) ? '' : sanitize_text_field($_POST['uri'])),
            'valid_until' => (empty($_POST['valid_until']) ? '' : sanitize_text_field($_POST['valid_until'])),
            'categories' => (empty($_POST['categories']) ? '' : sanitize_text_field($_POST['categories'])),
            'tags' => (empty($_POST['tags']) ? '' : sanitize_text_field($_POST['tags'])),
        ], $atts);
    
        $result_message = ''; // Initialize result message
        $qr_code_src = ''; // Initialize QR code source
    
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Check if URL is provided
            if (!empty($_POST['url'])) {
                // Call ShortURL::shorten() and add the result if URL is given
                $result = ShortURL::shorten($atts);
                $result_message = ($result['error'] ? 'Error: ' : 'Short URL: ') . $result['txt'];    
            }
        }
    
        // Generate form
        ob_start(); // Start output buffering
        ?>
        <form method="post">
            <div class="postbox">
                <h2 class="hndle">Create Short URL</h2>
                <div class="inside">
                    <label for="url">Long URL:</label>
                    <input type="text" name="url" value="<?php echo esc_attr($atts['url']); ?>">
                </div>
            </div>
    
            <p><a href="#" id="show-advanced-settings">Advanced Settings</a></p>
            <div id="div-advanced-settings" style="display: none;">
                <h2 class="handle">Categories</h2>
                <?php echo $this->display_shorturl_category(); ?>
                <h2 class="handle">Tags</h2>
                <?php echo $this->display_shorturl_tag(); ?>
            </div>
    
            <input type="submit" name="generate" value="Generate">
        </form>
    
        <!-- Display result message -->
        <p><?php echo $result_message; ?>
        <?php if (!$result['error']) : ?>
                <button class="copy-to-clipboard" data-clipboard-text="<?php echo esc_attr($result['txt']); ?>">Copy</button>
            <?php endif; ?>
    
    
    </p>
    
<!-- Display QR code if available -->
<?php if (!empty($result['txt'])) : ?>
    <canvas id="qr"></canvas>
            <script>
            jQuery(document).ready(function($) {
                // Generate QR code using QRious
                var qr = new QRious({
                    element: document.getElementById('qr'),
                    value: '<?php echo $result['txt']; ?>',
                    size: 200 // Adjust size as per your requirement
                });
            });
        </script>
    <?php endif; ?>    
        <?php
        $form = ob_get_clean(); // Get and clean the buffer
    
        return $form;
    }
            
    

    public function list_shortcode_handler($atts = null): string {

        if (!is_array($atts)) {
            $atts = [];
        }
    
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';

        // Determine the column to sort by and sort order
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'id';
        $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
        
        // Prepare SQL query to fetch post IDs from wp_postmeta and their associated category names
        $query = "SELECT l.id AS link_id, 
                         pm.meta_value AS post_id, 
                         l.long_url, 
                         l.short_url, 
                         l.uri, 
                         l.valid_until, 
                         GROUP_CONCAT(pm_category.meta_value) AS category_ids 
                  FROM $table_name l
                  INNER JOIN {$wpdb->prefix}postmeta AS pm ON l.id = pm.meta_value AND pm.meta_key = 'shorturl_id'
                  LEFT JOIN {$wpdb->prefix}postmeta AS pm_category ON pm_category.post_id = pm.meta_value AND pm_category.meta_key = 'category_id'
                  GROUP BY l.id, pm.meta_value, l.long_url, l.short_url, l.uri, l.valid_until
                  ORDER BY $orderby $order";
        
        // return $query;

        $results = $wpdb->get_results($query, ARRAY_A);
    
        // Generate table
        $table = '<table class="wp-list-table widefat striped">';
        $table .= '<thead><tr>';
        $table .= '<th scope="col" class="manage-column column-id"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=id&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">ID</a></th>';
        $table .= '<th scope="col" class="manage-column column-post-id">Post ID</th>';
        $table .= '<th scope="col" class="manage-column column-long-url"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=long_url&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">Long URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-short-url"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=short_url&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">Short URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-uri">URI</th>';
        $table .= '<th scope="col" class="manage-column column-valid-until">Valid Until</th>';
        $table .= '<th scope="col" class="manage-column column-categories">Categories</th>';
        $table .= '<th scope="col" class="manage-column column-tags">Tags</th>';
        $table .= '</tr></thead><tbody>';
    
        foreach ($results as $row) {
            // Unserialize category IDs if they exist
            $category_ids = !empty($row['category_ids']) ? unserialize($row['category_ids']) : ['nix'];
        
            // Fetch and concatenate category names only if category IDs exist
            if (!empty($category_ids)) {
                // Fetch category names based on IDs
                $category_names = [];
                foreach ($category_ids as $category_id) {
                    $category = get_term($category_id, 'shorturl_category');
                    if ($category && !is_wp_error($category)) {
                        $category_names[] = $category->name;
                    }else{
                        $category_names[] = '$category_id = ' . $category_id;
                    }
                }
        
                // Concatenate category names
                $category_names_str = implode(', ', $category_names);
            } else {
                // Set empty string if no category IDs exist
                $category_names_str = '';
            }
        
            // Output table row
            $table .= '<tr>';
            $table .= '<td class="column-id">' . $row['link_id'] . '</td>';
            $table .= '<td class="column-post-id">' . $row['post_id'] . '</td>';
            $table .= '<td class="column-long-url">' . $row['long_url'] . '</td>';
            $table .= '<td class="column-short-url">' . $row['short_url'] . '</td>';
            $table .= '<td class="column-uri">' . $row['uri'] . '</td>';
            $table .= '<td class="column-valid-until">' . $row['valid_until'] . '</td>';
            $table .= '<td class="column-categories">' . $category_names_str . '</td>';
            $table .= '<td class="column-tags"></td>'; // You can populate this column similarly for tags
            $table .= '</tr>';
        }
                $table .= '</tbody></table>';
    
        return $table;
    }
        

    public static function update_category_label() {
          // Verify nonce
    if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'update_category_label_nonce' ) ) {
        wp_send_json_error( 'Nonce verification failed.' );
    }

        global $wpdb;
    
        error_log('jawoll ja');
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
    

    public function display_shorturl_categories($atts) {
        global $wpdb;
    
        // Shortcode attributes
        $atts = shortcode_atts(array(
            'checkbox' => false, // Default checkbox value
        ), $atts);
    
        $message = ''; // Initialize message variable
    
        // Check if form submitted to add new category
        if (isset($_POST['new_category'])) {
            $new_label = sanitize_text_field($_POST['new_label']);
            $parent_id = isset($_POST['parent_category']) ? intval($_POST['parent_category']) : null; // Retrieve parent category ID
            // Check if the label is not empty
            if (!empty($new_label)) {
                // Check if the label already exists
                $existing_label = $wpdb->get_var($wpdb->prepare("SELECT label FROM {$wpdb->prefix}shorturl_categories WHERE label = %s", $new_label));
                if (!$existing_label) {
                    // Insert new category label into the database
                    $insert_query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}shorturl_categories (label, parent_id) VALUES (%s, %d)", $new_label, $parent_id);
                    if ($wpdb->query($insert_query)) {
                        $message = "New category added";
                    } else {
                        $message = "Error: Could not insert category.";
                    }
                } else {
                    $message = "Error: Category label must be unique.";
                }
            }
        }    
    
        // Retrieve categories from the database
        $categories = $wpdb->get_results("SELECT id, label, parent_id FROM {$wpdb->prefix}shorturl_categories ORDER BY label", ARRAY_A);
    
        // Start rendering the table
        $output = '<table class="wp-list-table widefat striped">';
        $output .= '<thead><tr><th>ID</th><th>Category Label</th></tr></thead><tbody>';
    
        foreach ($categories as $category) {
            $output .= '<tr class="shorturl-category-row">';
            $output .= '<td>' . $category['id'] . '</td>';
            $output .= '<td>';
            // Display hierarchical indentation based on category_id
            $output .= str_repeat('-', $category['parent_id'] * 4);
            // Display checkbox if attribute is set
            if ($atts['checkbox']) {
                $output .= '<input type="checkbox" name="category_ids[]" value="' . $category['id'] . '">&nbsp;';
            }
            // Display label
            $output .= '<span class="shorturl-category-span" data-id="' . $category['id'] . '">';
            $output .= '<span class="shorturl-category-label">' . $category['label'] . '</span>';            
            $output .= '<br>';
            $output .= '<a href="#" class="shorturl-edit-category hidden" data-id="' . $category['id'] . '">Edit</a>&nbsp;&nbsp;';
            $output .= '<a href="#" class="shorturl-delete-category hidden" data-id="' . $category['id'] . '">Delete</a>';
            $output .= '</span>';
            $output .= '</td>';
            $output .= '</tr>';
        }
                    
        $output .= '</tbody></table>';
    
        // Start rendering the form for adding new category
        $output .= '<p>' . $message . '</p>';
        $output .= '<form method="post">';
        $output .= '<label for="new_label">New Category Label:</label>';
        $output .= '<input type="text" name="new_label" id="new_label" placeholder="Enter new category label">';
    
        // Dropdown menu for selecting parent category
        $output .= '<label for="parent_category">Parent Category:</label>';
        $output .= '<select name="parent_category" id="parent_category">';
        $output .= '<option value="">None</option>'; // Option for no parent category
        foreach ($categories as $category) {
            $output .= '<option value="' . $category['id'] . '">' . $category['label'] . '</option>';
        }
        $output .= '</select>';
    
        // Submit button for adding new category
        $output .= '<input type="submit" name="new_category" value="Add New Category">';
        $output .= '</form>';
    
        return $output;
    }
    
                                        
}

