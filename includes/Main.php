<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

use RRZE\ShortURL\Rights;
use RRZE\ShortURL\CustomerDomains;
use RRZE\ShortURL\CleanupDB;
use RRZE\ShortURL\MyCrypt;
use RRZE\ShortURL\API;
use RRZE\ShortURL\ShortURL;
use RRZE\ShortURL\Shortcode;

/**
 * Hauptklasse (Main)
 */
class Main
{
    /**
     * Der vollständige Pfad- und Dateiname der Plugin-Datei.
     * @var string
     */
    protected $pluginFile;


    /**
     * Variablen Werte zuweisen.
     * @param string $pluginFile Pfad- und Dateiname der Plugin-Datei
     */
    public function __construct($pluginFile)
    {
        $this->pluginFile = $pluginFile;

    }


    /**
     * Es wird ausgeführt, sobald die Klasse instanziiert wird.
     */
    public function onLoaded()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('init', [$this, 'migrate_db_to_cpt']);
        // add_action('init', [$this, 'drop_shorturl_tables']);
        add_action('init', [$this, 'initialize_services']);
        add_action('init', [$this, 'init_query_dependend_classes']);


        $cpt = new CPT();
        $settings = new Settings();
        $domains = new CustomerDomains();
        $cleanup = new CleanupDB();
        $myCrypt = new MyCrypt();
    }

    public function init_query_dependend_classes()
    {
        $rightsObj = new Rights();
        $rights = $rightsObj->getRights();
        $shortURL = new ShortURL($rights);
        $shortcode = new Shortcode($rights);
        $api = new API($rights);
    }


    /* Load necessary WordPress admin styles for consistent UI elements in the frontend */
    public function load_admin_styles()
    {
        // if (!is_admin()) {
            // Load the script for the dismissible "X" button functionality
            wp_enqueue_script('wp-dismiss-notice');

            // Load the basic styles for WordPress admin notices, including the "X" button
            wp_enqueue_style('common');
            wp_enqueue_script('common', includes_url('js/wp-admin/common.js'), array('jquery'), null, true);

            // Optionally load table styles if needed for list tables
            wp_enqueue_style('wp-list-table');
        // }
    }

    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('qrious', plugins_url('assets/js/qrious.min.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        // wp_enqueue_script('rrze-shorturl', plugins_url('assets/js/rrze-shorturl.min.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        wp_enqueue_script('rrze-shorturl', plugins_url('src/js/rrze-shorturl.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        $this->load_admin_styles();

        // Localize the script with the nonces
        wp_localize_script(
            'rrze-shorturl',
            'rrze_shorturl_ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'update_category_label_nonce' => wp_create_nonce('update_category_label_nonce'),
                'add_shorturl_category_nonce' => wp_create_nonce('add_shorturl_category_nonce'),
                'delete_shorturl_link_nonce' => wp_create_nonce('delete_shorturl_link_nonce'),
                'update_shorturl_idm_nonce' => wp_create_nonce('update_shorturl_idm_nonce'),
                'update_shorturl_category_nonce' => wp_create_nonce('update_shorturl_category_nonce'),
                'delete_shorturl_category_nonce' => wp_create_nonce('delete_shorturl_category_nonce'),
                'delete_shorturl_tag_nonce' => wp_create_nonce('delete_shorturl_tag_nonce'),
            )
        );

        wp_enqueue_script('select2', plugins_url('assets/js/select2.min.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        wp_enqueue_style('select2', plugins_url('assets/css/select2.min.css', plugin_basename($this->pluginFile)));

        wp_enqueue_style('rrze-shorturl-css', plugins_url('assets/css/rrze-shorturl.css', plugin_basename($this->pluginFile)));
        //wp_enqueue_style('rrze-shorturl-css', plugins_url('src/rrze-shorturl.css', plugin_basename($this->pluginFile)));
    }

    private function drop_custom_tables()
    {
        global $wpdb;

        try {
            // Drop shorturl table if they exist
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_categories");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_tags");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_categories");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_tags");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_domains");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_services");
            $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_idms");
            // Delete triggers just to be sure (they should be deleted as they are binded to the dropped tables)
            $wpdb->query("DROP TRIGGER IF EXISTS validate_url");
            $wpdb->query("DROP TRIGGER IF EXISTS validate_hostname");
        } catch (CustomException $e) {
            // Handle the exception
            error_log("Error in drop_custom_tables: " . $e->getMessage());
        }
    }


    public function migrate_db_to_cpt()
    {

        // Check if migration has been done already
        if (get_option('rrze_shorturl_migration_completed')) {
            return;
        }

        global $wpdb;

        // Check if all tables exist
        $tables_to_check = [
            'shorturl_idms',
            'shorturl_domains',
            'shorturl_categories',
            'shorturl_links',
            'shorturl_links_categories'
        ];

        foreach ($tables_to_check as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'") != "{$wpdb->prefix}{$table}") {
                return;
            }
        }

        // Migrate shorturl_idms to CPT 'idm'
        $idm_ids = [];
        $idms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_idms", ARRAY_A);

        foreach ($idms as $idm) {
            // Check if the IDM already exists as a post
            $post_id = get_posts(
                array(
                    'post_type' => 'shorturl_idm',
                    'title' => $idm['idm'],
                    'post_status' => 'all',
                    'numberposts' => 1,
                    'fields' => 'ids'
                )
            );

            if (empty($post_id)) {
                // Insert IdM as a CPT post
                $post_data = [
                    'post_title' => sanitize_text_field($idm['idm']),
                    'post_type' => 'shorturl_idm',
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
            $idm_ids[$idm['id']] = $post_id;
        }

        // Migrate shorturl_domains to CPT 'domain'
        $domain_ids = [];
        $domains = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_domains", ARRAY_A);

        foreach ($domains as $domain) {
            // Check if the domain already exists as a post
            $post_id = get_posts(
                array(
                    'post_type' => 'shorturl_domain',
                    'title' => $domain['hostname'],
                    'post_status' => 'all',
                    'numberposts' => 1,
                    'fields' => 'ids'
                )
            );

            if (empty($post_id)) {
                // Insert domain as a CPT post
                $post_data = [
                    'post_title' => sanitize_text_field($domain['hostname']),
                    'post_type' => 'shorturl_domain',
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
            $domain_ids[$domain['id']] = $post_id;
        }


        // Migrate shorturl_categories to CPT 'shorturl_category'
        $category_ids = [];
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_categories ORDER BY id", ARRAY_A);

        foreach ($categories as $category) {
            // Check if the category already exists as a post
            $post_id = get_posts(
                array(
                    'post_type' => 'shorturl_category',
                    'title' => $category['label'],
                    'post_status' => 'all',
                    'numberposts' => 1,
                    'fields' => 'ids'
                )
            );

            if (empty($post_id)) {
                // Insert category as a CPT post
                $post_data = [
                    'post_title' => sanitize_text_field($category['label']),
                    'post_type' => 'shorturl_category',
                    'post_status' => 'publish',
                    'post_parent' => !empty($category['parent_id']) ? intval($category_ids[$category['parent_id']]) : 0 // Set parent category if applicable
                ];

                $post_id = wp_insert_post($post_data);

                if (!is_wp_error($post_id)) {
                    // Add meta fields
                    update_post_meta($post_id, 'idm_id', intval($idm_ids[$category['idm_id']]));
                }
            }
            $category_ids[$category['id']] = $post_id;
        }


        // Migrate shorturl_links to CPT 'link'
        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_links", ARRAY_A);

        foreach ($links as $link) {
            // Insert link as a CPT post
            $post_data = [
                'post_title' => sanitize_text_field($link['short_url']),
                'post_type' => 'shorturl_link',
                'post_status' => 'publish'
            ];

            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Add meta fields
                update_post_meta($post_id, 'domain_id', $domain_ids[$link['domain_id']]);
                update_post_meta($post_id, 'idm_id', $idm_ids[$link['idm_id']]);
                update_post_meta($post_id, 'long_url', esc_url($link['long_url']));
                update_post_meta($post_id, 'short_url', esc_url($link['short_url']));
                update_post_meta($post_id, 'uri', sanitize_text_field($link['uri']));
                update_post_meta($post_id, 'created_at', sanitize_text_field($link['created_at']));
                update_post_meta($post_id, 'updated_at', sanitize_text_field($link['updated_at']));
                update_post_meta($post_id, 'deleted_at', sanitize_text_field($link['deleted_at']));
                update_post_meta($post_id, 'valid_until', sanitize_text_field($link['valid_until']));
                update_post_meta($post_id, 'active', intval($link['active']));

                // Fetch the categories linked to this link from the `shorturl_links_categories` table
                $link_categories = $wpdb->get_results($wpdb->prepare(
                    "SELECT category_id FROM {$wpdb->prefix}shorturl_links_categories WHERE link_id = %d",
                    $link['id']
                ), ARRAY_A);

                // add all categories to link
                foreach ($link_categories as $category) {
                    add_post_meta($post_id, 'category_id', $category_ids[$category['category_id']], false);
                }
            }
        }

        update_option('rrze_shorturl_migration_completed', true);
    }

    public function drop_shorturl_tables()
    {
        if (get_option('rrze_shorturl_custom_tables_dropped')) {
            return;
        }
        $this->drop_custom_tables();
        update_option('rrze_shorturl_custom_tables_dropped', true);
    }

    public function initialize_services()
    {
        // Check if initialization has already been done
        if (get_option('rrze_shorturl_services_initialized')) {
            return; // Initialization has already been performed, exit function
        }

        // Entries to be saved
        $aEntries = [
            [
                'hostname' => 'www.fau.tv',
                'prefix' => 3,
                'regex' => 'https://www.fau.tv/clip/id/$nummer'
            ],
            [
                'hostname' => 'www.faq.rrze.fau.de',
                'prefix' => 8,
                'regex' => 'https://www.helpdesk.rrze.fau.de/otrs/public.pl?Action=PublicFAQ&ItemID=$id'
            ],
            [
                'hostname' => 'www.helpdesk.rrze.fau.de',
                'prefix' => 9,
                'regex' => 'https://www.helpdesk.rrze.fau.de/otrs/index.pl?Action=AgentZoom&TicketID=$id'
            ],
        ];

        // Insert entries as CPTs
        foreach ($aEntries as $entry) {
            // Check if the post already exists
            $existing_service_id = get_posts(
                array(
                    'post_type' => 'shorturl_service',
                    'title' => $entry['hostname'],
                    'post_status' => 'all',
                    'numberposts' => 1,
                    'fields' => 'ids'
                )
            );

            if (empty($existing_service_id)) {
                // Create a new post with the entry details
                $post_data = [
                    'post_title' => $entry['hostname'],
                    'post_type' => 'shorturl_service',
                    'post_status' => 'publish'
                ];

                $post_id = wp_insert_post($post_data);

                if (!is_wp_error($post_id)) {
                    // Add meta data for the newly created service
                    update_post_meta($post_id, 'prefix', $entry['prefix']);
                    update_post_meta($post_id, 'regex', $entry['regex']);
                } else {
                    error_log('Error inserting service: ' . $entry['hostname']);
                }
            }
        }

        // Set an option to indicate that initialization has been completed
        add_option('rrze_shorturl_services_initialized', true);
    }

}
