<?php
namespace RRZE\ShortURL;

use RRZE\ShortURL\CustomException;

class CPT
{

    public function __construct()
    {
        add_action('init', [$this, 'register_idm_cpt']);
        add_action('init', [$this, 'register_domain_cpt']);
        add_action('init', [$this, 'register_service_cpt']);
        add_action('init', [$this, 'register_link_cpt']);
        add_action('init', [$this, 'register_category_cpt']);

        add_filter('manage_shorturl_link_posts_columns', [$this, 'add_shorturl_link_custom_columns']);
        add_action('manage_shorturl_link_posts_custom_column', [$this, 'display_shorturl_link_custom_columns'], 10, 2);
        add_filter('manage_edit-shorturl_link_sortable_columns', [$this, 'make_shorturl_link_columns_sortable']);
        add_action('pre_get_posts', [$this, 'sort_shorturl_link_columns']);        
    }

    // Register Custom Post Type for IDMs
    public function register_idm_cpt()
    {
        try {
            register_post_type('shorturl_idm', array(
                'labels' => array(
                    'name' => __('IDMs'),
                    'singular_name' => __('IDM'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title', 'editor', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_idm_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Domains
    public function register_domain_cpt()
    {
        try {
            register_post_type('shorturl_domain', array(
                'labels' => array(
                    'name' => __('Domains'),
                    'singular_name' => __('Domain'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_domain_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Services
    public function register_service_cpt()
    {
        try {
            register_post_type('shorturl_service', array(
                'labels' => array(
                    'name' => __('Services'),
                    'singular_name' => __('Service'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => true,
                'rewrite' => false,
                'supports' => array('title', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_service_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Links
    public function register_link_cpt()
    {
        try {
            register_post_type('shorturl_link', array(
                'labels' => array(
                    'name' => __('Links'),
                    'singular_name' => __('Link'),
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'supports' => array('title', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_link_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Categories
    public function register_category_cpt()
    {
        try {
            register_post_type('shorturl_category', array(
                'labels' => array(
                    'name' => __('Short URL Categories'),
                    'singular_name' => __('Short URL Categories'),
                ),
                'public' => false,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
                'hierarchical' => true, // Makes this CPT hierarchical
                'supports' => array('title', 'editor', 'custom-fields', 'page-attributes'),
            ));

        } catch (CustomException $e) {
            error_log("Error in register_shorturl_category_cpt: " . $e->getMessage());
        }
    }



    // Add columns to table of shorturl_link
    public function add_shorturl_link_custom_columns($columns)
    {
        $columns = [
            'cb' => $columns['cb'],
            'title' => __('Short URL'),
            'long_url' => __('Long URL'),
            'idm' => __('IdM'),
            'date' => $columns['date'],
        ];
        return $columns;
    }

    // return values for columns of shorturl_link
    public function display_shorturl_link_custom_columns($column, $post_id)
    {
        switch ($column) {
            case 'long_url':
                echo esc_html(get_post_meta($post_id, 'long_url', true));
                break;
            case 'idm':
                echo esc_html(get_post_meta($post_id, 'idm', true));
                break;
        }
    }

    public function make_shorturl_link_columns_sortable($columns)
    {
        $columns['title'] = 'title';
        $columns['long_url'] = 'long_url';
        $columns['idm'] = 'idm';
        return $columns;
    }

    public function sort_shorturl_link_columns($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        if ($orderby === 'long_url') {
            $query->set('meta_key', 'long_url');
            $query->set('orderby', 'meta_value');
        }

        if ($orderby === 'idm') {
            $query->set('meta_key', 'idm');
            $query->set('orderby', 'meta_value');
        }
    }    
}
