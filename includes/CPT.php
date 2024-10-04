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
                'show_in_rest' => false,
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
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'show_in_rest' => false,
                'rewrite' => false,
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
}
