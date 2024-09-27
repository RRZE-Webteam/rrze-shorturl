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
