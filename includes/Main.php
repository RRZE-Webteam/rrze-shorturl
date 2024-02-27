<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

use RRZE\ShortURL\CustomerDomains;
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

        $taxonomy = new Taxonomy();
        $trigger = new Trigger();
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
        wp_enqueue_script('qrious', plugins_url('assets/js/qrious.min.js', plugin_basename($this->pluginFile)));        
        wp_enqueue_script('rrrze-shorturl', plugins_url('src/rrze-shorturl.js', plugin_basename($this->pluginFile)), array('jquery'), null, true);
    }



}
