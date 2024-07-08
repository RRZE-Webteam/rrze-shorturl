<?php

/*
Plugin Name:     RRZE ShortURL
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-shorturl
Description:     Plugin, um URLs zu verkürzen. 
Version:         1.8.10
Requires at least: 6.4
Requires PHP:      8.2
Author:          RRZE Webteam
Author URI:      https://blogs.fau.de/webworking/
License:         GNU General Public License v2
License URI:     http://www.gnu.org/licenses/gpl-2.0.html
Domain Path:     /languages
Text Domain:     rrze-shorturl
 */

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

require_once 'config/config.php';
const RRZE_PHP_VERSION = '8.2';
const RRZE_WP_VERSION = '6.4';

use RRZE\ShortURL\Main;


// Automatische Laden von Klassen.
spl_autoload_register(function ($class) {
    $prefix = __NAMESPACE__;
    $base_dir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});


// Registriert die Plugin-Funktion, die bei Aktivierung des Plugins ausgeführt werden soll.
register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
// Registriert die Plugin-Funktion, die ausgeführt werden soll, wenn das Plugin deaktiviert wird.
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');
// Wird aufgerufen, sobald alle aktivierten Plugins geladen wurden.
add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * Einbindung der Sprachdateien.
 */
function load_textdomain()
{
    load_plugin_textdomain('rrze-shorturl', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
}

/**
 * Überprüft die minimal erforderliche PHP- u. WP-Version.
 */
function system_requirements()
{
    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle PHP-Version, 2: erforderliche PHP-Version */
        $error = sprintf(__('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-shorturl'), PHP_VERSION, RRZE_PHP_VERSION);
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        /* Übersetzer: 1: aktuelle WP-Version, 2: erforderliche WP-Version */
        $error = sprintf(__('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-shorturl'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    // } elseif(!class_exists('\RRZE\AccessControl\Permissions')) {
    //     $error = __('Plugin RRZE-AC is mandatory.', 'rrze-shorturl');
    }
    return $error;
}


/**
 * Wird durchgeführt, nachdem das Plugin aktiviert wurde.
 */
function activation()
{
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    // Wenn die Überprüfung fehlschlägt, dann wird das Plugin automatisch deaktiviert.
    if ($error = system_requirements()) {
        deactivate_plugins(plugin_basename(__FILE__), false, true);
        wp_die($error);
    }

    // Ab hier können die Funktionen hinzugefügt werden,
    // die bei der Aktivierung des Plugins aufgerufen werden müssen.
    // Bspw. wp_schedule_event, flush_rewrite_rules, etc.
    Config\create_custom_tables();
}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation()
{
    // clean up the database
    Config\drop_custom_tables();

    // delete the crons we've added in this plugin
    wp_clear_scheduled_hook('rrze_shorturl_fetch_and_store_customerdomains');
    wp_clear_scheduled_hook('rrze_shorturl_cleanup_inactive_idms');
    wp_clear_scheduled_hook('rrze_shorturl_cleanup_invalid_links');
}


function rrze_shorturl_init()
{
    register_block_type(__DIR__ . '/build', ['render_callback' => [Settings::class, 'render_url_form']]);
}


function insertWebteam(){
    try {

    global $wpdb;

        // Insert Default Data
        // Insert Webteam and other known VIPs
        define('VIP', [
            'allow_uri' => true,
            'allow_get' => true,
            'allow_utm' => true
        ]);

        $aEntries = [
            'qe28nesi',
            'unrz59',
            'ej64ojyw',
            'zo95zofo',
            'unrz244',
            'unrz228',
            'unrz41',
            'ca27xybo',
            'zi45hupi',
            'ug46aqez'
        ];

        // Add 'fau.de' to each entry and combine with clear IdMs
        $aEntries = array_merge(
            $aEntries,
            array_map(function ($entry) {
                return $entry . 'fau.de';
            }, $aEntries)
        );

        // Merge each entry with VIP array to set allow values
        foreach ($aEntries as $entry) {
            $entry_data = array_merge(array('idm' => $entry), VIP);

            $idm = $entry_data['idm'];
            $allow_uri = $entry_data['allow_uri'];
            $allow_get = $entry_data['allow_get'];
            $allow_utm = $entry_data['allow_utm'];
            $created_by = 'system';

            // Prepare the SQL query string
            $sql_query = $wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}shorturl_idms (idm, allow_uri, allow_get, allow_utm, created_by) VALUES (%s, %d, %d, %d, %s)", $idm, $allow_uri, $allow_get, $allow_utm, $created_by);

            // Log the SQL query string
            error_log("SQL Query: " . $sql_query);

            // Execute the SQL query
            $wpdb->query($sql_query);

            // Log the result of the insert operation
            error_log("Insert result for entry '$idm': Error: " . $wpdb->last_error . ", Rows affected: " . $wpdb->rows_affected);
        }
    } catch (Exception $e) {
        // Handle the exception
        error_log("Error in drop_custom_tables: " . $e->getMessage());
    }

}

function setLinksIndefinite() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'shorturl_links';

    // Update the valid_until to NULL where user has not set valid_until
    $query = "
        UPDATE $table_name
        SET valid_until = NULL
        WHERE valid_until = DATE(DATE_ADD(created_at, INTERVAL 1 YEAR))
    ";

    $wpdb->query($query);
}

function setAllow_UTMtoFalse() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'shorturl_idms';

    $query = "
        UPDATE $table_name
        SET allow_utm = FALSE
    ";

    $wpdb->query($query);
}


function renameField() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'shorturl_idms';

    // Check if the column 'allow_longlifelinks' exists
    $column_exists = $wpdb->get_results(
        $wpdb->prepare(
            "SHOW COLUMNS FROM `$table_name` LIKE %s",
            'allow_longlifelinks'
        )
    );

    if (!empty($column_exists)) {
        // Rename the column
        $sql = "ALTER TABLE `$table_name` CHANGE `allow_longlifelinks` `allow_utm` TINYINT(1) NOT NULL DEFAULT '0'";
        $wpdb->query($sql);
    }
}

/**
 * Wird durchgeführt, nachdem das WP-Grundsystem hochgefahren
 * und alle Plugins eingebunden wurden.
 */
function loaded()
{
    // Sprachdateien werden eingebunden.
    load_textdomain();

    // Überprüft die minimal erforderliche PHP- u. WP-Version.
    if ($error = system_requirements()) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_data = get_plugin_data(__FILE__);
        $plugin_name = $plugin_data['Name'];
        $tag = is_network_admin() ? 'network_admin_notices' : 'admin_notices';
        add_action($tag, function () use ($plugin_name, $error) {
            printf('<div class="notice notice-error"><p>%1$s: %2$s</p></div>', esc_html($plugin_name), esc_html($error));
        });
    } else {
        // Hauptklasse (Main) wird instanziiert.
        $main = new Main(__FILE__);
        $main->onLoaded();

        // insertWebteam();
        renameField();
        setAllow_UTMtoFalse();
        setLinksIndefinite();

    }

    add_action('init', __NAMESPACE__ . '\rrze_shorturl_init');

}
