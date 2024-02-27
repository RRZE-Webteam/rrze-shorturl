<?php

namespace RRZE\ShortURL;

class Shortcode {
    public function __construct() {
        add_shortcode('shorturl-generate', [$this, 'generate_shortcode_handler']);
        add_shortcode('shorturl-list', [$this, 'list_shortcode_handler']);
        add_shortcode('shorturl-tmp', [$this, 'custom_category_checklist']);

        if ( ! function_exists( 'wp_terms_checklist' ) ) {
            include ABSPATH . 'wp-admin/includes/template.php';
        }        
    }

    public function custom_category_checklist($post_id = 0, $descendants_and_self = 0, $selected_cats = false, $popular_cats = false, $walker = null) {
        global $post_ID;
    
        if (empty($post_id)) {
            $post_id = $post_ID;
        }
    
        $tax_name = 'shorturl_category';
        $taxonomy = get_taxonomy($tax_name);
        $disabled = !current_user_can($taxonomy->cap->assign_terms) ? 'disabled="disabled"' : '';
    
        // Start the output buffer
        ob_start();
    
        ?>
        <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
            <ul id="<?php echo $tax_name; ?>-tabs" class="category-tabs">
                <li class="tabs"><?php echo $taxonomy->labels->name; ?></li>
            </ul>
    
            <div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
                <ul id="<?php echo $tax_name; ?>checklist" class="list:<?php echo $tax_name; ?> categorychecklist form-no-clear">
                    <?php wp_terms_checklist($post_id, [
                        'taxonomy' => $tax_name,
                        'descendants_and_self' => $descendants_and_self,
                        'selected_cats' => $selected_cats,
                        'popular_cats' => $popular_cats,
                        'walker' => $walker,
                    ]); ?>
                </ul>
    
                <!-- Add new category -->
                <div id="<?php echo $tax_name; ?>-adder" class="wp-hidden-children">
                    <h4>
                        <a id="<?php echo $tax_name; ?>-add-toggle" href="#"><?php echo $taxonomy->labels->add_new_item; ?></a>
                    </h4>
                    <p id="<?php echo $tax_name; ?>-add" class="category-add wp-hidden-child" style="display: none;">
                        <label class="screen-reader-text" for="new<?php echo $tax_name; ?>"><?php echo $taxonomy->labels->add_new_item; ?></label>
                        <input type="text" name="new<?php echo $tax_name; ?>" id="new<?php echo $tax_name; ?>" class="form-required form-input-tip" value="New <?php echo $taxonomy->labels->singular_name; ?>" aria-required="true">
                        <?php wp_dropdown_categories([
                            'taxonomy' => $tax_name,
                            'hide_empty' => 0,
                            'hide_if_empty' => false,
                            'orderby' => 'name',
                            'hierarchical' => true,
                            'show_option_none' => $taxonomy->labels->parent_item,
                            'name' => 'new' . $tax_name . '_parent',
                            'orderby' => 'name',
                        ]); ?>
                        <input type="button" id="<?php echo $tax_name; ?>-add-submit" data-wp-lists="add:<?php echo $tax_name; ?>-add" class="button <?php echo $tax_name; ?>-add-submit" value="<?php echo esc_attr( $taxonomy->labels->add_new_item ); ?>">
                        <?php wp_nonce_field( 'add-' . $tax_name, '_ajax_nonce-add-' . $tax_name, false ); ?>
                        <span id="<?php echo $tax_name; ?>-ajax-response"></span>
                    </p>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo $tax_name; ?>-add-toggle').on('click', function(e) {
                e.preventDefault();
                $('#<?php echo $tax_name; ?>-add').toggleClass('wp-hidden-child').slideToggle();
            });
        });
        </script>
        <?php
    
        // Get the buffered content and clean the buffer
        $output = ob_get_clean();
    
        return $output;
    }
            
            

    public function generate_shortcode_handler($atts = null): string {
        // If $atts is null or not an array, initialize it as an empty array
        if (!is_array($atts)) {
            $atts = [];
        }
        
        // Extract shortcode attributes
        $atts = shortcode_atts([
            'url' => '',
            // 'uri' => '',
            // 'valid_until' => '',
            // 'categories' => '',
            // 'tags' => ''
        ], $atts);
    
        // Check if form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Check if URL is provided
            if (!empty($_POST['url'])) {
                // Call ShortURL::shorten() and add the result if URL is given
                $shortened_url = ShortURL::shorten($_POST['url']);
                $atts['url'] = $_POST['url']; // Set the URL in $atts for form population
                $result_message = '<p>Shortened URL: ' . $shortened_url . '</p>';
            } else {
                $result_message = '<p>Error: Please provide a URL.</p>';
            }
        } else {
            $result_message = ''; // Initialize result message
        }
    
        // Generate form
        ob_start(); // Start output buffering
        ?>
        <form method="post">
            <div class="postbox">
                <h2 class="hndle">Categories</h2>
                <div class="inside">
                    <?php
                    // Display categories using custom taxonomy 'shorturl_category'
                    wp_terms_checklist(0, [
                        'taxonomy' => 'shorturl_category',
                        'selected_cats' => $atts['categories'], // Selected categories
                    ]);
                    ?>
                </div>
            </div>
    
            <?php
            // Other attributes
            foreach ($atts as $key => $value) {
                ?>
                <div class="postbox">
                    <h2 class="hndle"><?php echo ucfirst($key); ?></h2>
                    <div class="inside">
                        <label for="<?php echo $key; ?>"><?php echo ucfirst($key); ?>:</label>
                        <input type="text" name="<?php echo $key; ?>" value="<?php echo $value; ?>"><br>
                    </div>
                </div>
                <?php
            }
            ?>
    
            <!-- Add input for adding a new category -->
            <label for="new_category">New Category:</label>
            <input type="text" name="new_category"><br>
            <input type="submit" name="generate" value="Generate">
        </form>
        <?php
        $form = ob_get_clean(); // Get and clean the buffer
    
        // Display result message
        $form .= $result_message;
    
        return $form;
    }
        
    

    public function list_shortcode_handler(array $atts = []): string {
        global $wpdb;
        $table_name = $wpdb->prefix . 'shorturl_links';
    
        // Determine the column to sort by and sort order
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'id';
        $order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
    
        // Prepare SQL query
        $query = "SELECT * FROM $table_name ORDER BY $orderby $order";
        $results = $wpdb->get_results($query, ARRAY_A);
    
        // Generate table
        $table = '<table class="wp-list-table widefat striped">';
        $table .= '<thead><tr>';
        $table .= '<th scope="col" class="manage-column column-id"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=id&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">ID</a></th>';
        $table .= '<th scope="col" class="manage-column column-long-url"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=long_url&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">Long URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-short-url"><a href="' . admin_url('admin.php?page=your_page_slug&orderby=short_url&order=' . ($order == 'ASC' ? 'DESC' : 'ASC')) . '">Short URL</a></th>';
        $table .= '<th scope="col" class="manage-column column-uri">URI</th>';
        $table .= '<th scope="col" class="manage-column column-valid-until">Valid Until</th>';
        $table .= '<th scope="col" class="manage-column column-categories">Categories</th>';
        $table .= '<th scope="col" class="manage-column column-tags">Tags</th>';
        $table .= '</tr></thead><tbody>';
    
        foreach ($results as $row) {
            $table .= '<tr>';
            foreach ($row as $key => $value) {
                $table .= '<td class="column-' . $key . '">' . $value . '</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';
    
        return $table;
    }
}

