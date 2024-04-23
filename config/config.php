<?php

namespace RRZE\ShortURL\Config;

defined('ABSPATH') || exit;

/**
 * Gibt der Name der Option zurück.
 * @return string [description]
 */
function getOptionName()
{
    return 'rrze-shorturl';
}


/**
 * Gibt die Einstellungen des Menus zurück.
 * @return array [description]
 */
function getMenuSettings()
{
    return [
        'page_title' => __('RRZE ShortURL', 'rrze-shorturl'),
        'menu_title' => __('RRZE ShortURL', 'rrze-shorturl'),
        'capability' => 'manage_options',
        'menu_slug' => 'rrze-shorturl',
        'title' => __('RRZE ShortURL Settings', 'rrze-shorturl'),
    ];
}

/**
 * Gibt die Einstellungen der Inhaltshilfe zurück.
 * @return array [description]
 */
function getHelpTab()
{
    return [
        [
            'id' => 'rrze-shorturl-help',
            'content' => [
                '<p>' . __('Here comes the Context Help content.', 'rrze-shorturl') . '</p>'
            ],
            'title' => __('Overview', 'rrze-shorturl'),
            'sidebar' => sprintf('<p><strong>%1$s:</strong></p><p><a href="https://blogs.fau.de/webworking">RRZE Webworking</a></p><p><a href="https://github.com/RRZE Webteam">%2$s</a></p>', __('For more information', 'rrze-shorturl'), __('RRZE Webteam on Github', 'rrze-shorturl'))
        ]
    ];
}

/**
 * Gibt die Einstellungen der Optionsbereiche zurück.
 * @return array [description]
 */

function getSections()
{
    return [
        [
            'id' => 'shorurllog',
            'title' => __('Logfile', 'rrze-shorturl')
        ]
    ];
}




function drop_custom_tables()
{
    global $wpdb;

    try {
        // Drop shorturl table if they exist
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_categories");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links_tags");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_categories");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_tags");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_links");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_domains");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}shorturl_idms");

        // Delete triggers just to be sure (they should be deleted as they are binded to the dropped tables)
        $wpdb->query("DROP TRIGGER IF EXISTS validate_url");
        $wpdb->query("DROP TRIGGER IF EXISTS validate_hostname");
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
        // Define table creation queries
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create shorturl_idms table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_idms (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            idm VARCHAR(255) UNIQUE NOT NULL,
            allow_uri BOOLEAN DEFAULT FALSE,
            allow_get BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_by VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_domains table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_domains (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hostname varchar(255) NOT NULL DEFAULT '' UNIQUE,
            prefix int(1) NOT NULL DEFAULT 1,
            active BOOLEAN DEFAULT TRUE,
            notice varchar(255) NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_services table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_services (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hostname varchar(255) NOT NULL DEFAULT '' UNIQUE,
            prefix int(1) NOT NULL DEFAULT 1,
            active BOOLEAN DEFAULT TRUE,
            notice varchar(255) NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_links table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            domain_id mediumint(9) NOT NULL,            
            long_url varchar(255) NOT NULL,
            short_url varchar(255) NULL DEFAULT NULL,
            uri varchar(255) DEFAULT NULL,
            idm_id mediumint(9) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            valid_until DATE DEFAULT NULL,
            active BOOLEAN DEFAULT TRUE,
            PRIMARY KEY (id),
            CONSTRAINT fk_domain_id FOREIGN KEY (domain_id) REFERENCES {$wpdb->prefix}shorturl_domains(id) ON DELETE CASCADE,
            CONSTRAINT fk_idm_id FOREIGN KEY (idm_id) REFERENCES {$wpdb->prefix}shorturl_idms(id)
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_categories table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            label varchar(255) NOT NULL UNIQUE,
            parent_id mediumint(9),
            PRIMARY KEY (id),
            CONSTRAINT fk_parent_id FOREIGN KEY (parent_id) REFERENCES {$wpdb->prefix}shorturl_categories(id)
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_tags table
        // $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_tags (
        //     id mediumint(9) NOT NULL AUTO_INCREMENT,
        //     label varchar(255) NOT NULL UNIQUE,
        //     PRIMARY KEY (id)
        // ) $charset_collate";
        // dbDelta($sql);

        // Create shorturl_links_categories table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_links_categories (
            link_id mediumint(9) NOT NULL,
            category_id mediumint(9) NOT NULL,
            PRIMARY KEY (link_id, category_id),
            CONSTRAINT fk_link_id FOREIGN KEY (link_id) REFERENCES {$wpdb->prefix}shorturl_links(id) ON DELETE CASCADE,
            CONSTRAINT fk_category_id FOREIGN KEY (category_id) REFERENCES {$wpdb->prefix}shorturl_categories(id) ON DELETE CASCADE
        ) $charset_collate";
        dbDelta($sql);

        // Create shorturl_links_tags table
        // $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shorturl_links_tags (
        //     link_id mediumint(9) NOT NULL,
        //     tag_id mediumint(9) NOT NULL,
        //     PRIMARY KEY (link_id, tag_id),
        //     CONSTRAINT fk_link_id2 FOREIGN KEY (link_id) REFERENCES {$wpdb->prefix}shorturl_links(id) ON DELETE CASCADE,
        //     CONSTRAINT fk_tag_id FOREIGN KEY (tag_id) REFERENCES {$wpdb->prefix}shorturl_tags(id) ON DELETE CASCADE
        // ) $charset_collate";
        // dbDelta($sql);

        // Create some triggers to let the database do some job, too ;)
        // Validate URL
        $trigger_sql = "
        CREATE TRIGGER validate_url
        BEFORE INSERT ON {$wpdb->prefix}shorturl_links 
        FOR EACH ROW
        BEGIN
            IF NEW.long_url NOT REGEXP '^https?://.*$' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid long_url format';
            END IF;
        
            IF NEW.short_url IS NOT NULL AND NEW.short_url NOT REGEXP '^https?://.*$' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid short_url format';
            END IF;
        END;
        ";

        $wpdb->query($trigger_sql);

        // Validate Hostname
        $trigger_sql = "
            CREATE TRIGGER validate_hostname
            BEFORE INSERT ON {$wpdb->prefix}shorturl_domains
            FOR EACH ROW
            BEGIN
                IF NEW.hostname NOT REGEXP '^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\\-]*[a-zA-Z0-9])\\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\\-]*[A-Za-z0-9])$' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid hostname format';
                END IF;
            END;
            ";

        $wpdb->query($trigger_sql);

        // Insert Default Data
        // idm "system"
        $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}shorturl_idms (idm, created_by) VALUES (%s, %s)", 'system', 'system'));

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
                    "INSERT IGNORE INTO {$wpdb->prefix}shorturl_services (hostname, prefix) VALUES (%s, %d)",
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
