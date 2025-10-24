<?php
// File: includes/class-sms-admin-columns.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMS_Admin_Columns
 * Handles customizing the admin columns for CPTs.
 */
class SMS_Admin_Columns {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter('manage_skate_posts_columns', [$this, 'add_admin_columns']);
        add_filter('manage_skatemate_posts_columns', [$this, 'add_admin_columns']);
        add_action('manage_skate_posts_custom_column', [$this, 'populate_admin_columns'], 10, 2);
        add_action('manage_skatemate_posts_custom_column', [$this, 'populate_admin_columns'], 10, 2);
    }

    /**
     * Add Columns to Admin List
     */
    public function add_admin_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            if ($key == 'title') {
                $new_columns['skate_status'] = __('Status', 'skate-management');
                $new_columns['skate_qr_code'] = __('QR Code ID', 'skate-management');
            }
        }
        return $new_columns;
    }

    /**
     * Populate Admin Columns
     */
    public function populate_admin_columns($column, $post_id) {
        switch ($column) {
            case 'skate_status':
                $status = get_post_meta($post_id, '_' . get_post_type($post_id) . '_status', true);
                echo esc_html(ucfirst($status));
                break;
            case 'skate_qr_code':
                $qr = get_post_meta($post_id, '_' . get_post_type($post_id) . '_qr_code', true);
                echo esc_html($qr);
                break;
        }
    }
}

