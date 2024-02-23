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

        register_setting('rrze_shorturl_services', 'rrze_shorturl_services');

        // Customer Domains tab settings
        add_settings_section(
            'rrze_shorturl_customer_domains_section',
            '&nbsp;',
            [$this, 'render_customer_domains_section'],
            'rrze_shorturl_customer_domains'
        );

        register_setting('rrze_shorturl_customer_domains', 'rrze_shorturl_customer_domains');

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
        $bDel = false;

        // Check if form is submitted
        if (isset($_POST['submit'])) {
            try {
                // Delete selected entries
                if (!empty($_POST['delete'])) {

                    foreach ($_POST['delete'] as $id => $delete_id) {
                        if ($_POST['prefix'][$id] == '1') {
                            $message = __('You cannot delete the entry used for our customers.', 'rrze-shorturl');
                        } else {
                            $wpdb->delete("{$wpdb->prefix}shorturl_domains", array('id' => $delete_id), array('%d'));
                            $bDel = true;
                        }
                    }
                    $message = (empty($message) ? '' : $message . '<br \>') . ($bDel ? __('Selected entries deleted successfully.', 'rrze-shorturl') : '');
                }

                // Add new entry
                if (!empty($_POST['new_hostname'])) {
                    try {
                        // Sanitize input data
                        $new_hostname = sanitize_text_field($_POST['new_hostname']);
                        $new_prefix = sanitize_text_field($_POST['new_prefix']);

                        // Validate hostname
                        if (!self::isValidHostName($new_hostname)) {
                            $message = __('Hostname is not valid.', 'rrze-shorturl');
                        } else {
                            if (empty($new_prefix)) {
                                // this is a customer domain
                                $message = __('You are trying to enter a customer domain. Please use tab "Customer Domains" to do this.', 'rrze-shorturl');
                            } else {
                                $wpdb->insert(
                                    "{$wpdb->prefix}shorturl_domains",
                                    array(
                                        'hostname' => $new_hostname,
                                        'prefix' => $new_prefix
                                    )
                                );

                                $message = __('New service added successfully.', 'rrze-shorturl');

                                if ($wpdb->last_error) {
                                    $message = __('An error occurred: ', 'rrze-shorturl') . $wpdb->last_error;
                                    throw new \Exception($wpdb->last_error);
                                }


                            }
                            $new_hostname = '';
                            $new_prefix = 0;


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

        // Fetch entries from shorturl_domains table (prefix = 1 is reserved for our customer domains)
        $entries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_domains WHERE NOT prefix = 1 ORDER BY prefix");

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
                                <td><input type="text" name="hostname[]" value="<?php echo esc_attr($entry->hostname); ?>"
                                        readonly /></td>
                                <td><input type="text" name="prefix[]" value="<?php echo esc_attr($entry->prefix); ?>" readonly />
                                </td>
                                <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr($entry->id); ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
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

                        // Validate hostname
                        if (!filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                            throw new \Exception(__('Invalid hostname.', 'rrze-shorturl'));
                        }

                        // Insert new entry into the database
                        $wpdb->insert(
                            "{$wpdb->prefix}shorturl_domains",
                            array(
                                'hostname' => $hostname
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
        $entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shorturl_domains WHERE prefix = %d AND NOT hostname = 'reserved for our customers' ORDER BY hostname", 1));

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
}

