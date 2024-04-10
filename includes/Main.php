<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

use RRZE\ShortURL\Rights;
use RRZE\ShortURL\CustomerDomains;
use RRZE\ShortURL\API;
use RRZE\ShortURL\ShortURL;
use RRZE\ShortURL\Shortcode;
use RRZE\ShortURL\Redirect;

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

        $settings = new Settings();
        $domains = new CustomerDomains();
        $shortURL = new ShortURL();
        $api = new API();
        $shortcode = new Shortcode();
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

        // wp_enqueue_style('rrze-shorturl-css', plugins_url('assets/css/rrze-shorturl.min.css', plugin_basename($this->pluginFile)));
        wp_enqueue_style('rrze-shorturl-css', plugins_url('src/rrze-shorturl.css', plugin_basename($this->pluginFile)));
    }



}
