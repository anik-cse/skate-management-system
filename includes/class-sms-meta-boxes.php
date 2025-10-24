<?php
// File: includes/class-sms-meta-boxes.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMS_Meta_Boxes
 * Handles adding and saving custom meta boxes for CPTs.
 */
class SMS_Meta_Boxes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_data']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    /**
     * Enqueue scripts for admin meta boxes (QR code generator).
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;

        // Only load on add/edit screens for our CPTs
        if (('post.php' == $hook || 'post-new.php' == $hook) && ($post_type == 'skate' || $post_type == 'skatemate')) {
            wp_enqueue_script('qrcode-js', 'https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js', [], '1.0.0', true);
            wp_enqueue_script('sms-admin-meta', SMS_PLUGIN_URL . 'assets/js/sms-admin-meta.js', ['qrcode-js', 'jquery'], SMS_PLUGIN_VERSION, true);
        }
    }

    /**
     * Add Meta Boxes hook.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'skate_details_meta_box',
            __('Skate Details', 'skate-management'),
            [$this, 'render_skate_meta_box'],
            'skate',
            'normal',
            'high'
        );
        add_meta_box(
            'skatemate_details_meta_box',
            __('Skatemate Details', 'skate-management'),
            [$this, 'render_skatemate_meta_box'],
            'skatemate',
            'normal',
            'high'
        );
    }

    /**
     * Render Skate Meta Box
     */
    public function render_skate_meta_box($post) {
        // Add nonce field
        wp_nonce_field('sms_save_skate_meta', 'sms_skate_meta_nonce');

        // Get saved values
        $qr_code = get_post_meta($post->ID, '_skate_qr_code', true);
        if (empty($qr_code)) {
            $qr_code = 'skate_' . $post->ID . '_' . wp_create_nonce($post->ID);
        }
        
        $status = get_post_meta($post->ID, '_skate_status', true) ?: 'available';
        $brand = get_post_meta($post->ID, '_skate_brand', true);
        $year = get_post_meta($post->ID, '_skate_year', true);
        $service_date = get_post_meta($post->ID, '_skate_service_date', true);
        $size = get_post_meta($post->ID, '_skate_size', true);
        $wheel_hardness = get_post_meta($post->ID, '_skate_wheel_hardness', true);
        $truck_type = get_post_meta($post->ID, '_skate_truck_type', true);
        $bearings_type = get_post_meta($post->ID, '_skate_bearings_type', true);
        $laces = get_post_meta($post->ID, '_skate_laces', true);
        $stopper = get_post_meta($post->ID, '_skate_stopper', true);
        $notes = get_post_meta($post->ID, '_skate_notes', true);

        ?>
        <style>
            .sms-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .sms-meta-field { margin-bottom: 15px; }
            .sms-meta-field label { font-weight: bold; display: block; margin-bottom: 5px; }
            .sms-meta-field input, .sms-meta-field select, .sms-meta-field textarea { width: 100%; }
            #sms-qr-code-display { padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 4px; display: inline-block; margin-top: 10px; }
        </style>
        
        <div class="sms-meta-grid">
            <!-- Column 1 -->
            <div>
                <div class="sms-meta-field">
                    <label for="skate_qr_code"><?php _e('Unique QR Code ID', 'skate-management'); ?></label>
                    <input type="text" id="skate_qr_code" name="skate_qr_code" value="<?php echo esc_attr($qr_code); ?>" readonly>
                    <div id="sms-qr-code-display" data-qr-value="<?php echo esc_attr($qr_code); ?>"></div>
                    <button type="button" class="button" id="sms-print-qr"><?php _e('Print QR', 'skate-management'); ?></button>
                </div>
                
                <div class="sms-meta-field">
                    <label for="skate_status"><?php _e('Status', 'skate-management'); ?></label>
                    <select id="skate_status" name="skate_status">
                        <option value="available" <?php selected($status, 'available'); ?>><?php _e('Available', 'skate-management'); ?></option>
                        <option value="rented" <?php selected($status, 'rented'); ?>><?php _e('Rented', 'skate-management'); ?></option>
                        <option value="maintenance" <?php selected($status, 'maintenance'); ?>><?php _e('Maintenance', 'skate-management'); ?></option>
                    </select>
                </div>

                <div class="sms-meta-field">
                    <label for="skate_brand"><?php _e('Brand', 'skate-management'); ?></label>
                    <input type="text" id="skate_brand" name="skate_brand" value="<?php echo esc_attr($brand); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_year"><?php _e('Year', 'skate-management'); ?></label>
                    <input type="number" id="skate_year" name="skate_year" value="<?php echo esc_attr($year); ?>" min="1900" max="<?php echo date('Y') + 1; ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_service_date"><?php _e('Date Entered Service', 'skate-management'); ?></label>
                    <input type="date" id="skate_service_date" name="skate_service_date" value="<?php echo esc_attr($service_date); ?>">
                </div>

                 <div class="sms-meta-field">
                    <label for="skate_notes"><?php _e('Notes / Maintenance Log', 'skate-management'); ?></label>
                    <textarea id="skate_notes" name="skate_notes" rows="5"><?php echo esc_textarea($notes); ?></textarea>
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="sms-meta-field">
                    <label for="skate_size"><?php _e('Size (# or text)', 'skate-management'); ?></label>
                    <input type="text" id="skate_size" name="skate_size" value="<?php echo esc_attr($size); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_wheel_hardness"><?php _e('Wheel Hardness (text)', 'skate-management'); ?></label>
                    <input type="text" id="skate_wheel_hardness" name="skate_wheel_hardness" value="<?php echo esc_attr($wheel_hardness); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_truck_type"><?php _e('Truck Type (metal/plastic/other)', 'skate-management'); ?></label>
                    <input type="text" id="skate_truck_type" name="skate_truck_type" value="<?php echo esc_attr($truck_type); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_bearings_type"><?php _e('Bearings Type (text)', 'skate-management'); ?></label>
                    <input type="text" id="skate_bearings_type" name="skate_bearings_type" value="<?php echo esc_attr($bearings_type); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_laces"><?php _e('Laces (text)', 'skate-management'); ?></label>
                    <input type="text" id="skate_laces" name="skate_laces" value="<?php echo esc_attr($laces); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skate_stopper"><?php _e('Stopper (text)', 'skate-management'); ?></label>
                    <input type="text" id="skate_stopper" name="skate_stopper" value="<?php echo esc_attr($stopper); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Skatemate Meta Box
     */
    public function render_skatemate_meta_box($post) {
        // Add nonce field
        wp_nonce_field('sms_save_skatemate_meta', 'sms_skatemate_meta_nonce');

        // Get saved values
        $qr_code = get_post_meta($post->ID, '_skatemate_qr_code', true);
         if (empty($qr_code)) {
            $qr_code = 'skatemate_' . $post->ID . '_' . wp_create_nonce($post->ID);
        }

        $status = get_post_meta($post->ID, '_skatemate_status', true) ?: 'available';
        $brand = get_post_meta($post->ID, '_skatemate_brand', true);
        $year = get_post_meta($post->ID, '_skatemate_year', true);
        $service_date = get_post_meta($post->ID, '_skatemate_service_date', true);
        $new_used = get_post_meta($post->ID, '_skatemate_new_used', true);
        $size = get_post_meta($post->ID, '_skatemate_size', true);
        $notes = get_post_meta($post->ID, '_skatemate_notes', true);

        ?>
         <style>
            .sms-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
            .sms-meta-field { margin-bottom: 15px; }
            .sms-meta-field label { font-weight: bold; display: block; margin-bottom: 5px; }
            .sms-meta-field input, .sms-meta-field select, .sms-meta-field textarea { width: 100%; }
            #sms-qr-code-display-skatemate { padding: 10px; background: #fff; border: 1px solid #ccc; border-radius: 4px; display: inline-block; margin-top: 10px; }
        </style>
        
        <div class="sms-meta-grid">
            <!-- Column 1 -->
            <div>
                <div class="sms-meta-field">
                    <label for="skatemate_qr_code"><?php _e('Unique QR Code ID', 'skate-management'); ?></label>
                    <input type="text" id="skatemate_qr_code" name="skatemate_qr_code" value="<?php echo esc_attr($qr_code); ?>" readonly>
                    <div id="sms-qr-code-display-skatemate" data-qr-value="<?php echo esc_attr($qr_code); ?>"></div>
                     <button type="button" class="button" id="sms-print-qr-skatemate"><?php _e('Print QR', 'skate-management'); ?></button>
                </div>
                
                <div class="sms-meta-field">
                    <label for="skatemate_status"><?php _e('Status', 'skate-management'); ?></label>
                    <select id="skatemate_status" name="skatemate_status">
                        <option value="available" <?php selected($status, 'available'); ?>><?php _e('Available', 'skate-management'); ?></option>
                        <option value="rented" <?php selected($status, 'rented'); ?>><?php _e('Rented', 'skate-management'); ?></option>
                        <option value="maintenance" <?php selected($status, 'maintenance'); ?>><?php _e('Maintenance', 'skate-management'); ?></option>
                    </select>
                </div>

                <div class="sms-meta-field">
                    <label for="skatemate_brand"><?php _e('Brand', 'skate-management'); ?></label>
                    <input type="text" id="skatemate_brand" name="skatemate_brand" value="<?php echo esc_attr($brand); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skatemate_year"><?php _e('Year', 'skate-management'); ?></label>
                    <input type="number" id="skatemate_year" name="skatemate_year" value="<?php echo esc_attr($year); ?>" min="1900" max="<?php echo date('Y') + 1; ?>">
                </div>
            </div>
            
            <!-- Column 2 -->
            <div>
                <div class="sms-meta-field">
                    <label for="skatemate_service_date"><?php _e('Date Entered Service', 'skate-management'); ?></label>
                    <input type="date" id="skatemate_service_date" name="skatemate_service_date" value="<?php echo esc_attr($service_date); ?>">
                </div>

                <div class="sms-meta-field">
                    <label for="skatemate_new_used"><?php _e('New/Used', 'skate-management'); ?></label>
                    <select id="skatemate_new_used" name="skatemate_new_used">
                        <option value="new" <?php selected($new_used, 'new'); ?>><?php _e('New', 'skate-management'); ?></option>
                        <option value="used" <?php selected($new_used, 'used'); ?>><?php _e('Used', 'skate-management'); ?></option>
                    </select>
                </div>

                <div class="sms-meta-field">
                    <label for="skatemate_size"><?php _e('Size', 'skate-management'); ?></label>
                    <select id="skatemate_size" name="skatemate_size">
                        <option value="xsmall" <?php selected($size, 'xsmall'); ?>><?php _e('XSmall', 'skate-management'); ?></option>
                        <option value="small" <?php selected($size, 'small'); ?>><?php _e('Small', 'skate-management'); ?></option>
                        <option value="medium" <?php selected($size, 'medium'); ?>><?php _e('Medium', 'skate-management'); ?></option>
                        <option value="large" <?php selected($size, 'large'); ?>><?php _e('Large', 'skate-management'); ?></option>
                    </select>
                </div>

                 <div class="sms-meta-field">
                    <label for="skatemate_notes"><?php _e('Notes / Maintenance Log', 'skate-management'); ?></label>
                    <textarea id="skatemate_notes" name="skatemate_notes" rows="5"><?php echo esc_textarea($notes); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save Meta Box Data
     */
    public function save_meta_data($post_id) {
        // Save Skate Data
        if (isset($_POST['sms_skate_meta_nonce'])) {
            if (!wp_verify_nonce($_POST['sms_skate_meta_nonce'], 'sms_save_skate_meta')) {
                return;
            }
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (isset($_POST['post_type']) && 'skate' == $_POST['post_type']) {
                if (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }

            $fields = [
                'skate_qr_code', 'skate_status', 'skate_brand', 'skate_year', 'skate_service_date', 
                'skate_size', 'skate_wheel_hardness', 'skate_truck_type', 'skate_bearings_type', 
                'skate_laces', 'skate_stopper', 'skate_notes'
            ];

            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    if (strpos($field, 'notes') !== false) {
                        $value = sanitize_textarea_field($_POST[$field]);
                    }
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }

        // Save Skatemate Data
        if (isset($_POST['sms_skatemate_meta_nonce'])) {
            if (!wp_verify_nonce($_POST['sms_skatemate_meta_nonce'], 'sms_save_skatemate_meta')) {
                return;
            }
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (isset($_POST['post_type']) && 'skatemate' == $_POST['post_type']) {
                if (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }

            $fields = [
                'skatemate_qr_code', 'skatemate_status', 'skatemate_brand', 'skatemate_year', 
                'skatemate_service_date', 'skatemate_new_used', 'skatemate_size', 'skatemate_notes'
            ];

            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                     $value = sanitize_text_field($_POST[$field]);
                    if (strpos($field, 'notes') !== false) {
                        $value = sanitize_textarea_field($_POST[$field]);
                    }
                    update_post_meta($post_id, '_' . $field, $value);
                }
            }
        }
    }
}

