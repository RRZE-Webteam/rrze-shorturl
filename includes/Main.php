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
        // add_action( 'wp_enqueue_scripts', [$this, 'enqueueScripts'] );
        // add_action( 'enqueue_block_assets', [$this, 'enqueueScripts'] );


        $CustomerDomains = new CustomerDomains(); 

        // var_dump($CustomerDomains);
        // exit;

        $shortURL = new ShortURL(); 

        $api = new API();
    }


    /**
     * Enqueue der globale Skripte.
     */
    public function enqueueScripts() {
        // wp_register_style('rrze-faq-style', plugins_url('assets/css/rrze-faq.css', plugin_basename($this->pluginFile)));
        // wp_enqueue_style('rrze-faq-style');
    }


}
