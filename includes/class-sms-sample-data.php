<?php
// File: includes/class-sms-sample-data.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the creation of sample data for testing purposes.
 */
class SMS_Sample_Data {

    /**
     * Creates a batch of sample Skatemate posts for testing.
     * @param int $count The number of Skatemates to create.
     */
    public static function create_sample_skatemates($count = 100) {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to run this script.');
        }
        
        // --- Data Definitions for Realistic Skatemates ---
        $sizes = ['XSmall', 'Small', 'Medium', 'Large', 'XXL'];
        $brands = ['Powerslide', 'Rollerblade', 'K2', 'Fr Skates', 'Micro', 'Seba', 'Chaya', 'Roces'];
        $new_used = ['new', 'used'];
        $maintenance_notes = [
            'Cracked plastic buckle on left cuff.',
            'Sticky wheel (bearing failed) on right front.',
            'Needs laces replaced.',
            'Base plate screw loosened; check truck alignment.',
            'Minor cosmetic damage on toe cap.',
            'Heel brake pad worn down.',
            'Missing one wheel bolt.',
            'Dirty bearings, needs cleaning and lubricant.',
        ];
        
        // Assuming a few agents exist (User IDs 2 through 5). These must exist in WordPress.
        $agent_ids = [2, 3, 4, 5]; 
        
        $skatemates_created = 0;

        for ($i = 1; $i <= $count; $i++) {
            $brand = $brands[array_rand($brands)];
            $size = $sizes[array_rand($sizes)];
            $condition = $new_used[array_rand($new_used)];
            $year = rand(2020, 2025);
            $date_in_service = date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'));
            
            // Randomly distribute status: ~70% available, ~15% rented, ~15% maintenance
            $rand_status = rand(1, 100);
            if ($rand_status <= 70) {
                $initial_status = 'available';
            } elseif ($rand_status <= 85) {
                $initial_status = 'rented';
            } else {
                $initial_status = 'maintenance';
            }

            $title = $brand . ' - ' . $size . ' - #' . $i;
            
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_status'  => 'publish',
                'post_type'    => 'sms_skatemate',
            ]);
            
            if (!is_wp_error($post_id)) {
                $skatemates_created++;
                
                // Update Meta Fields
                update_post_meta($post_id, '_sms_date_in_service', $date_in_service);
                update_post_meta($post_id, '_sms_new_used', $condition);
                update_post_meta($post_id, '_sms_year', $year);
                update_post_meta($post_id, '_sms_brand', $brand);
                update_post_meta($post_id, '_sms_size', $size);
                update_post_meta($post_id, '_sms_status', $initial_status);
                
                // Set the QR ID
                $qr_id = 'M-' . str_pad($post_id, 6, '0', STR_PAD_LEFT);
                update_post_meta($post_id, '_sms_qr_id', $qr_id);
                
                // Add rental log if marked as Rented
                if ($initial_status === 'rented') {
                    $rental_log = [
                        [
                            'type'        => 'rental',
                            'agent_id'    => $agent_ids[array_rand($agent_ids)],
                            'rental_date' => time() - rand(3600, 86400 * 3), // Rented out between 1 hour and 3 days ago
                        ]
                    ];
                    update_post_meta($post_id, '_sms_rental_log', $rental_log);
                }

                // Add an initial maintenance log if necessary
                if ($initial_status === 'maintenance') {
                     update_post_meta($post_id, '_sms_maintenance_log', [
                         [
                            'date'  => time() - rand(86400, 86400 * 7), // Logged 1-7 days ago
                            'agent' => $agent_ids[array_rand($agent_ids)],
                            'note'  => $maintenance_notes[array_rand($maintenance_notes)],
                        ]
                     ]);
                }
            }
        }
        return $skatemates_created;
    }
}
