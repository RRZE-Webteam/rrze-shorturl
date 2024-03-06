<?php

namespace RRZE\ShortURL;

use RRZE\ShortURL\Walker_Category_Checklist_Custom;

class Shortcode
{
    public function __construct()
    {
        add_shortcode('shorturl', [$this, 'generate_shortcode_handler']);
        add_shortcode('shorturl-list', [$this, 'list_shortcode_handler']);
        add_shortcode('shorturl-categories', [$this, 'display_shorturl_categories']);
        add_shortcode('shorturl-test', [$this, 'display_custom_tags']);
        add_shortcode('custom_tag_shortcode', [$this, 'render_custom_tag_shortcode']);




        if (!function_exists('wp_terms_checklist')) {
            include ABSPATH . 'wp-admin/includes/template.php';
        }

        add_action('wp_ajax_nopriv_update_category_label_action', [$this, 'update_category_label']);
        add_action('wp_ajax_update_category_label_action', [$this, 'update_category_label']);


        wp_localize_script('rrze-shorturl', 'custom_tag_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));



    }




    public function render_shorturl_block_shortcode() {
        ob_start(); // Start output buffering

        // Output the form created in edit.js
        ?>
        <div id="rrze-shorturl-form"></div>
        <script>
            jQuery(document).ready(function ($) {
                const { __ } = wp.i18n;
                const { useState, useEffect } = wp.element;
                const { PanelBody, DateTimePicker, TextControl, Button, FormTokenField } = wp.components;
                const { InspectorControls, useBlockProps } = wp.blockEditor;
                const Edit = ({ attributes, setAttributes }) => {
    const { valid_until: defaultValidUntil } = attributes;

    const [url, setUrl] = useState('');
    const [shortenedUrl, setShortenedUrl] = useState('');
    const [selfExplanatoryUri, setSelfExplanatoryUri] = useState('');
    const [validUntil, setValidUntil] = useState(defaultValidUntil);
    const [selectedCategories, setSelectedCategories] = useState([]);
    const [selectedTags, setSelectedTags] = useState([]);
    const [errorMessage, setErrorMessage] = useState('');
    const [qrCodeUrl, setQrCodeUrl] = useState('');
    const [categoriesOptions, setCategoriesOptions] = useState([]);
    const [tagSuggestions, setTagSuggestions] = useState([]);
    const [copied, setCopied] = useState(false);
    let clipboard; // Declare clipboard variable

    const onChangeValidUntil = newDate => {
        setValidUntil(newDate);
        setAttributes({ valid_until: newDate });
    };

    useEffect(() => {
        if (!validUntil) {
            const now = new Date();
            const nextYear = new Date(now.getFullYear() + 1, now.getMonth(), now.getDate());
            setValidUntil(nextYear);
            setAttributes({ valid_until: nextYear });
        }

        // Initialize clipboard instance
        clipboard = new ClipboardJS('.btn', {
            text: function() {
                return shortenedUrl;
            }
        });

        // Define success and error handlers
        clipboard.on('success', function(e) {
            setCopied(true);
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            console.error('Copy failed:', e.action);
        });

        // Clean up function to remove event listeners when component unmounts
        return () => {
            if (clipboard) {
                clipboard.destroy();
            }
        };

        // Fetch categories from shorturl_categories table
        fetch('/wp-json/short-url/v1/categories')
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    const categoriesOptions = data.map(term => ({
                        label: term.label,
                        value: term.id,
                        parent: term.parent_id || 0
                    }));
                    setCategoriesOptions(categoriesOptions);
                } else {
                    console.log('No categories found.');
                    setCategoriesOptions([]);
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_category terms:', error);
            });

        // Fetch tags from shorturl_tags table
        fetch('/wp-json/short-url/v1/tags')
            .then(response => response.json())
            .then(data => {
                console.log('ShortURL Tags Data:', data);
                if (Array.isArray(data)) {
                    const tagSuggestions = data.map(tag => ({ id: tag.id, value: tag.label }));
                    setTagSuggestions(tagSuggestions);
                } else {
                    console.log('No tags found.');
                    setTagSuggestions([]);
                }
            })
            .catch(error => {
                console.error('Error fetching shorturl_tags:', error);
            });

    }, [shortenedUrl]);

    const handleAddCategory = () => {
        const newCategoryLabel = prompt('Enter the label of the new category:');
        if (!newCategoryLabel) return;

        // Make a POST request to add the new category
        fetch('/wp-json/short-url/v1/add-category', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ label: newCategoryLabel })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to add category');
                }
                return response.json();
            })
            .then(newCategory => {
                const updatedCategories = [...categoriesOptions, { label: newCategory.label, value: newCategory.id }];
                setCategoriesOptions(updatedCategories);
            })
            .catch(error => {
                console.error('Error adding category:', error);
            });
    };

    const shortenUrl = () => {
        let isValid = true;

        if (selfExplanatoryUri.trim() !== '') {
            const uriWithoutSpaces = selfExplanatoryUri.replace(/\s/g, '');

            if (encodeURIComponent(selfExplanatoryUri) !== encodeURIComponent(uriWithoutSpaces)) {
                setErrorMessage('Error: Self-Explanatory URI is not valid');
                isValid = false;
            }
        }

        if (isValid) {
            const allTagIds = selectedTags.map(tag => tag.id);

            const newTags = selectedTags.filter(tag => !tagSuggestions.some(suggestion => suggestion.value === tag.value));

            if (newTags.length > 0) {
                Promise.all(newTags.map(newTag => {
                    return fetch('/wp-json/short-url/v1/add-tag', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ label: newTag.value })
                    }).then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to add tag');
                        }
                        return response.json();
                    }).then(newTag => newTag.id);
                })).then(newTagIds => {
                    const combinedTagIds = [...allTagIds, ...newTagIds];
                    continueShorteningUrl(combinedTagIds);
                }).catch(error => {
                    console.error('Error adding tag:', error);
                    setErrorMessage('Error: Failed to add tag');
                });
            } else {
                continueShorteningUrl(allTagIds);
            }
        }
    };

    const continueShorteningUrl = (tags) => {
        const shortenParams = {
            url: url.trim(),
            uri: selfExplanatoryUri,
            valid_until: validUntil,
            categories: selectedCategories,
            tags: tags
        };

        fetch('/wp-json/short-url/v1/shorten', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(shortenParams)
        })
            .then(response => response.json())
            .then(shortenData => {
                if (!shortenData.error) {
                    setShortenedUrl(shortenData.txt);
                    setErrorMessage('');
                    generateQRCode(shortenData.txt);
                } else {
                    setErrorMessage('Error: ' + shortenData.txt);
                    setShortenedUrl('');
                }
            })
            .catch(error => console.error('Error:', error));
    };

    const generateQRCode = (text) => {
        const qr = new QRious({
            element: document.getElementById('qrcode'),
            value: text,
            size: 150
        });
        setQrCodeUrl(qr.toDataURL());
    }

    const handleCopy = () => {
        console.log('handleCopy clicked');
        // Trigger copy action
        if (shortenedUrl) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortenedUrl)
                    .then(() => {
                        setCopied(true);
                    })
                    .catch(err => {
                        console.error('Copy failed:', err);
                    });
            } else {
                // Fallback method for browsers that do not support Clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = shortenedUrl;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    setCopied(true);
                } catch (err) {
                    console.error('Copy failed:', err);
                }
                document.body.removeChild(textArea);
            }
        }
    };
        
    return (
        <div {...useBlockProps()}>
            <InspectorControls>
                <PanelBody title={__('Self-Explanatory URI')}>
                    <TextControl
                        value={selfExplanatoryUri}
                        onChange={setSelfExplanatoryUri}
                    />
                </PanelBody>
                <PanelBody title={__('Validity')}>
                    <DateTimePicker
                        currentDate={validUntil}
                        onChange={onChangeValidUntil}
                        is12Hour={false}
                        minDate={new Date()}
                        maxDate={new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate())}
                        isInvalidDate={(date) => {
                            const nextYear = new Date(new Date().getFullYear() + 1, new Date().getMonth(), new Date().getDate());
                            return date > nextYear;
                        }}
                    />
                </PanelBody>
                <PanelBody title={__('Categories')}>
                    {categoriesOptions.map(category => (
                        <div key={category.value}>
                            <input
                                type="checkbox"
                                value={category.value}
                                checked={selectedCategories.includes(category.value)}
                                onChange={(event) => {
                                    const isChecked = event.target.checked;
                                    if (isChecked) {
                                        setSelectedCategories([...selectedCategories, category.value]);
                                    } else {
                                        setSelectedCategories(selectedCategories.filter(cat => cat !== category.value));
                                    }
                                }}
                            />
                            {' '}
                            <label>{category.label}</label>
                            <br />
                        </div>
                    ))}
                    <a href="#" onClick={handleAddCategory}>Add New Category</a>
                </PanelBody>
                <PanelBody title={__('Tags')}>
                    <FormTokenField
                        label="Tags"
                        value={selectedTags.map(tag => tag.value)} // Extracting values from selectedTags
                        suggestions={tagSuggestions.map(tag => tag.value)} // Extracting values from tagSuggestions
                        onChange={(newTags) => {
                            const updatedTags = newTags.map(tagValue => ({
                                id: tagSuggestions.find(suggestion => suggestion.value === tagValue)?.id,
                                value: tagValue
                            }));
                            setSelectedTags(updatedTags);
                        }}
                    />
                </PanelBody>
            </InspectorControls>

            <TextControl
                label={__('Enter URL')}
                value={url}
                onChange={setUrl}
            />
            <Button isPrimary onClick={shortenUrl}>
                {__('Shorten URL')}
            </Button>

            {errorMessage && (
                <p style={{ color: 'red' }}>
                    {errorMessage}
                </p>
            )}

            {/* Display shortened URL and copy button */}
            {shortenedUrl && (
                <div>
                    <p>
                        {__('Shortened URL')}: {shortenedUrl} 
                        &nbsp;&nbsp;<button class="btn" data-clipboard-target="#foo">
                        <img
                src="data:image/svg+xml,%3Csvg height='1024' width='896' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M128 768h256v64H128v-64z m320-384H128v64h320v-64z m128 192V448L384 640l192 192V704h320V576H576z m-288-64H128v64h160v-64zM128 704h160v-64H128v64z m576 64h64v128c-1 18-7 33-19 45s-27 18-45 19H64c-35 0-64-29-64-64V192c0-35 29-64 64-64h192C256 57 313 0 384 0s128 57 128 128h192c35 0 64 29 64 64v320h-64V320H64v576h640V768zM128 256h512c0-35-29-64-64-64h-64c-35 0-64-29-64-64s-29-64-64-64-64 29-64 64-29 64-64 64h-64c-35 0-64 29-64 64z'/%3E%3C/svg%3E"
                width="13"
                alt="Copy to clipboard"
                onClick={handleCopy} // Attach onClick event handler
                style={{ cursor: 'pointer' }} // Add cursor style to indicate it's clickable
            />
            </button> {copied && <span>URL copied!</span>}
                    </p>
                    <img src={qrCodeUrl} alt="QR Code" />                                        
                </div>
            )}
        </div>
    );
};

export default Edit;
 
                // Your edit.js code goes here...
            });
        </script>
        <?php
    
        $output = ob_get_clean(); // Get and clean the buffer
    
        return $output;
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


    public function display_shorturl_category()
    {

        // return 'test';

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
                                taxonomy: '<?php echo $taxonomy; ?>',
                                _ajax_nonce: '<?php echo wp_create_nonce('add-shorturl-category'); ?>'
                            },
                            success: function (response) {
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


    public function generate_shortcode_handler($atts = null): string
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
        // $form .= $this->display_shorturl_category();
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


    public function display_shorturl_categories($atts)
    {
        global $wpdb;

        // Shortcode attributes
        $atts = shortcode_atts(
            array(
                'checkbox' => false, // Default checkbox value
            ),
            $atts
        );

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

