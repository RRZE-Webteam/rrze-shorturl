<?php

namespace RRZE\ShortURL\Config;

defined('ABSPATH') || exit;

/**
 * Gibt der Name der Option zurück.
 * @return array [description]
 */
function getOptionName() {
    return 'rrze-shorturl';
}


/**
 * Gibt die Einstellungen des Menus zurück.
 * @return array [description]
 */
function getMenuSettings() {
    return [
        'page_title'    => __('RRZE ShortURL', 'rrze-shorturl'),
        'menu_title'    => __('RRZE ShortURL', 'rrze-shorturl'),
        'capability'    => 'manage_options',
        'menu_slug'     => 'rrze-shorturl',
        'title'         => __('RRZE ShortURL Settings', 'rrze-shorturl'),
    ];
}

/**
 * Gibt die Einstellungen der Inhaltshilfe zurück.
 * @return array [description]
 */
function getHelpTab() {
    return [
        [
            'id'        => 'rrze-shorturl-help',
            'content'   => [
                '<p>' . __('Here comes the Context Help content.', 'rrze-shorturl') . '</p>'
            ],
            'title'     => __('Overview', 'rrze-shorturl'),
            'sidebar'   => sprintf('<p><strong>%1$s:</strong></p><p><a href="https://blogs.fau.de/webworking">RRZE Webworking</a></p><p><a href="https://github.com/RRZE Webteam">%2$s</a></p>', __('For more information', 'rrze-shorturl'), __('RRZE Webteam on Github', 'rrze-shorturl'))
        ]
    ];
}

/**
 * Gibt die Einstellungen der Optionsbereiche zurück.
 * @return array [description]
 */

function getSections() {
	return [ 
		[
		  	'id' => 'shorurllog',
		  	'title' => __('Logfile', 'rrze-shorturl' )
		]
	];   
}



  
function drop_custom_tables()
{
    global $wpdb;
    $table_domains = $wpdb->prefix . 'shorturl_domains';
    $table_links = $wpdb->prefix . 'shorturl_links';

    try {
        // Drop shorturl_links table if it exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_links'") == $table_links) {
            $wpdb->query("DROP TABLE $table_links");
        }

        // Drop shorturl_domains table if it exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_domains'") == $table_domains) {
            $wpdb->query("DROP TABLE $table_domains");
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
        // Create the shorturl_idms table
        $table_name = $wpdb->prefix . 'shorturl_idms';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            id INT(11) NOT NULL AUTO_INCREMENT,
        idm VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_by VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by VARCHAR(255) NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create the shorturl_domains table
        $table_name = $wpdb->prefix . 'shorturl_domains';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hostname varchar(255) NOT NULL DEFAULT '' UNIQUE,
            prefix int(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create the shorturl_links table
        $table_name = $wpdb->prefix . 'shorturl_links';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            domain_id mediumint(9) NOT NULL,            
            long_url varchar(255) UNIQUE NOT NULL,
            short_url varchar(255) NOT NULL,
            uri varchar(255) DEFAULT NULL,
            idm varchar(255) DEFAULT 'system',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP DEFAULT NULL,
            valid_until TIMESTAMP DEFAULT NULL,
            active BOOLEAN DEFAULT TRUE,
            PRIMARY KEY (id),
            CONSTRAINT fk_domain_id FOREIGN KEY (domain_id) REFERENCES {$table_name}(id) ON DELETE CASCADE
        ) $charset_collate;";
        dbDelta($sql);

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
