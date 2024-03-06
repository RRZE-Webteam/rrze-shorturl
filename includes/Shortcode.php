<?php

namespace RRZE\ShortURL;

use RRZE\ShortURL\Walker_Category_Checklist_Custom;

class Shortcode
{
    public function __construct()
    {
        add_shortcode('shorturl', [$this, 'shorturl_handler']);
        add_shortcode('shorturl-list', [$this, 'list_shortcode_handler']);
        add_shortcode('custom_tag_shortcode', [$this, 'render_custom_tag_shortcode']);

        add_action('wp_ajax_nopriv_store_link_category', [$this, 'store_link_category_callback']);
        add_action('wp_ajax_store_link_category', [$this, 'store_link_category_callback']);

        add_action('wp_ajax_nopriv_add_shorturl_category', [$this, 'add_shorturl_category_callback']);
        add_action('wp_ajax_add_shorturl_category', [$this, 'add_shorturl_category_callback']);

        add_action('wp_ajax_nopriv_update_category_label_action', [$this, 'update_category_label']);
        add_action('wp_ajax_update_category_label_action', [$this, 'update_category_label']);


        wp_localize_script('rrze-shorturl', 'custom_tag_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));



    }


    public function shorturl_handler($atts = null): string
    {

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
        $form = '';
        $form .= '<form method="post">';
        $form .= '<div class="postbox">';
        $form .= '<h2 class="hndle">Create Short URL</h2>';
        $form .= '<div class="inside">';
        $form .= '<label for="url">Long URL:</label>';
        $form .= '<input type="text" name="url" value="' . esc_attr($atts['url']) . '">';
        $form .= '</div>';
        $form .= '</div>';

        $form .= '<p><a href="#" id="show-advanced-settings">Advanced Settings</a></p>';
        $form .= '<div id="div-advanced-settings" style="display: none;">';
        $form .= '<h2 class="handle">Categories</h2>';
        $form .= self::display_shorturl_category();
        $form .= '<h2 class="handle">Tags</h2>';
        // $form .= $this->display_shorturl_tag();
        $form .= '</div>';

        $form .= '<input type="submit" name="generate" value="Generate">';
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

    public function render_custom_tag_shortcode()
    {
        ob_start();
        ?>
        <div class="tagsdiv" id="custom-tagdiv">
            <div class="jaxtag">
                <div class="ajaxtag hide-if-no-js">
                    <label class="screen-reader-text" for="new-tag">Add New Tag</label>
                    <div class="taghint">Add New Tag</div>
                    <input type="text" id="new-tag" name="newtag" class="newtag form-input-tip" size="16" autocomplete="off">
                    <input type="button" class="button tagadd" value="Add">
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }


    public function fetch_custom_tags()
    {
        global $wpdb;
        $tags = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}shorturl_tags");
        return $tags;
    }

    public function display_custom_tags()
    {
        $tags = $this->fetch_custom_tags();
        $ret = '';
        if ($tags) {
            $ret .= '<div class="custom-tags">';
            foreach ($tags as $tag) {
                $ret .= '<button class="tag-button" data-tag-id="' . $tag->id . '">' . $tag->name . '</button>';
            }
            $ret .= '</div>';
        }
        return $ret;
    }


    public static function display_shorturl_category()
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
            <?php self::display_hierarchical_categories($hierarchicalCategories); ?>
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
        <script>
            jQuery(document).ready(function ($) {
                $('#add-new-shorturl-category').on('click', function (e) {
                    e.preventDefault();
                    $('#new-shorturl-category').slideToggle();
                });

                $('#add-shorturl-category-btn').on('click', function (e) {
                    e.preventDefault();
                    var categoryName = $('input[name=new_shorturl_category]').val();
                    if (categoryName) {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'add_shorturl_category',
                                categoryName: categoryName,
                                parentCategory: $('select[name=parent_category]').val(),                                
                                _ajax_nonce: '<?php echo wp_create_nonce('add-shorturl-category'); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    // Replace the existing category list with the updated HTML
                                    $('#shorturl-category-metabox').html(response.data.category_list_html);
                                    // Check the checkbox for the newly added category
                                    var newCategoryId = response.data.category_id;
                                    $('input[name="shorturl_categories[]"][value="' + newCategoryId + '"]').prop('checked', true);

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
    private static function display_hierarchical_categories($categories, $level = 0)
    {
        foreach ($categories as $category) {
            echo str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level); // Indent based on level
            echo '<input type="checkbox" name="shorturl_categories[]" value="' . esc_attr($category->id) . '" />';
            echo esc_html($category->label) . '<br>';
            if (!empty($category->children)) {
                self::display_hierarchical_categories($category->children, $level + 1);
            }
        }
    }

    private function display_shorturl_tag()
    {
        $taxonomy = 'shorturl_tag';
        $tags = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            )
        );

        ob_start();
        ?>
        <div id="shorturl-tag-metabox">
            <ul class="tagchecklist">
                <?php foreach ($tags as $tag): ?>
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
            jQuery(document).ready(function ($) {
                $('#add-new-shorturl-tag').on('click', function (e) {
                    e.preventDefault();
                    $('#new-shorturl-tag').slideToggle();
                });

                $('#add-shorturl-tag-btn').on('click', function (e) {
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
                            success: function (response) {
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






    public function list_shortcode_handler($atts = null): string
    {

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
                    } else {
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


    public static function update_category_label()
    {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'update_category_label_nonce')) {
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
        check_ajax_referer('add-shorturl-category', '_ajax_nonce');
    
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


}

