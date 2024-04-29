<?php

namespace RRZE\ShortURL;

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

        $id = intval($_POST['id']);
        $field = sanitize_text_field($_POST['field']);
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
        $_GET['tab'] = (empty($_GET['tab']) ? 'services' : $_GET['tab']);

        ?>
        <div class="wrap">
            <h1>RRZE ShortURL Settings</h1>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=rrze-shorturl&tab=general"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'general' ? 'nav-tab-active' : ''; ?>"><?php echo __('General', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=services"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'services' ? 'nav-tab-active' : ''; ?>"><?php echo __('Services', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=customer-domains"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'customer-domains' ? 'nav-tab-active' : ''; ?>"><?php echo __('Customers Domains', 'rrze-shorturl'); ?></a>
                <a href="?page=rrze-shorturl&tab=idm"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'idm' ? 'nav-tab-active' : ''; ?>">IdM</a>
                <a href="?page=rrze-shorturl&tab=statistic"
                    class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'statistic' ? 'nav-tab-active' : ''; ?>"><?php echo __('Statistic', 'rrze-shorturl'); ?></a>
            </h2>

            <div class="tab-content">
                <?php
                $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'services';
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


    // Render the General tab section
    public function render_general_section()
    {
        $message = '';
        $aOptions = [];
        $aOptions['ShortURLBase'] = 'https://go.fau.de';
        $aOptions['maxShortening'] = 60;

        $aOptions = json_decode(get_option('rrze-shorturl'), true);

        if (isset($_POST['submit_general'])) {
            if (filter_var($_POST['ShortURLBase'], FILTER_VALIDATE_URL)) {
                $aOptions['ShortURLBase'] = esc_url($_POST['ShortURLBase']);
            } else {
                $message = 'Error: ' . __('Basis is not valid.', 'rrze-shorturl');
            }

            if (filter_var($_POST['maxShortening'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1))) !== false) {
                // If it's a valid positive integer
                $aOptions['maxShortening'] = (int) $_POST['maxShortening']; // Cast to integer
            } else {
                $message = 'Error: ' . __('Maximum number of shortenings is not valid.', 'rrze-shorturl');
            }

            update_option('rrze-shorturl', json_encode($aOptions));
        }

        ?>

        <div class="wrap">
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post" action="" id="general-form">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><?php echo __('Basis', 'rrze-shorturl'); ?></td>
                            <td><input type="text" name="ShortURLBase" id="ShortURLBase" placeholder="https://go.fau.de"
                                    value="<?php echo $aOptions['ShortURLBase']; ?>"></td>
                        </tr>
                        <tr>
                            <td><?php echo __('Maximum shortenings per hour per user', 'rrze-shorturl'); ?></td>
                            <td><input type="number" name="maxShortening" id="maxShortening" min="1"
                                    value="<?php echo $aOptions['maxShortening']; ?>"></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" name="submit_general" class="button button-primary">
                    <?php echo __('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php
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
                        $message = __('An error occurred: ', 'rrze-shorturl') . $e->getMessage();
                    }
                }
            } catch (\Exception $e) {
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
            <form method="post" action="" id="services-form">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php echo __('Hostname', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php echo __('Prefix', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php echo __('Delete', 'rrze-shorturl'); ?>
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
                                <td><input type="checkbox" name="delete[]" value="<?php echo esc_attr($entry->id); ?>" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="new_hostname"
                                    value="<?php echo (!empty($new_hostname) ? $new_hostname : ''); ?>" /></td>
                            <td><input type="text" name="new_prefix"
                                    value="<?php echo (!empty($new_prefix) ? $new_prefix : ''); ?>" pattern="\d*" />
                            </td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" name="submit" class="button button-primary">
                    <?php echo __('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
        </div>
        <?php
    }


    // Render the Customer Domains tab section
    public function render_customer_domains_section()
    {
        global $wpdb;

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
            <form method="post" action="" id="customer-domains-form">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>
                                <?php echo __('Hostname', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php echo __('Active', 'rrze-shorturl'); ?>
                            </th>
                            <th>
                                <?php echo __('Notice', 'rrze-shorturl'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td><?php echo esc_attr($entry->hostname); ?></td>
                                <td><?php echo $entry->active == 1 ? '&#10004;' : '&#10008;'; ?></td>
                                <td><?php echo esc_attr($entry->notice); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    public function render_idm_section()
    {
        global $wpdb;

        // Determine the current sorting order and column
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'idm';
        $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';

        try {
            // Check if form is submitted
            if (isset($_POST['submit_idm'])) {
                $idm = sanitize_text_field($_POST['idm']);

                // Delete rows if delete checkbox is checked
                if (!empty($_POST['delete'])) {
                    foreach ($_POST['delete'] as $delete_id) {
                        $wpdb->delete(
                            $wpdb->prefix . 'shorturl_idms',
                            array('id' => $delete_id),
                            array('%d')
                        );
                    }
                    $message = __('Selected IdMs have been deleted.', 'rrze-shorturl');
                } elseif (!empty($idm)) { // Add new entry
                    $insert_result = $wpdb->insert(
                        $wpdb->prefix . 'shorturl_idms',
                        array('idm' => $idm, 'created_by' => 'Admin', 'allow_uri' => 0, 'allow_get' => 0),
                        array('%s', '%s', '%d', '%d')
                    );

                    if ($insert_result === false) {
                        $message = __('An error occurred: this IdM already exists.', 'rrze-shorturl');
                    } else {
                        $message = __('New IdM has been added.', 'rrze-shorturl');
                    }

                }
            }

            // Display form to add/update entries
            ?>
            <?php if (!empty($message)): ?>
                <div class="<?php echo strpos($message, 'error') !== false ? 'error' : 'updated'; ?>">
                    <p>
                        <?php echo $message; ?>
                    </p>
                </div>
            <?php endif; ?>
            <form method="post">
                <table class="shorturl-wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"
                                class="manage-column column-hostname <?php echo $orderby === 'idm' ? 'sorted' : 'sortable'; ?> <?php echo $order; ?>"
                                data-sort="<?php echo $orderby === 'idm' ? $order : 'asc'; ?>">
                                <a
                                    href="<?php echo admin_url('admin.php?page=rrze-shorturl&tab=idm&orderby=idm&order=' . ($orderby === 'idm' && $order === 'asc' ? 'desc' : 'asc')); ?>">
                                    <span>IdM</span>
                                    <span class="sorting-indicators"><span class="sorting-indicator asc"
                                            aria-hidden="true"></span><span class="sorting-indicator desc"
                                            aria-hidden="true"></span></span>
                                </a>
                            </th>
                            <th scope="col"><?php echo __('Allow URI', 'rrze-shorturl'); ?></th>
                            <th scope="col"><?php echo __('Allow GET', 'rrze-shorturl'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $idms = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "shorturl_idms ORDER BY " . $orderby . ' ' . $order, ARRAY_A);

                        if ($idms) {
                            foreach ($idms as $idm) {
                                ?>
                                <tr>
                                    <td><?php echo $idm["idm"]; ?></td>
                                    <td><input type="checkbox" class="allow-uri-checkbox" data-id="<?php echo $idm["id"]; ?>" <?php echo $idm["allow_uri"] ? 'checked' : ''; ?>></td>
                                    <td><input type="checkbox" class="allow-get-checkbox" data-id="<?php echo $idm["id"]; ?>" <?php echo $idm["allow_get"] ? 'checked' : ''; ?>></td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        <tr>
                            <td colspan="3"><input type="text" name="idm" id="idm" value=""></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" name="submit_idm" class="button button-primary">
                    <?php echo __('Save Changes', 'rrze-shorturl'); ?>
                </button>
            </form>
            <?php
        } catch (\Exception $e) {
            echo '<div class="error notice"><p>' . $e->getMessage() . '</p></div>';
            error_log("Error in render_idm_section: " . $e->getMessage());
        }
    }

    public function render_statistic_section()
    {
        global $wpdb;

        // Determine the current sorting order and column
        $orderby = isset($_GET['orderby']) ? $_GET['orderby'] : 'hostname';
        $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';

        // Fetch link counts grouped by domain_id and hostname with sorting
        $link_counts = $wpdb->get_results("
        SELECT sd.hostname, COUNT(sl.id) AS link_count
        FROM {$wpdb->prefix}shorturl_links AS sl
        LEFT JOIN {$wpdb->prefix}shorturl_domains AS sd ON sl.domain_id = sd.id
        GROUP BY sl.domain_id, sd.hostname
        ORDER BY $orderby $order
    ");

        // Output the statistics table
        ?>
        <div class="wrap">
            <table class="shorturl-wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th scope="col"
                            class="manage-column column-hostname <?php echo $orderby === 'hostname' ? 'sorted' : 'sortable'; ?> <?php echo $order; ?>"
                            data-sort="<?php echo $orderby === 'hostname' ? $order : 'asc'; ?>">
                            <a
                                href="<?php echo admin_url('admin.php?page=rrze-shorturl&tab=statistic&orderby=hostname&order=' . ($orderby === 'hostname' && $order === 'asc' ? 'desc' : 'asc')); ?>">
                                <span><?php echo __('Hostname', 'rrze-shorturl'); ?></span>
                                <span class="sorting-indicators"><span class="sorting-indicator asc"
                                        aria-hidden="true"></span><span class="sorting-indicator desc"
                                        aria-hidden="true"></span></span>
                            </a>
                        </th>
                        <th scope="col"
                            class="manage-column column-count <?php echo $orderby === 'link_count' ? 'sorted' : 'sortable'; ?> <?php echo $order; ?>"
                            data-sort="<?php echo $orderby === 'link_count' ? $order : 'asc'; ?>">
                            <a
                                href="<?php echo admin_url('admin.php?page=rrze-shorturl&tab=statistic&orderby=link_count&order=' . ($orderby === 'link_count' && $order === 'asc' ? 'desc' : 'asc')); ?>">
                                <span><?php echo __('Link Count', 'rrze-shorturl'); ?></span>
                                <span class="sorting-indicators"><span class="sorting-indicator asc"
                                        aria-hidden="true"></span><span class="sorting-indicator desc"
                                        aria-hidden="true"></span></span>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($link_counts as $link_count): ?>
                        <tr>
                            <td class="hostname column-hostname">
                                <?php echo esc_html($link_count->hostname); ?>
                            </td>
                            <td class="count column-count">
                                <?php echo esc_html($link_count->link_count); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col"
                            class="manage-column column-hostname <?php echo $orderby === 'hostname' ? 'sorted' : 'sortable'; ?> <?php echo $order; ?>"
                            data-sort="<?php echo $orderby === 'hostname' ? $order : 'asc'; ?>">
                            <a
                                href="<?php echo admin_url('admin.php?page=rrze-shorturl&tab=statistic&orderby=hostname&order=' . ($orderby === 'hostname' && $order === 'asc' ? 'desc' : 'asc')); ?>">
                                <span><?php echo __('Hostname', 'rrze-shorturl'); ?></span>
                                <span class="sorting-indicators"><span class="sorting-indicator asc"
                                        aria-hidden="true"></span><span class="sorting-indicator desc"
                                        aria-hidden="true"></span></span>
                            </a>
                        </th>
                        <th scope="col"
                            class="manage-column column-count <?php echo $orderby === 'link_count' ? 'sorted' : 'sortable'; ?> <?php echo $order; ?>"
                            data-sort="<?php echo $orderby === 'link_count' ? $order : 'asc'; ?>">
                            <a
                                href="<?php echo admin_url('admin.php?page=rrze-shorturl&tab=statistic&orderby=link_count&order=' . ($orderby === 'link_count' && $order === 'asc' ? 'desc' : 'asc')); ?>">
                                <span><?php echo __('Link Count', 'rrze-shorturl'); ?></span>
                                <span class="sorting-indicators"><span class="sorting-indicator asc"
                                        aria-hidden="true"></span><span class="sorting-indicator desc"
                                        aria-hidden="true"></span></span>
                            </a>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }
}

