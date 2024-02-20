<?php

namespace RRZE\ShortURL;

class Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_options_page']);
        add_action('admin_init', [$this, 'register_settings']);
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

    // Register settings sections and fields
    public function register_settings()
    {
        // Services tab settings
        add_settings_section(
            'rrze_shorturl_services_section',
            '&nbsp;',
            [$this, 'render_services_section'],
            'rrze_shorturl_services'
        );

        // add_settings_field(
        //     'rrze_shorturl_services_field',
        //     'Services Field',
        //     [$this, 'render_services_field'],
        //     'rrze_shorturl_services',
        //     'rrze_shorturl_services_section'
        // );

        register_setting('rrze_shorturl_services', 'rrze_shorturl_services');

        // Customer Domains tab settings
        add_settings_section(
            'rrze_shorturl_customer_domains_section',
            'Customer Domains Settings',
            [$this, 'render_customer_domains_section'],
            'rrze_shorturl_customer_domains'
        );

        add_settings_field(
            'rrze_shorturl_customer_domains_field',
            'Customer Domains Field',
            [$this, 'render_customer_domains_field'],
            'rrze_shorturl_customer_domains',
            'rrze_shorturl_customer_domains_section'
        );

        register_setting('rrze_shorturl_customer_domains', 'rrze_shorturl_customer_domains');

        // Short URLs tab settings
        add_settings_section(
            'rrze_shorturl_short_urls_section',
            'Short URLs Settings',
            [$this, 'render_short_urls_section'],
            'rrze_shorturl_short_urls'
        );

        add_settings_field(
            'rrze_shorturl_short_urls_field',
            'Short URLs Field',
            [$this, 'render_short_urls_field'],
            'rrze_shorturl_short_urls',
            'rrze_shorturl_short_urls_section'
        );

        register_setting('rrze_shorturl_short_urls', 'rrze_shorturl_short_urls');
    }

    // Render the options page
    public function render_options_page()
    {
        $_GET['tab'] = (empty($_GET['tab']) ? 'services' : $_GET['tab']);

        ?>
        <div class="wrap">
            <h1>RRZE ShortURL Settings</h1>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=rrze-shorturl&tab=services"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'services' ? 'nav-tab-active' : ''; ?>">Services</a>
                <a href="?page=rrze-shorturl&tab=customer-domains"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customer-domains' ? 'nav-tab-active' : ''; ?>">Customer
                    Domains</a>
                <a href="?page=rrze-shorturl&tab=short-urls"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'short-urls' ? 'nav-tab-active' : ''; ?>">Short
                    URLs</a>
            </h2>

            <div class="tab-content">
                <?php
                $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'services';
                switch ($current_tab) {
                    case 'services':
                        settings_fields('rrze_shorturl_services');
                        do_settings_sections('rrze_shorturl_services');
                        break;
                    case 'customer-domains':
                        settings_fields('rrze_shorturl_customer_domains');
                        do_settings_sections('rrze_shorturl_customer_domains');
                        break;
                    case 'short-urls':
                        settings_fields('rrze_shorturl_short_urls');
                        do_settings_sections('rrze_shorturl_short_urls');
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
        if (!preg_match('/^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])$/', $hostname)) {
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


    // Render the Services tab section
    public function render_services_section()
    {
        global $wpdb;
        $message = '';

        // Check if form is submitted
        if (isset($_POST['submit'])) {
            try {
                // Delete selected entries
                if (!empty($_POST['delete'])) {
                    foreach ($_POST['delete'] as $delete_id) {
                        $wpdb->delete("{$wpdb->prefix}shorturl_domains", array('id' => $delete_id), array('%d'));
                    }
                    $message = __('Selected entries deleted successfully.', 'rrze-shorturl');
                }

                // Add new entry
                if (!empty($_POST['new_type_code'])) {
                    try {
                        // Sanitize input data
                        $new_type_code = sanitize_text_field($_POST['new_type_code']);
                        $new_hostname = sanitize_text_field($_POST['new_hostname']);
                        $new_prefix = sanitize_text_field($_POST['new_prefix']);

                        // Validate hostname
                        if (!self::isValidHostName($new_hostname)) {
                            $message = __('Hostname is not valid.', 'rrze-shorturl');
                        } else {

                            $wpdb->insert(
                                "{$wpdb->prefix}shorturl_domains",
                                array(
                                    'type_code' => $new_type_code,
                                    'hostname' => $new_hostname,
                                    'prefix' => $new_prefix
                                )
                            );

                            if ($wpdb->last_error) {
                                $message = __('An error occurred: ', 'rrze-shorturl') . $wpdb->last_error;
                                throw new \Exception($wpdb->last_error);
                            }

                            $new_type_code = $new_hostname = $new_prefix = '';


                            $message = __('New service added successfully.', 'rrze-shorturl');
                        }
                    } catch (\Exception $e) {
                        // Handle \Exceptions
                        $message = __('An error occurred: ', 'rrze-shorturl') . $e->getMessage();
                    }
                }
                // Display success message
            } catch (\Exception $e) {
                // Handle \Exceptions
                $message = __('An error occurred: ', 'rrze-shorturl') . $e->getMessage();
            }
        }

        // Fetch entries from shorturl_domains table
        $entries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_domains ORDER BY type_code");

        ?>
        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php _e('Type Code', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php _e('Hostname', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php _e('Prefix', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php _e('Delete', 'rrze-shorturl'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><input type="text" name="type_code[]" value="<?php echo esc_attr($entry->type_code); ?>"
                                        readonly /></td>
                                <td><input type="text" name="hostname[]" value="<?php echo esc_attr($entry->hostname); ?>"
                                        readonly /></td>
                                <td><input type="text" name="prefix[]" value="<?php echo esc_attr($entry->prefix); ?>" readonly />
                                </td>
                                <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr($entry->id); ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="new_type_code"
                                    value="<?php echo (!empty($new_type_code) ? $new_type_code : ''); ?>" /></td>
                            <td><input type="text" name="new_hostname"
                                    value="<?php echo (!empty($new_hostname) ? $new_hostname : ''); ?>" /></td>
                            <td><input type="text" name="new_prefix"
                                    value="<?php echo (!empty($new_prefix) ? $new_prefix : ''); ?>" pattern="\d*" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" name="submit" class="button button-primary">
                    <?php _e('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php
    }


    // Render the Services tab field
    public function render_services_field()
    {
        echo '<input type="text" name="rrze_shorturl_services" value="' . esc_attr(get_option('rrze_shorturl_services')) . '" />';
    }

    // Render the Customer Domains tab section
    public function render_customer_domains_section()
    {
        global $wpdb;
        $message = '';

        // Check if form is submitted
        if (isset($_POST['submit'])) {
            try {
                // Handle form submission
                // Validate and process form data
                // Add new entry or update existing entry

                // Delete selected entries
                if (!empty($_POST['delete'])) {
                    foreach ($_POST['delete'] as $delete_id) {
                        $wpdb->delete("{$wpdb->prefix}shorturl_domains", array('id' => $delete_id), array('%d'));
                    }
                    $message = __('Selected entries deleted successfully.', 'rrze-shorturl');
                }

                // Add new entry
                if (!empty($_POST['new_hostname'])) {
                    try {
                        // Sanitize input data
                        $hostname = sanitize_text_field($_POST['new_hostname']);
                        $type_code = 'customerdomain';
                        $prefix = 1;

                        // Validate hostname
                        if (!filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                            throw new \Exception(__('Invalid hostname.', 'rrze-shorturl'));
                        }

                        // Check if prefix is unique
                        $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shorturl_domains WHERE prefix = %d", $prefix));
                        if ($existing_entry) {
                            throw new \Exception(__('Prefix is already in use. Choose a different one.', 'rrze-shorturl'));
                        }

                        // Insert new entry into the database
                        $wpdb->insert("{$wpdb->prefix}shorturl_domains", array(
                            'type_code' => $type_code,
                            'hostname' => $hostname,
                            'prefix' => $prefix
                        )
                        );

                        if ($wpdb->last_error) {
                            throw new \Exception($wpdb->last_error);
                        }

                        $message = __('New customer domain added successfully.', 'rrze-shorturl');
                    } catch (\Exception $e) {
                        // Handle exceptions
                        $message = __('An error occurred: ', 'rrze-shorturl') . $e->getMessage();
                    }
                }
                // Display success message
            } catch (\Exception $e) {
                // Handle exceptions
                $message = __('An error occurred: ', 'rrze-shorturl') . $e->getMessage();
            }
        }

        // Fetch entries from shorturl_domains table where prefix is 1
        $entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shorturl_domains WHERE prefix = %d ORDER BY hostname", 1));

        ?>
        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php _e('Hostname', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php _e('Delete', 'rrze-shorturl'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><input type="text" name="hostname[]" value="<?php echo esc_attr($entry->hostname); ?>"
                                        readonly /></td>
                                <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr($entry->id); ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="new_hostname" value="" /></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" name="submit" class="button button-primary">
                    <?php _e('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php

    }

    // Render the Customer Domains tab field
    public function render_customer_domains_field()
    {
        echo '<input type="text" name="rrze_shorturl_customer_domains" value="' . esc_attr(get_option('rrze_shorturl_customer_domains')) . '" />';
    }

    // Render the Short URLs tab section
    public function render_short_urls_section()
    {
        echo '<p>Short URLs tab settings</p>';
    }

    // Render the Short URLs tab field
    public function render_short_urls_field()
    {
        echo '<input type="text" name="rrze_shorturl_short_urls" value="' . esc_attr(get_option('rrze_shorturl_short_urls')) . '" />';
    }
}

