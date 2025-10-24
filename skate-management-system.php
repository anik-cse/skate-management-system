<?php
// File: skate-management-system.php

/**
 * Plugin Name: Skate Management System
 * Plugin URI: http://yourwebsite.com/
 * Description: A custom web-based system for skate and skatemate inventory, rental, and maintenance management.
 * Version: 1.0.0
 * Author: Mir M.
 * Author URI: https://mirm.pro/
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('SMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMS_PLUGIN_VERSION', '1.0.0');

// -------------------------------------------------------------------
// 1. Activation & Deactivation Hooks (Must be global functions)
// -------------------------------------------------------------------

/**
 * Executes on plugin activation.
 */
function sms_activate_plugin() {
    // Load the Roles class directly to ensure it exists for the activation hook
    require_once SMS_PLUGIN_PATH . 'includes/class-sms-roles.php';
    SMS_Roles::activate();
}
register_activation_hook(__FILE__, 'sms_activate_plugin');

/**
 * Executes on plugin deactivation.
 */
function sms_deactivate_plugin() {
    // Load the Roles class directly to ensure it exists for the deactivation hook
    require_once SMS_PLUGIN_PATH . 'includes/class-sms-roles.php';
    SMS_Roles::deactivate();
}
register_deactivation_hook(__FILE__, 'sms_deactivate_plugin');

// -------------------------------------------------------------------
// 2. Main Plugin Class Initialization
// -------------------------------------------------------------------

final class Skate_Management_System {

    private static $instance = null;

    /**
     * Declared property to avoid PHP 8.2+ Deprecated: Creation of dynamic property error.
     * @var array
     */
    private $includes = [];

    /**
     * Singleton instance.
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load all necessary classes.
     */
    public function load_dependencies() {
        $this->includes = [
            'class-sms-roles.php',
            'class-sms-post-types.php',
            'class-sms-meta-boxes.php',
            'class-sms-admin-columns.php',
            'class-sms-shortcode.php',
            'class-sms-ajax.php',
            'class-sms-sample-data.php',
        ];
        
        foreach ($this->includes as $file) {
            if (file_exists(SMS_PLUGIN_PATH . 'includes/' . $file)) {
                require_once SMS_PLUGIN_PATH . 'includes/' . $file;
            }
        }
    }

    /**
     * Define the hooks and actions.
     */
    public function define_hooks() {
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Initialize all classes
        new SMS_Roles(); 
        new SMS_Post_Types();
        new SMS_Meta_Boxes();
        new SMS_Admin_Columns();
        new SMS_Shortcode();
        new SMS_Ajax();

        // Removed TEMPORARY: Demo Data Creation Hook
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_script('sms-admin-meta', SMS_PLUGIN_URL . 'assets/js/sms-admin-meta.js', ['jquery'], SMS_PLUGIN_VERSION, true);
        
        // Localize script for AJAX URL
        wp_localize_script('sms-admin-meta', 'sms_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sms_meta_box_nonce'),
        ]);
    }

    // Removed TEMPORARY: handle_demo_data_creation method
}

// Ensure the plugin doesn't run until all other plugins have been loaded
add_action('plugins_loaded', ['Skate_Management_System', 'get_instance']);
