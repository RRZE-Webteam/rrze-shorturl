<?php

/*
Plugin Name:     RRZE ShortURL
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-shorturl
Description:     Plugin, um URLs zu verkürzen. 
Version:         2.0.0
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


function deleteOldCron(){
    wp_clear_scheduled_hook('rrze_shorturl_cleanup_database');
}

function migrate_db_to_cpt()
{
    global $wpdb;

    // Migrate shorturl_domains to CPT 'domain'
    $domains = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_domains", ARRAY_A);

    foreach ($domains as $domain) {
        // Check if the domain already exists as a post
        $existing_domain = get_page_by_title($domain['hostname'], OBJECT, 'domain');

        if (!$existing_domain) {
            // Insert domain as a CPT post
            $post_data = [
                'post_title'  => sanitize_text_field($domain['hostname']),
                'post_type'   => 'domain',
                'post_status' => 'publish'
            ];

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Add meta fields
                update_post_meta($post_id, 'prefix', intval($domain['prefix']));
                update_post_meta($post_id, 'external', intval($domain['external']));
                update_post_meta($post_id, 'active', intval($domain['active']));
                update_post_meta($post_id, 'notice', sanitize_text_field($domain['notice']));
                update_post_meta($post_id, 'webmaster_name', sanitize_text_field($domain['webmaster_name']));
                update_post_meta($post_id, 'webmaster_email', sanitize_email($domain['webmaster_email']));
            }
        }
    }

    // Migrate shorturl_idms to CPT 'idm'
    $idms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_idms", ARRAY_A);

    foreach ($idms as $idm) {
        // Check if the IDM already exists as a post
        $existing_idm = get_page_by_title($idm['idm'], OBJECT, 'idm');

        if (!$existing_idm) {
            // Insert IdM as a CPT post
            $post_data = [
                'post_title'  => sanitize_text_field($idm['idm']),
                'post_type'   => 'idm',
                'post_status' => 'publish'
            ];

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Add meta fields
                update_post_meta($post_id, 'allow_uri', intval($idm['allow_uri']));
                update_post_meta($post_id, 'allow_get', intval($idm['allow_get']));
                update_post_meta($post_id, 'allow_utm', intval($idm['allow_utm']));
                update_post_meta($post_id, 'created_by', sanitize_text_field($idm['created_by']));
            }
        }
    }

    // Migrate shorturl_links to CPT 'link'
    $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_links", ARRAY_A);

    foreach ($links as $link) {
        // Insert link as a CPT post
        $post_data = [
            'post_title'  => sanitize_text_field($link['short_url']),
            'post_type'   => 'link',
            'post_status' => 'publish'
        ];

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id)) {
            // Add meta fields
            update_post_meta($post_id, 'domain_id', intval($link['domain_id']));
            update_post_meta($post_id, 'long_url', esc_url($link['long_url']));
            update_post_meta($post_id, 'short_url', esc_url($link['short_url']));
            update_post_meta($post_id, 'uri', sanitize_text_field($link['uri']));
            update_post_meta($post_id, 'idm_id', intval($link['idm_id']));
            update_post_meta($post_id, 'created_at', sanitize_text_field($link['created_at']));
            update_post_meta($post_id, 'updated_at', sanitize_text_field($link['updated_at']));
            update_post_meta($post_id, 'deleted_at', sanitize_text_field($link['deleted_at']));
            update_post_meta($post_id, 'valid_until', sanitize_text_field($link['valid_until']));
            update_post_meta($post_id, 'active', intval($link['active']));
        }
    }

    // Add any additional migrations here (e.g. for shorturl_services, etc.)
    // Example for shorturl_services to CPT 'service'
    $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_services", ARRAY_A);

    foreach ($services as $service) {
        // Check if the service already exists as a post
        $existing_service = get_page_by_title($service['hostname'], OBJECT, 'service');

        if (!$existing_service) {
            // Insert service as a CPT post
            $post_data = [
                'post_title'  => sanitize_text_field($service['hostname']),
                'post_type'   => 'service',
                'post_status' => 'publish'
            ];

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Add meta fields
                update_post_meta($post_id, 'prefix', intval($service['prefix']));
                update_post_meta($post_id, 'regex', sanitize_text_field($service['regex']));
                update_post_meta($post_id, 'active', intval($service['active']));
                update_post_meta($post_id, 'notice', sanitize_text_field($service['notice']));
            }
        }
    }

    // echo __('Migration completed successfully.', 'rrze-shorturl');
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

        migrate_db_to_cpt();


    }

    add_action('init', __NAMESPACE__ . '\rrze_shorturl_init');

}
