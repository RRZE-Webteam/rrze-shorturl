<?php

/*
Plugin Name:     RRZE ShortURL
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-shorturl
Description:     Plugin, um URLs zu verkürzen. 
Version:         0.1.14
Requires at least: 6.2
Requires PHP:      8.0
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
const RRZE_PHP_VERSION = '8.0';
const RRZE_WP_VERSION = '6.1';

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
    }
    return $error;
}

function drop_custom_tables()
{
    global $wpdb;
    $table_domains = $wpdb->prefix . 'shorturl_domains';
    $table_links = $wpdb->prefix . 'shorturl_links';

    try {
        // Drop shorturl_domains table if it exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_domains'") == $table_domains) {
            $wpdb->query("DROP TABLE $table_domains");
        }

        // Drop shorturl_links table if it exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_links'") == $table_links) {
            $wpdb->query("DROP TABLE $table_links");
        }
    } catch (\Exception $e) {
        // Handle the exception
        error_log("Error in drop_custom_tables: " . $e->getMessage());
    }
}

function create_custom_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    try {
        // Create the shorturl_domains table
        $shorturl_domains_table_name = $wpdb->prefix . 'shorturl_domains';
        $shorturl_domains_sql = "CREATE TABLE IF NOT EXISTS $shorturl_domains_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hostname varchar(255) NOT NULL DEFAULT '' UNIQUE,
            prefix int(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($shorturl_domains_sql);

        // Create the shorturl_links table
        $shorturl_links_table_name = $wpdb->prefix . 'shorturl_links';
        $shorturl_links_sql = "CREATE TABLE IF NOT EXISTS $shorturl_links_table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            long_url varchar(255) UNIQUE NOT NULL,
            short_url varchar(255) NOT NULL,
            uri varchar(255) DEFAULT NULL,
            idm varchar(255) DEFAULT 'system', /* New field idm with default value 'system' */
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP DEFAULT NULL,
            valid_until TIMESTAMP DEFAULT NULL,
            active BOOLEAN DEFAULT TRUE,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($shorturl_links_sql);

        // Insert Service domains
        $aEntries = [
            [
                'hostname' => 'blogs.fau.de',
                'prefix' => 7,
            ],
            [
                'hostname' => 'www.helpdesk.rrze.fau.de',
                'prefix' => 9,
            ],
            [
                'hostname' => 'fau.zoom-x.de',
                'prefix' => 2,
            ],
        ];

        foreach ($aEntries as $entry) {
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$wpdb->prefix}shorturl_domains (hostname, prefix) VALUES (%s, %d)",
                    $entry['hostname'],
                    $entry['prefix']
                )
            );
        }

    } catch (\Exception $e) {
        // Handle the exception
        error_log("Error in create_custom_tables: " . $e->getMessage());
    }
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
    create_custom_tables();
}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation()
{
    // Hier können die Funktionen hinzugefügt werden, die
    // bei der Deaktivierung des Plugins aufgerufen werden müssen.
    // Bspw. delete_option, wp_clear_scheduled_hook, flush_rewrite_rules, etc.
    drop_custom_tables();
}


function rrze_shorturl_init()
{
    register_block_type(__DIR__ . '/build', ['render_callback' => [Settings::class, 'render_url_form']]);
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
    }

    add_action('init', __NAMESPACE__ . '\rrze_shorturl_init');

}
