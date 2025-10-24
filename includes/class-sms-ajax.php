<?php
// File: includes/class-sms-ajax.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMS_Ajax
 * Handles all AJAX requests for the frontend application.
 */
class SMS_Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_ajax_sms_get_dashboard_data', [$this, 'get_dashboard_data']);
        add_action('wp_ajax_sms_get_item_by_qr', [$this, 'get_item_by_qr']);
        add_action('wp_ajax_sms_process_action', [$this, 'process_action']);
    }

    /**
     * Check AJAX nonce.
     */
    private function check_nonce() {
        if (!check_ajax_referer('sms_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token.'], 403);
        }
    }

    /**
     * AJAX: Get Dashboard Data
     */
    public function get_dashboard_data() {
        $this->check_nonce();

        $data = [
            'skates' => ['total' => 0, 'available' => 0, 'rented' => 0, 'maintenance' => 0],
            'skatemates' => ['total' => 0, 'available' => 0, 'rented' => 0, 'maintenance' => 0],
            'skate_list' => [],
            'skatemate_list' => [],
        ];

        // Get Skates
        $skate_query = new WP_Query([
            'post_type' => 'skate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        $data['skates']['total'] = $skate_query->post_count;
        if ($skate_query->have_posts()) {
            while ($skate_query->have_posts()) {
                $skate_query->the_post();
                $status = get_post_meta(get_the_ID(), '_skate_status', true) ?: 'available';
                if (isset($data['skates'][$status])) {
                    $data['skates'][$status]++;
                }
                $data['skate_list'][] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'status' => $status
                ];
            }
        }
        wp_reset_postdata();

        // Get Skatemates
        $skatemate_query = new WP_Query([
            'post_type' => 'skatemate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        $data['skatemates']['total'] = $skatemate_query->post_count;
        if ($skatemate_query->have_posts()) {
            while ($skatemate_query->have_posts()) {
                $skatemate_query->the_post();
                $status = get_post_meta(get_the_ID(), '_skatemate_status', true) ?: 'available';
                if (isset($data['skatemates'][$status])) {
                    $data['skatemates'][$status]++;
                }
                 $data['skatemate_list'][] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'status' => $status
                ];
            }
        }
        wp_reset_postdata();

        wp_send_json_success($data);
    }

    /**
     * AJAX: Get Item by QR Code
     */
    public function get_item_by_qr() {
        $this->check_nonce();

        if (!isset($_POST['qr_code']) || empty($_POST['qr_code'])) {
            wp_send_json_error(['message' => 'QR Code is required.']);
        }

        $qr_code = sanitize_text_field($_POST['qr_code']);
        $item_type = strpos($qr_code, 'skate_') === 0 ? 'skate' : 'skatemate';
        $meta_key = $item_type === 'skate' ? '_skate_qr_code' : '_skatemate_qr_code';
        $notes_key = $item_type === 'skate' ? '_skate_notes' : '_skatemate_notes';
        $status_key = $item_type === 'skate' ? '_skate_status' : '_skatemate_status';


        $query = new WP_Query([
            'post_type' => $item_type,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'meta_key' => $meta_key,
            'meta_value' => $qr_code,
        ]);

        if (!$query->have_posts()) {
            wp_send_json_error(['message' => 'No item found with this QR code.']);
        }

        $query->the_post();
        $post_id = get_the_ID();
        
        $data = [
            'id' => $post_id,
            'title' => get_the_title(),
            'type' => $item_type,
            'qr_code' => $qr_code,
            'status' => get_post_meta($post_id, $status_key, true) ?: 'available',
            'notes' => get_post_meta($post_id, $notes_key, true),
        ];
        
        wp_reset_postdata();

        wp_send_json_success($data);
    }

    /**
     * AJAX: Process Action (Rent, Return, Maintenance)
     */
    public function process_action() {
        $this->check_nonce();

        // Validate input
        $required_fields = ['item_id', 'new_status', 'notes', 'agent_id', 'agent_name', 'log_message'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field])) {
                wp_send_json_error(['message' => "Missing required field: $field"]);
            }
        }

        $item_id = intval($_POST['item_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $notes = sanitize_textarea_field($_POST['notes']);
        $agent_id = intval($_POST['agent_id']);
        $agent_name = sanitize_text_field($_POST['agent_name']);
        $log_message = sanitize_text_field($_POST['log_message']);

        // Get post type
        $post_type = get_post_type($item_id);
        if ($post_type !== 'skate' && $post_type !== 'skatemate') {
             wp_send_json_error(['message' => 'Invalid item type.']);
        }

        $status_key = '_' . $post_type . '_status';
        $notes_key = '_' . $post_type . '_notes';

        // Update Post Meta
        update_post_meta($item_id, $status_key, $new_status);
        update_post_meta($item_id, $notes_key, $notes);

        // Add a log entry as a post comment (simple logging)
        $commentdata = [
            'comment_post_ID' => $item_id,
            'comment_author' => $agent_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'comment_content' => $log_message,
            'user_id' => $agent_id,
            'comment_type' => 'skate_log',
            'comment_approved' => 1,
        ];
        wp_insert_comment($commentdata);
        
        wp_send_json_success([
            'message' => 'Action processed successfully.',
            'new_status' => $new_status
        ]);
    }
}

