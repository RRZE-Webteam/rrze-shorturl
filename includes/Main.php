<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

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

    protected $settings;

    protected $shortcode;

    protected $rights;


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
        add_action('init', [$this, 'initialize_services']);

        $cpt = new CPT();
        $settings = new Settings();
        $domains = new CustomerDomains();
        $cleanup = new CleanupDB();
        $myCrypt = new MyCrypt();
        // $shortURL = new ShortURL();
        // $api = new API();
        // $shortcode = new Shortcode();
    }


    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts()
    {
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('qrious', plugins_url('assets/js/qrious.min.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        // wp_enqueue_script('rrze-shorturl', plugins_url('assets/js/rrze-shorturl.min.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        wp_enqueue_script('rrze-shorturl', plugins_url('src/rrze-shorturl.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
        wp_enqueue_style('wp-list-table');

        // Localize the script with the nonces
        wp_localize_script(
            'rrze-shorturl',
            'rrze_shorturl_ajax_object',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'update_category_label_nonce' => wp_create_nonce('update_category_label_nonce'),
                'add_shorturl_category_nonce' => wp_create_nonce('add_shorturl_category_nonce'),
                'add_shorturl_tag_nonce' => wp_create_nonce('add_shorturl_tag_nonce'),
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

    public function migrate_db_to_cpt()
    {
    
        // Check if migration has been done already
        if (get_option('rrze_shorturl_migration_completed')) {
            return;
        }
    
        global $wpdb;
    
        // Migrate shorturl_domains to CPT 'domain'
        $domains = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_domains", ARRAY_A);
    
        foreach ($domains as $domain) {
            // Check if the domain already exists as a post
            $existing_domain = get_page_by_title($domain['hostname'], OBJECT, 'domain');
    
            if (!$existing_domain) {
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
        }
    
        // Migrate shorturl_idms to CPT 'idm'
        $idms = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shorturl_idms", ARRAY_A);
    
        foreach ($idms as $idm) {
            // Check if the IDM already exists as a post
            $existing_idm = get_page_by_title($idm['idm'], OBJECT, 'idm');
    
            if (!$existing_idm) {
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
                    'post_title' => sanitize_text_field($service['hostname']),
                    'post_type' => 'shorturl_service',
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
    
        update_option('rrze_shorturl_migration_completed', true);
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
            $existing_service = get_page_by_title($entry['hostname'], OBJECT, 'shorturl_service');
    
            if (!$existing_service) {
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
