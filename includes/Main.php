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

    public static array $CONFIG = [
    ];

    /**
     * Variablen Werte zuweisen.
     * @param string $pluginFile Pfad- und Dateiname der Plugin-Datei
     */
    public function __construct($pluginFile)
    {
        $this->pluginFile = $pluginFile;
        $options = json_decode(get_option('rrze-shorturl'), true);

        self::$CONFIG['ShortURLBase'] = (!empty($options['ShortURLBase']) ? $options['ShortURLBase'] : 'https://go.fau.de');
    }


    /**
     * Es wird ausgeführt, sobald die Klasse instanziiert wird.
     */
    public function onLoaded()
    {
        $cpt = new CPT();
        $settings = new Settings();
        $domains = new CustomerDomains();
        $cleanup = new CleanupDB();
        $myCrypt = new MyCrypt();

        add_action('enqueue_block_editor_assets', [$this, 'enqueueScripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);

        add_action('init', [$this, 'setIdM']);
        add_action('init', [$this, 'rrze_shorturl_set_shorturls'], 20); // priority 20 to make sure CPT is already registered

        add_action('init', [$this, 'initialize_services']);
        add_action('init', [$this, 'init_query_dependend_classes']);
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

        wp_enqueue_script(
            'rrze-shorturl', 
            plugins_url('src/index.js', plugin_basename($this->pluginFile)), // BK 2024-12-15 : "wp-scripts build" causes issues by breaking the functionality of $(document).on('submit', '#edit-link-form', function (e) { ... }
            array('jquery'), 
            filemtime(plugin_dir_path($this->pluginFile) . 'build/index.js'), 
            true);

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

        wp_enqueue_style(
            'rrze-shorturl-css',
            plugins_url('build/css/rrze-shorturl.css', $this->pluginFile),
            [],
            filemtime(plugin_dir_path($this->pluginFile) . 'build/css/rrze-shorturl.css')
        );        
    }

    // private function drop_custom_tables()
    // {
    //     global $wpdb;

    //     try {
    //         // Drop shorturl table if they exist
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_categories");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_tags");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_categories");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_tags");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_domains");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_services");
    //         $result = $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_idms");
    //         // Delete triggers just to be sure (they should be deleted as they are binded to the dropped tables)
    //         $wpdb->query("DROP TRIGGER IF EXISTS validate_url");
    //         $wpdb->query("DROP TRIGGER IF EXISTS validate_hostname");
    //     } catch (CustomException $e) {
    //         // Handle the exception
    //         error_log("Error in drop_custom_tables: " . $e->getMessage());
    //     }
    // }


    // sets the actual idm instead of the id of shorturl_idm
    public function setIdM()
    {

        // Check if migration has been done already
        if (get_option('rrze_shorturl_set_idm_completed')) {
            return;
        }

        $cpts = ['shorturl_domain', 'shorturl_service', 'shorturl_link', 'shorturl_category'];
    
        foreach ($cpts as $cpt) {
            $posts = get_posts([
                'post_type'   => $cpt,
                'post_status' => 'any',
                'numberposts' => -1,
                'fields'      => 'ids',
            ]);
    
            foreach ($posts as $post_id) {
                $idm_id = get_post_meta($post_id, 'idm_id', true);
    
                if (!empty($idm_id)) {
                    $idm_post = get_post($idm_id);
    
                    if ($idm_post && $idm_post->post_type === 'shorturl_idm') {
                        update_post_meta($post_id, 'idm', $idm_post->post_title);
                        // delete_post_meta($post_id, 'idm_id');
                    }
                }
            }
        }

        update_option('rrze_shorturl_set_idm_completed', true);
    }
    
    public function rrze_shorturl_set_shorturls() {
        // Check if the process has already been completed
        if (get_option('rrze_shorturl_set_shorturls_completed')) {
            return;
        }

        $args = [
            'post_type'      => 'shorturl_link',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $long_url = get_post_meta($post_id, 'long_url', true);

                if (empty($long_url)) {
                    continue;
                }

                $uri = get_post_meta($post_id, 'uri', true);
                $old_shorturl = get_post_meta($post_id, 'short_url', true);

                if (!empty($uri)) {
                    // we have a custom URI
                    // create a new 'shorturl_generated'
                    $prefix = '1'; // only customer domains (prefix = 1) can have an URI
                    $shorturl_generated = self::$CONFIG['ShortURLBase'] . '/' . $prefix . ShortURL::cryptNumber($post_id);
                    update_post_meta($post_id, 'shorturl_generated', $shorturl_generated);
                    update_post_meta($post_id, 'shorturl_custom', $old_shorturl);
                } else {
                    update_post_meta($post_id, 'shorturl_generated', $old_shorturl);
                    update_post_meta($post_id, 'shorturl_custom', ''); // Set as empty string to make the column sortable without using "relation OR and EXISTS / NOT EXISTS in the backend
                }

                wp_update_post([
                    'ID'         => $post_id,
                    'post_title' => $long_url,
                ]);
            }

            wp_reset_postdata();
        }

        update_option('rrze_shorturl_set_shorturls_completed', true);
    }

    // public function drop_shorturl_tables()
    // {
    //     if (get_option('rrze_shorturl_custom_tables_dropped')) {
    //         return;
    //     }
    //     $this->drop_custom_tables();
    //     update_option('rrze_shorturl_custom_tables_dropped', true);
    // }

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
                'regex' => 'https://www.faq.rrze.fau.de/otrs/public.pl?Action=PublicFAQZoom;ItemID=$id'
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
