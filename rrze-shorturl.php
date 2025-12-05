<?php

/*
Plugin Name:     RRZE ShortURL
Plugin URI:      https://gitlab.rrze.fau.de/rrze-webteam/rrze-shorturl
Description:     Plugin, um URLs zu verkürzen. 
Version: 3.0.12
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
        /* translators: 1: current PHP version, 2: required PHP version */
        $error = sprintf(__('The server is running PHP version %1$s. The Plugin requires at least PHP version %2$s.', 'rrze-typesettings'), PHP_VERSION, RRZE_PHP_VERSION);
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        /* translators: 1: current WordPress version, 2: required WordPress version */
        $error = sprintf(__('The server is running WordPress version %1$s. The Plugin requires at least WordPress version %2$s.', 'rrze-typesettings'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    } elseif (!class_exists('\RRZE\AccessControl\Permissions')) {
        $error = __('Plugin RRZE-AC is mandatory.', 'rrze-shorturl');
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
        wp_die(esc_html($error));
    }

    // Ab hier können die Funktionen hinzugefügt werden,
    // die bei der Aktivierung des Plugins aufgerufen werden müssen.
    // Bspw. wp_schedule_event, flush_rewrite_rules, etc.
}

/**
 * Wird durchgeführt, nachdem das Plugin deaktiviert wurde.
 */
function deactivation()
{
    // delete the crons we've added in this plugin
    wp_clear_scheduled_hook('rrze_shorturl_fetch_and_store_customerdomains');
    wp_clear_scheduled_hook('rrze_shorturl_cleanup_inactive_idms');
    wp_clear_scheduled_hook('rrze_shorturl_cleanup_invalid_links');


    // delete all entries of all CPT
    $custom_post_types = ['shorturl_idm', 'shorturl_domain', 'shorturl_service', 'shorturl_link', 'shorturl_category'];
    foreach ($custom_post_types as $cpt) {
        $post_ids = get_posts(array(
            'post_type' => $cpt,
            'numberposts' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ));

        foreach ($post_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    // delete our options
    delete_option('rrze_shorturl_option');
    delete_option('rrze_shorturl_services_initialized');
    delete_option('rrze_shorturl_migration_completed');
    delete_option('rrze_shorturl_custom_tables_dropped');
}


function rrze_shorturl_domain_cleanup()
{

    if (get_site_option('rrze_shorturl_domain_cleanup_done')) {
        return;
    }

    $args = [
        'post_type' => 'shorturl_domain',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ];

    $all_posts = get_posts($args);

    if (empty($all_posts)) {
        error_log('rrze_shorturl_cleanup_duplicate_domains(): No shorturl_domain posts found.');
        return;
    }

    // Map hostnames (post_title) to post IDs
    $hostname_map = []; // [hostname => [id1, id2, ...]]

    foreach ($all_posts as $post_id) {
        $post = get_post($post_id);
        if (!$post) {
            continue;
        }

        $hostname = $post->post_title;

        // Normalize hostname just in case (lowercase, trim)
        $hostname = trim(mb_strtolower($hostname));

        if (!isset($hostname_map[$hostname])) {
            $hostname_map[$hostname] = [];
        }

        $hostname_map[$hostname][] = $post_id;
    }

    $deleted_count = 0;

    // Iterate over hostnames and delete duplicates
    foreach ($hostname_map as $hostname => $ids) {
        if (count($ids) <= 1) {
            // No duplicates, nothing to do
            continue;
        }

        sort($ids); // first is the smallest ID

        $keep_id = array_shift($ids); // keep this one
        $duplicate_ids = $ids;

        error_log(sprintf(
            'rrze_shorturl_cleanup_duplicate_domains(): Keeping ID %d for hostname "%s", deleting duplicates: %s',
            $keep_id,
            $hostname,
            implode(', ', $duplicate_ids)
        ));

        // Delete all duplicates
        foreach ($duplicate_ids as $dup_id) {
            // true = force delete, not move to trash
            wp_delete_post($dup_id, true);
            $deleted_count++;
        }
    }

    error_log(sprintf(
        'rrze_shorturl_cleanup_duplicate_domains(): Finished. Deleted %d duplicate shorturl_domain posts.',
        $deleted_count
    ));

    update_site_option('rrze_shorturl_domain_cleanup_done', 1);

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

        add_action('admin_init', __NAMESPACE__ . '\rrze_shorturl_domain_cleanup');

    }
}
