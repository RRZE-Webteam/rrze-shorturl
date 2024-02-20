<?php

namespace RRZE\ShortURL;

class Taxomomy
{
    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('init', [$this, 'register_taxonomies']);
    }

    public function register_cpt()
    {
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'shorturl_links'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
            'taxonomies' => array('shorturl_category', 'shorturl_tag'), // Assign custom taxonomies here
        );
        register_post_type('shorturl_links', $args);
    }

    public function register_taxonomies()
    {
        $labels = array(
            'name' => _x('Categories', 'taxonomy general name', 'textdomain'),
            'singular_name' => _x('Category', 'taxonomy singular name', 'textdomain'),
            'search_items' => __('Search Categories', 'textdomain'),
            'popular_items' => __('Popular Categories', 'textdomain'),
            'all_items' => __('All Categories', 'textdomain'),
            'edit_item' => __('Edit Category', 'textdomain'),
            'update_item' => __('Update Category', 'textdomain'),
            'add_new_item' => __('Add New Category', 'textdomain'),
            'new_item_name' => __('New Category Name', 'textdomain'),
            'separate_items_with_commas' => __('Separate categories with commas', 'textdomain'),
            'add_or_remove_items' => __('Add or remove categories', 'textdomain'),
            'choose_from_most_used' => __('Choose from the most used categories', 'textdomain'),
            'not_found' => __('No categories found', 'textdomain'),
            'menu_name' => __('Categories', 'textdomain'),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'shorturl_category'), // Change 'shorturl_category' to desired slug
        );

        register_taxonomy('shorturl_category', 'shorturl_links', $args);

        $labels = array(
            'name' => __('ShortURL Tags', 'text-domain'),
            'singular_name' => __('ShortURL Tag', 'text-domain'),
            'search_items' => __('Search ShortURL Tags', 'text-domain'),
            'popular_items' => __('Popular ShortURL Tags', 'text-domain'),
            'all_items' => __('All ShortURL Tags', 'text-domain'),
            'edit_item' => __('Edit ShortURL Tag', 'text-domain'),
            'update_item' => __('Update ShortURL Tag', 'text-domain'),
            'add_new_item' => __('Add New ShortURL Tag', 'text-domain'),
            'new_item_name' => __('New ShortURL Tag Name', 'text-domain'),
            'separate_items_with_commas' => __('Separate ShortURL tags with commas', 'text-domain'),
            'add_or_remove_items' => __('Add or remove ShortURL tags', 'text-domain'),
            'choose_from_most_used' => __('Choose from the most used ShortURL tags', 'text-domain'),
            'not_found' => __('No ShortURL tags found', 'text-domain'),
            'menu_name' => __('ShortURL Tags', 'text-domain'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false, // Set to false for non-hierarchical taxonomy (like tags)
            'show_in_rest' => true, // Enable REST API support
            'rewrite' => array('slug' => 'shorturl_tag'), // Set the slug to 'shorturl_tag'
        );

        register_taxonomy('shorturl_tag', array('shorturl_links'), $args); // 'shorturl_links' is the post type to which the taxonomy will be associated
    }
}
