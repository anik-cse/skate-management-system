<?php
/**
 * Plugin Name:       Skate Management System
 * Plugin URI:        https://mirm.pro/
 * Description:       A complete skate rental and management system with QR code tracking and maintenance logs.
 * Version:           1.1.0
 * Author:            Mir M. (mirm.pro)
 * Author URI:        https://mirm.pro/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       skate-management
 * Domain Path:       /languages
 */

// File: skate-management-system.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('SMS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SMS_PLUGIN_VERSION', '1.1.0');

// --- FIX ---
// ONLY load the files needed for activation/deactivation hooks.
// The rest of the plugin will be loaded by the main class.
require_once SMS_PLUGIN_PATH . 'includes/class-sms-roles.php';

// Register hooks in the global scope.
register_activation_hook(__FILE__, ['SMS_Roles', 'activate']);
register_deactivation_hook(__FILE__, ['SMS_Roles', 'deactivate']);

/**
 * Main Plugin Class
 */
final class Skate_Management_System {

    private static $instance;

    /**
     * Get the plugin instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self."eof

