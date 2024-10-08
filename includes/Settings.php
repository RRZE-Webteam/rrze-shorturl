<?php

namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);

        add_action('wp_ajax_nopriv_update_idm', [$this, 'update_idm_callback']);
        add_action('wp_ajax_update_idm', [$this, 'update_idm_callback']);
    }

    // Add a menu item to the Settings menu
    public function add_options_page()
    {
        add_options_page(
            'RRZE ShortURL',
            'RRZE ShortURL',
            'manage_options',
            'rrze-shorturl',
            [$this, 'render_options_page']
        );
    }

    public function update_idm_callback()
    {
        // Check if AJAX request to update allow_uri or allow_get
        check_ajax_referer('update_shorturl_idm_nonce', '_ajax_nonce');

        global $wpdb;

        // Validate and sanitize input
        if (!isset($_POST['id']) || !isset($_POST['field'])) {
            wp_send_json_error('Invalid request');
            return;
        }

        $id = intval($_POST['id']);
        $allowed_fields = ['allow_uri', 'allow_get'];  // List of valid fields
        $field = sanitize_text_field(wp_unslash($_POST['field']));

        if (!in_array($field, $allowed_fields)) {
            wp_send_json_error('Invalid field');
            return;
        }

        $value = isset($_POST['value']) && $_POST['value'] === 'true' ? 1 : 0;

        // Update the allow_uri or allow_get field
        $wpdb->update(
            $wpdb->prefix . 'shorturl_idms',
            array($field => $value),
            array('id' => $id),
            array('%d'),
            array('%d')
        );

        // Return success response
        wp_send_json_success();
    }



    // Register settings sections and fields
    public function register_settings()
    {
        // General tab settings
        add_settings_section(
            'rrze_shorturl_general_section',
            '&nbsp;',
            [$this, 'render_general_section'],
            'rrze_shorturl_general'
        );

        register_setting('rrze_shorturl_general', 'rrze_shorturl_general');

        // Services tab settings
        add_settings_section(
            'rrze_shorturl_services_section',
            '&nbsp;',
            [$this, 'render_services_section'],
            'rrze_shorturl_services'
        );

        register_setting('rrze_shorturl_services', 'rrze_shorturl_services');

        // Customer Domains tab settings
        add_settings_section(
            'rrze_shorturl_customer_domains_section',
            '&nbsp;',
            [$this, 'render_customer_domains_section'],
            'rrze_shorturl_customer_domains'
        );

        register_setting('rrze_shorturl_customer_domains', 'rrze_shorturl_customer_domains');

        // External Domains tab settings
        add_settings_section(
            'rrze_shorturl_external_domains_section',
            '&nbsp;',
            [$this, 'render_external_domains_section'],
            'rrze_shorturl_external_domains'
        );

        register_setting('rrze_shorturl_external_domains', 'rrze_shorturl_external_domains');

        // IdM tab settings
        add_settings_section(
            'rrze_shorturl_idm_section',
            '&nbsp;',
            [$this, 'render_idm_section'],
            'rrze_shorturl_idm'
        );

        register_setting('rrze_shorturl_statistic', 'rrze_shorturl_statistic');
        // Statistic tab settings
        add_settings_section(
            'rrze_shorturl_statistic_section',
            '&nbsp;',
            [$this, 'render_statistic_section'],
            'rrze_shorturl_statistic'
        );

        register_setting('rrze_shorturl_statistic', 'rrze_shorturl_statistic');

    }



    // Render the options page
    public function render_options_page()
    {
        // Sanitize and validate the 'tab' parameter
        $allowed_tabs = ['general', 'services', 'customer-domains', 'external-domains', 'idm', 'statistic'];
        $current_tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowed_tabs) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('RRZE ShortURL Settings', 'rrze-shorturl'); ?></h1>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=rrze-shorturl&tab=general"
                    class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('General', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=services"
                    class="nav-tab <?php echo $current_tab === 'services' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Services', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=customer-domains"
                    class="nav-tab <?php echo $current_tab === 'customer-domains' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Customers Domains', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=external-domains"
                    class="nav-tab <?php echo $current_tab === 'external-domains' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('External Domains', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=idm"
                    class="nav-tab <?php echo $current_tab === 'idm' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('IdM', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=statistic"
                    class="nav-tab <?php echo $current_tab === 'statistic' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Statistic', 'rrze-shorturl'); ?></a>
            </h2>

            <div class="tab-content">
                <?php
                switch ($current_tab) {
                    case 'general':
                        settings_fields('rrze_shorturl_general');
                        do_settings_sections('rrze_shorturl_general');
                        break;
                    case 'services':
                        settings_fields('rrze_shorturl_services');
                        do_settings_sections('rrze_shorturl_services');
                        break;
                    case 'customer-domains':
                        settings_fields('rrze_shorturl_customer_domains');
                        do_settings_sections('rrze_shorturl_customer_domains');
                        break;
                    case 'external-domains':
                        settings_fields('rrze_shorturl_external_domains');
                        do_settings_sections('rrze_shorturl_external_domains');
                        break;
                    case 'idm':
                        settings_fields('rrze_shorturl_idm');
                        do_settings_sections('rrze_shorturl_idm');
                        break;
                    case 'statistic':
                        settings_fields('rrze_shorturl_statistic');
                        do_settings_sections('rrze_shorturl_statistic');
                        break;
                    default:
                        settings_fields('rrze_shorturl_services');
                        do_settings_sections('rrze_shorturl_services');
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function isValidHostName($hostname)
    {
        // Check if the hostname is not empty
        if (empty($hostname)) {
            return false;
        }

        // Check if the hostname length is within limits
        if (strlen($hostname) > 255) {
            return false;
        }

        // Check if the hostname matches the allowed pattern
        if (!preg_match('/^([a-zA-Z0-9]+(-[a-zA-Z0-9]+)*\.)+[a-zA-Z]{2,}$/', $hostname)) {
            return false;
        }

        // Check if the hostname contains at least one dot
        if (strpos($hostname, '.') === false) {
            return false;
        }

        // Split the hostname into parts and check each part
        $parts = explode('.', $hostname);
        foreach ($parts as $part) {
            // Each part must be between 1 and 63 characters long
            if (strlen($part) < 1 || strlen($part) > 63) {
                return false;
            }
            // Each part must consist of letters, digits, and hyphens only
            if (!preg_match('/^[a-zA-Z0-9\-]+$/', $part)) {
                return false;
            }
            // The first and last characters of each part must be letters or digits
            if (!ctype_alnum($part[0]) || !ctype_alnum($part[strlen($part) - 1])) {
                return false;
            }
        }

        // Passed all checks, hostname is valid
        return true;
    }


    // Render the General tab section
    public function render_general_section()
    {
        $message = '';

        $aOptions = json_decode(get_option('rrze-shorturl'), true);
        $aOptions = (empty($aOptions) ? [] : $aOptions);
        $aOptions['ShortURLBase'] = (empty($aOptions['ShortURLBase']) ? 'https://go.fau.de' : $aOptions['ShortURLBase']);
        $aOptions['maxShortening'] = (empty($aOptions['maxShortening']) ? 60 : $aOptions['maxShortening']);
        $aOptions['allowed_ip_addresses'] = (empty($aOptions['allowed_ip_addresses']) ? '' : $aOptions['allowed_ip_addresses']);

        // Process form submission
        if (isset($_POST['submit_general'])) {
            // Unslash all the inputs
            $post_data = wp_unslash($_POST);

            // Validate and sanitize ShortURLBase
            if (isset($post_data['ShortURLBase']) && filter_var($post_data['ShortURLBase'], FILTER_VALIDATE_URL)) {
                $aOptions['ShortURLBase'] = esc_url_raw($post_data['ShortURLBase']);
            } else {
                $message = 'Error: ' . __('Basis is not valid.', 'rrze-shorturl');
            }

            // Validate maxShortening (ensure it's a positive integer)
            if (isset($post_data['maxShortening']) && filter_var($post_data['maxShortening'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) !== false) {
                $aOptions['maxShortening'] = (int) $post_data['maxShortening']; // Cast to integer
            } else {
                $message = 'Error: ' . __('Maximum number of shortenings is not valid.', 'rrze-shorturl');
            }

            // Sanitize allowed_ip_addresses (assuming it is a comma-separated list of IPs)
            if (isset($post_data['allowed_ip_addresses'])) {
                $aOptions['allowed_ip_addresses'] = sanitize_textarea_field($post_data['allowed_ip_addresses']);
            } else {
                $message = 'Error: ' . __('Allowed IP Addresses for REST API endpoints is not valid.', 'rrze-shorturl');
            }

            // Save the options using wp_json_encode()
            update_option('rrze-shorturl', wp_json_encode($aOptions));
        }

        ?>

        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo esc_html($message); ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="" id="general-form">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><?php echo esc_html__('Basis', 'rrze-shorturl'); ?></td>
                            <td><input type="text" name="ShortURLBase" id="ShortURLBase" placeholder="https://go.fau.de"
                                    value="<?php echo esc_attr($aOptions['ShortURLBase']); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html__('Maximum shortenings per hour per user', 'rrze-shorturl'); ?></td>
                            <td><input type="number" name="maxShortening" id="maxShortening" min="1"
                                    value="<?php echo esc_attr($aOptions['maxShortening']); ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html__('Allowed IP Addresses for REST API endpoints', 'rrze-shorturl'); ?></td>
                            <td>
                                <textarea name="allowed_ip_addresses" id="allowed_ip_addresses" rows="4"
                                    cols="50"><?php echo esc_textarea($aOptions['allowed_ip_addresses']); ?></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" name="submit_general" class="button button-primary">
                    <?php echo esc_html__('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php
    }


    // Render the Services tab section
    public function render_services_section()
    {
        $error = true;
        $message = '';
        $bDel = false;

        // Check if form is submitted
        if (isset($_POST['submit'])) {
            // Verify the nonce field
            check_admin_referer('rrze_shorturl_services_nonce');

            try {
                // Unslash the $_POST data
                $post_data = wp_unslash($_POST);

                // Delete selected entries
                if (!empty($post_data['delete'])) {
                    foreach ($post_data['delete'] as $delete_id) {
                        wp_delete_post(intval($delete_id), true);
                        $bDel = true;
                    }
                    $message = ($bDel ? __('Selected entries deleted successfully.', 'rrze-shorturl') : '');
                    $error = false;
                }

                // Add new entry
                if (!empty($post_data['new_hostname'])) {
                    try {
                        // Sanitize input data
                        $new_hostname = sanitize_text_field($post_data['new_hostname']);
                        $new_prefix = sanitize_text_field($post_data['new_prefix']);
                        $new_regex = sanitize_text_field($post_data['new_regex']);

                        // Validate hostname
                        if (!self::isValidHostName($new_hostname)) {
                            $message = __('Hostname is not valid.', 'rrze-shorturl');
                        } else {
                            if (empty($new_prefix) || $new_prefix == '1') {
                                $message = __('Prefix 1 is reserved for customer domains. Please use another prefix.', 'rrze-shorturl');
                            } elseif ($new_prefix == '0') {
                                $message = __('Prefix not allowed.', 'rrze-shorturl');
                            } else {
                                // Check if the prefix already exists
                                $existing_services = new \WP_Query(array(
                                    'post_type' => 'shorturl_service',
                                    'meta_query' => array(
                                        array(
                                            'key' => 'prefix',
                                            'value' => $new_prefix,
                                            'compare' => '='
                                        )
                                    )
                                ));

                                if ($existing_services->found_posts > 0) {
                                    $message = __('Prefix is already in use.', 'rrze-shorturl');
                                } else {
                                    // Create new post
                                    $new_service_id = wp_insert_post(array(
                                        'post_type' => 'shorturl_service',
                                        'post_title' => $new_hostname,
                                        'post_status' => 'publish',
                                        'meta_input' => array(
                                            'prefix' => $new_prefix,
                                            'regex' => $new_regex
                                        )
                                    ));

                                    if (is_wp_error($new_service_id)) {
                                        $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($new_service_id->get_error_message());
                                    } else {
                                        $message = __('New service added successfully.', 'rrze-shorturl');
                                        $error = false;
                                    }

                                    $new_hostname = '';
                                    $new_prefix = '';
                                    $new_regex = '';
                                }
                            }
                        }
                    } catch (CustomException $e) {
                        $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($e->getMessage());
                    }
                }
            } catch (CustomException $e) {
                $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($e->getMessage());
            }
        }

        // Fetch entries from shorturl_services (prefix = 1 is reserved for customer domains)
        $args = array(
            'post_type' => 'shorturl_service',
            'meta_query' => array(
                array(
                    'key' => 'prefix',
                    'value' => '1',
                    'compare' => '!='
                )
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'prefix',
            'order' => 'ASC'
        );

        $entries = new \WP_Query($args);
        ?>
        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo esc_attr($error ? 'error' : 'updated'); ?>">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="services-form">
                <?php wp_nonce_field('rrze_shorturl_services_nonce'); ?>

                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Hostname', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Prefix', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Regex', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Delete', 'rrze-shorturl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($entries->have_posts()): ?>
                            <?php while ($entries->have_posts()):
                                $entries->the_post(); ?>
                                <tr>
                                    <td><input type="text" name="hostname[]" value="<?php echo esc_attr(get_the_title()); ?>"
                                            readonly /></td>
                                    <td><input type="text" name="prefix[]"
                                            value="<?php echo esc_attr(get_post_meta(get_the_ID(), 'prefix', true)); ?>" readonly />
                                    </td>
                                    <td><input type="text" name="regex[]"
                                            value="<?php echo esc_attr(get_post_meta(get_the_ID(), 'regex', true)); ?>" /></td>
                                    <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr(get_the_ID()); ?>" /></td>
                                </tr>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        <?php endif; ?>
                        <tr>
                            <td><input type="text" name="new_hostname"
                                    value="<?php echo !empty($new_hostname) ? esc_attr($new_hostname) : ''; ?>" /></td>
                            <td><input type="text" name="new_prefix"
                                    value="<?php echo !empty($new_prefix) ? esc_attr($new_prefix) : ''; ?>" pattern="\d*" />
                            </td>
                            <td><input type="text" name="new_regex"
                                    value="<?php echo !empty($new_regex) ? esc_attr($new_regex) : ''; ?>" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" name="submit" class="button button-primary">
                    <?php echo esc_html__('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php
    }


    // Render the Customer Domains tab section
    public function render_customer_domains_section()
    {
        // Define arguments for WP_Query to fetch 'domain' CPT entries with specific conditions
        $args = [
            'post_type' => 'shorturl_domain',
            'posts_per_page' => -1, // Fetch all domains
            'meta_query' => [
                [
                    'key' => 'prefix',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'key' => 'external',
                    'value' => '0',
                    'compare' => '='
                ]
            ],
            'orderby' => 'title', // Sort by the hostname (post_title)
            'order' => 'ASC'
        ];


        // Execute the query
        $query = new \WP_Query($args);

        ?>
        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo esc_html($message); ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="" id="customer-domains-form">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Hostname', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Active', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Notice', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Webmaster Name', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Webmaster eMail', 'rrze-shorturl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->have_posts()): ?>
                            <?php while ($query->have_posts()):
                                $query->the_post(); ?>
                                <tr>
                                    <td><?php echo esc_html(get_the_title()); ?></td>
                                    <td><?php echo get_post_meta(get_the_ID(), 'active', true) == 1 ? '&#10004;' : '&#10008;'; ?></td>
                                    <td><?php echo esc_html(get_post_meta(get_the_ID(), 'notice', true)); ?></td>
                                    <td><?php echo esc_html(get_post_meta(get_the_ID(), 'webmaster_name', true)); ?></td>
                                    <td><?php echo esc_html(get_post_meta(get_the_ID(), 'webmaster_email', true)); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5"><?php echo esc_html__('No customer domains found', 'rrze-shorturl'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php

        // Restore original Post Data
        wp_reset_postdata();
    }




    // Render the External Domains tab section
    public function render_external_domains_section()
    {
        $message = '';
        $bDel = false;

        // Check if the form is submitted
        if (isset($_POST['submit'])) {
            // Verify the nonce field
            check_admin_referer('rrze_shorturl_external_domains_nonce');

            try {
                // Unslash $_POST data
                $post_data = wp_unslash($_POST);

                // Delete selected entries
                if (!empty($post_data['delete'])) {
                    foreach ($post_data['delete'] as $id => $delete_id) {
                        wp_delete_post(intval($delete_id), true); // True for force delete (bypassing the trash)
                        $bDel = true;
                    }
                    $message = ($bDel ? __('Selected entries deleted successfully.', 'rrze-shorturl') : '');
                }

                // Add new entry
                if (!empty($post_data['new_hostname'])) {
                    try {
                        // Sanitize input data
                        $new_hostname = sanitize_text_field($post_data['new_hostname']);

                        // Validate hostname
                        if (!self::isValidHostName($new_hostname)) {
                            $message = __('Hostname is not valid.', 'rrze-shorturl');
                        } else {
                            // Insert new domain as a Custom Post Type
                            $post_data = [
                                'post_title' => $new_hostname,
                                'post_type' => 'shorturl_domain',
                                'post_status' => 'publish'
                            ];

                            $post_id = wp_insert_post($post_data);

                            if (!is_wp_error($post_id)) {
                                // Add meta data for the domain
                                update_post_meta($post_id, 'active', 1);
                                update_post_meta($post_id, 'prefix', 1);
                                update_post_meta($post_id, 'external', 1);
                                $message = __('New external domain added successfully.', 'rrze-shorturl');
                                $new_hostname = '';
                            } else {
                                $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($post_id->get_error_message());
                            }
                        }
                    } catch (CustomException $e) {
                        $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($e->getMessage());
                    }
                }
            } catch (CustomException $e) {
                $message = __('An error occurred: ', 'rrze-shorturl') . esc_html($e->getMessage());
            }
        }

        // Fetch entries from the 'domain' Custom Post Type where prefix = 1 and external = 1
        $args = [
            'post_type' => 'shorturl_domain',
            'posts_per_page' => -1, // Fetch all domains
            'meta_query' => [
                [
                    'key' => 'prefix',
                    'value' => '1',
                    'compare' => '='
                ],
                [
                    'key' => 'external',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'orderby' => 'title', // Sort by the hostname (post_title)
            'order' => 'ASC'
        ];

        $query = new \WP_Query($args);

        ?>
        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo esc_attr(strpos($message, 'error') !== false ? 'error' : 'updated'); ?>">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="external-domains-form">
                <?php wp_nonce_field('rrze_shorturl_external_domains_nonce'); ?>

                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Hostname', 'rrze-shorturl'); ?></th>
                            <th><?php echo esc_html__('Delete', 'rrze-shorturl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($query->have_posts()): ?>
                            <?php while ($query->have_posts()):
                                $query->the_post(); ?>
                                <tr>
                                    <td><input type="text" name="hostname[]" value="<?php echo esc_attr(get_the_title()); ?>"
                                            readonly /></td>
                                    <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr(get_the_ID()); ?>" /></td>
                                </tr>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2"><?php echo esc_html__('No external domains found', 'rrze-shorturl'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td><input type="text" name="new_hostname"
                                    value="<?php echo esc_attr(!empty($new_hostname) ? $new_hostname : ''); ?>" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" name="submit" class="button button-primary">
                    <?php echo esc_html__('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php

        // Reset Post Data
        wp_reset_postdata();
    }


    public function render_idm_section()
    {
        // Determine the current sorting order and column
        $orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'idm';
        $order = isset($_GET['order']) && in_array(sanitize_text_field(wp_unslash($_GET['order'])), ['asc', 'desc']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'asc';

        try {
            // Check if form is submitted
            if (isset($_POST['submit_idm'])) {
                check_admin_referer('rrze_shorturl_idm_nonce'); // Add nonce check

                if (isset($_POST['idm'])) {
                    $idm = sanitize_text_field(wp_unslash($_POST['idm']));

                    if (!empty($idm)) {
                        // Check if the IdM already exists
                        $existing_idm_id = get_posts(
                            array(
                                'post_type' => 'shorturl_idm',
                                'title' => $idm,
                                'post_status' => 'all',
                                'numberposts' => 1,
                                'fields' => 'ids'
                            )
                        );

                        if (!empty($existing_idm_id)) {
                            $message = __('An error occurred: this IdM already exists.', 'rrze-shorturl');
                        } else {
                            // Add new entry as a Custom Post Type
                            $post_data = [
                                'post_title' => $idm,
                                'post_type' => 'shorturl_idm',
                                'post_status' => 'publish'
                            ];

                            $insert_result = wp_insert_post($post_data);

                            if (is_wp_error($insert_result)) {
                                $message = __('An error occurred while adding the IdM.', 'rrze-shorturl');
                            } else {
                                // Add default meta data
                                update_post_meta($insert_result, 'allow_uri', 0);
                                update_post_meta($insert_result, 'allow_get', 0);
                                update_post_meta($insert_result, 'allow_utm', 0);
                                $message = __('New IdM has been added.', 'rrze-shorturl');
                            }
                        }
                    }
                }
            }

            // Prepare the WP_Query to fetch IdM entries sorted by title
            $args = [
                'post_type' => 'shorturl_idm',
                'posts_per_page' => -1, // Fetch all IdMs
                'orderby' => $orderby === 'idm' ? 'title' : 'meta_value',
                'meta_key' => $orderby === 'idm' ? '' : $orderby, // Sort by meta if not 'idm'
                'order' => $order
            ];

            $idms_query = new \WP_Query($args);

            ?>
            <div class="wrap">
                <?php if (!empty($message)): ?>
                    <div class="<?php echo esc_attr(strpos($message, 'error') !== false ? 'error' : 'updated'); ?>">
                        <p><?php echo esc_html($message); ?></p>
                    </div>
                <?php endif; ?>
                <form method="post">
                    <?php wp_nonce_field('rrze_shorturl_idm_nonce'); // Nonce field for form ?>
                    <table class="shorturl-wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="manage-column column-hostname <?php echo esc_attr($orderby === 'idm' ? 'sorted' : 'sortable'); ?> <?php echo esc_attr($order); ?>"
                                    data-sort="<?php echo esc_attr($orderby === 'idm' ? $order : 'asc'); ?>">
                                    <a
                                        href="<?php echo esc_url(admin_url('admin.php?page=rrze-shorturl&tab=idm&orderby=idm&order=' . ($orderby === 'idm' && $order === 'asc' ? 'desc' : 'asc'))); ?>">
                                        <span><?php echo esc_html__('IdM', 'rrze-shorturl'); ?></span>
                                        <span class="sorting-indicators">
                                            <span class="sorting-indicator asc" aria-hidden="true"></span>
                                            <span class="sorting-indicator desc" aria-hidden="true"></span>
                                        </span>
                                    </a>
                                </th>
                                <th scope="col"><?php echo esc_html__('Allow URI', 'rrze-shorturl'); ?></th>
                                <th scope="col"><?php echo esc_html__('Allow GET', 'rrze-shorturl'); ?></th>
                                <th scope="col"><?php echo esc_html__('Allow UTM', 'rrze-shorturl'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($idms_query->have_posts()) {
                                while ($idms_query->have_posts()) {
                                    $idms_query->the_post();
                                    $allow_uri = get_post_meta(get_the_ID(), 'allow_uri', true);
                                    $allow_get = get_post_meta(get_the_ID(), 'allow_get', true);
                                    $allow_utm = get_post_meta(get_the_ID(), 'allow_utm', true);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html(get_the_title()); ?></td>
                                        <td><input type="checkbox" class="allow-uri-checkbox"
                                                data-id="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $allow_uri ? 'checked' : ''; ?>>
                                        </td>
                                        <td><input type="checkbox" class="allow-get-checkbox"
                                                data-id="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $allow_get ? 'checked' : ''; ?>>
                                        </td>
                                        <td><input type="checkbox" class="allow-utm-checkbox"
                                                data-id="<?php echo esc_attr(get_the_ID()); ?>" <?php echo $allow_utm ? 'checked' : ''; ?>>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                            <tr>
                                <td colspan="4"><input type="text" name="idm" id="idm" value=""></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="submit" name="submit_idm"
                        class="button button-primary"><?php echo esc_html__('Save Changes', 'rrze-shorturl'); ?></button>
                </form>
            </div>
            <?php

            // Restore original Post Data
            wp_reset_postdata();

        } catch (CustomException $e) {
            echo '<div class="error notice"><p>' . esc_html($e->getMessage()) . '</p></div>';
            error_log("Error in render_idm_section: " . esc_html($e->getMessage()));
        }
    }

}

