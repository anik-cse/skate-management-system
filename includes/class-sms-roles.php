<?php
// File: includes/Sms_Roles.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Handles plugin activation and deactivation tasks, primarily user roles.
 */
class Sms_Roles {

    /**
     * Plugin activation hook.
     * Creates custom user roles and capabilities.
     */
    public static function activate() {
        // Create 'Admin' role
        add_role(
            'sms_admin',
            __('Skate Admin', 'skate-management'),
            get_role('editor')->capabilities
        );

        // Add capabilities to the 'Administrator' (Super Admin) role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('create_users');
            $admin_role->add_cap('edit_users');
            $admin_role->add_cap('list_users');
            $admin_role->add_cap('promote_users');
            $admin_role->add_cap('remove_users');
            $admin_role->add_cap('delete_users');
        }
        
        // Ensure post types are registered *during* activation
        Sms_Post_Types::register_post_types();

        // Flush rewrite rules to make sure the CPT permalinks are active
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     * Cleans up roles and capabilities.
     */
    public static function deactivate() {
        // Remove 'Skate Admin' role
        remove_role('sms_admin');

        // Remove capabilities from the 'Administrator' (Super Admin) role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('create_users');
            $admin_role->remove_cap('edit_users');
            $admin_role->remove_cap('list_users');
            $admin_role->remove_cap('promote_users');
            $admin_role->remove_cap('remove_users');
            $admin_role->remove_cap('delete_users');
        }

        // Flush rewrite rules to remove CPT permalinks
        flush_rewrite_rules();
    }
}
