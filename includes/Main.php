<?php

namespace RRZE\ShortURL;

defined('ABSPATH') || exit;

use RRZE\ShortURL\CustomerDomains;
use RRZE\ShortURL\API;
use RRZE\ShortURL\ShortURL;

/**
 * Hauptklasse (Main)
 */
class Main {
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
    public function __construct($pluginFile) {
        $this->pluginFile = $pluginFile;
    }

    /**
     * Es wird ausgeführt, sobald die Klasse instanziiert wird.
     */
    public function onLoaded() {
        add_action( 'enqueue_block_editor_assets', [$this, 'enqueueScripts'] );
        add_action( 'wp_enqueue_scripts', [$this, 'enqueueScripts'] );
        // add_action( 'enqueue_block_assets', [$this, 'enqueueScripts'] );

        $taxonomy = new Taxonomy();
        $post = new Post();
        $settings = new Settings();
        $domains = new CustomerDomains(); 
        $shortURL = new ShortURL(); 
        $api = new API();
    }


    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts() {
        wp_enqueue_script('qrious', plugins_url('assets/js/qrious.min.js', plugin_basename($this->pluginFile)));
    }


}
