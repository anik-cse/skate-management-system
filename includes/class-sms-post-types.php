<?php
// File: includes/class-sms-post-types.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMS_Post_Types
 * Registers the 'skate' and 'skatemate' custom post types.
 */
class SMS_Post_Types {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_types']);
    }

    /**
     * Register Custom Post Types: Skates and Skatemates
     */
    public static function register_post_types() {
        // Skates
        $skate_labels = [
            'name' => _x('Skates', 'Post Type General Name', 'skate-management'),
            'singular_name' => _x('Skate', 'Post Type Singular Name', 'skate-management'),
            'menu_name' => __('Skates', 'skate-management'),
            'all_items' => __('All Skates', 'skate-management'),
            'add_new_item' => __('Add New Skate', 'skate-management'),
            'add_new' => __('Add New', 'skate-management'),
            'edit_item' => __('Edit Skate', 'skate-management'),
            'update_item' => __('Update Skate', 'skate-management'),
            'search_items' => __('Search Skate', 'skate-management'),
        ];
        $skate_args = [
            'label' => __('Skate', 'skate-management'),
            'description' => __('Skate inventory', 'skate-management'),
            'labels' => $skate_labels,
            'supports' => ['title', 'author'],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-generic',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability_type' => 'post',
            'show_in_rest' => true,
        ];
        register_post_type('skate', $skate_args);

        // Skatemates
        $skatemate_labels = [
            'name' => _x('Skatemates', 'Post Type General Name', 'skate-management'),
            'singular_name' => _x('Skatemate', 'Post Type Singular Name', 'skate-management'),
            'menu_name' => __('Skatemates', 'skate-management'),
            'all_items' => __('All Skatemates', 'skate-management'),
            'add_new_item' => __('Add New Skatemate', 'skate-management'),
            'add_new' => __('Add New', 'skate-management'),
            'edit_item' => __('Edit Skatemate', 'skate-management'),
            'update_item' => __('Update Skatemate', 'skate-management'),
            'search_items' => __('Search Skatemate', 'skate-management'),
        ];
        $skatemate_args = [
            'label' => __('Skatemate', 'skate-management'),
            'description' => __('Skatemate inventory', 'skate-management'),
            'labels' => $skatemate_labels,
            'supports' => ['title', 'author'],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-shield-alt',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability_type' => 'post',
            'show_in_rest' => true,
        ];
        register_post_type('skatemate', $skatemate_args);
    }
}

