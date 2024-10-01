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
                'has_archive' => false,
                'supports' => array('title', 'editor', 'custom-fields'),
            ));
        } catch (CustomException $e) {
            error_log("Error in register_link_cpt: " . $e->getMessage());
        }
    }

    // Register Custom Post Type for Categories
    public function register_category_cpt()
    {
        try {
            $labels = array(
                'name'               => __('Short URL Categories'),
                'singular_name'      => __('Short URL Category'),
                'menu_name'          => __('Short URL Categories'),
                'name_admin_bar'     => __('Short URL Category'),
                'add_new'            => __('Add New Category'),
                'add_new_item'       => __('Add New Short URL Category'),
                'edit_item'          => __('Edit Short URL Category'),
                'new_item'           => __('New Short URL Category'),
                'view_item'          => __('View Short URL Category'),
                'search_items'       => __('Search Short URL Categories'),
                'not_found'          => __('No categories found'),
                'not_found_in_trash' => __('No categories found in Trash'),
            );
    
            $args = array(
                'label'               => __('Short URL Category'),
                'description'         => __('Categories for organizing short URLs'),
                'labels'              => $labels,
                'public'              => true,
                'hierarchical'        => true, // Makes this CPT hierarchical
                'show_ui'             => true,
                'show_in_menu'        => true,
                'menu_position'       => 20,
                'supports'            => array('title', 'editor', 'page-attributes'),
                'has_archive'         => true,
                'rewrite'             => array('slug' => 'shorturl_category'),
                'show_in_rest'        => true,
            );
        } catch (CustomException $e) {
            error_log("Error in register_shorturl_category_cpt: " . $e->getMessage());
        }
    }
    }
