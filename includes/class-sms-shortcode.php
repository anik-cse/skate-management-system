<?php
// File: includes/class-sms-shortcode.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMS_Shortcode
 * Handles the [skate_management_app] shortcode and enqueues frontend assets.
 */
class SMS_Shortcode {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('skate_management_app', [$this, 'render_shortcode']);
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_scripts() {
        global $post;
        // Only load assets if the page has the shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'skate_management_app')) {
            
            // External libraries
            wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', [], null, false); // Not ideal for production, but per spec
            wp_enqueue_script('html5-qrcode', 'https://unpkg.com/html5-qrcode', [], null, true);

            // Plugin assets
            wp_enqueue_style('sms-frontend-app', SMS_PLUGIN_URL . 'assets/css/sms-frontend-app.css', [], SMS_PLUGIN_VERSION);
            
            wp_enqueue_script('sms-frontend-app', SMS_PLUGIN_URL . 'assets/js/sms-frontend-app.js', ['html5-qrcode'], SMS_PLUGIN_VERSION, true);

            // Pass data to JS
            $current_user = wp_get_current_user();
            wp_localize_script('sms-frontend-app', 'sms_app_data', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sms_ajax_nonce'),
                'currentAgentId' => $current_user->ID,
                'currentAgentName' => esc_js($current_user->display_name),
                'logoutUrl' => wp_logout_url(get_permalink()),
                'adminUrlSkates' => admin_url('edit.php?post_type=skate'),
                'adminUrlSkatemates' => admin_url('edit.php?post_type=skatemate'),
            ]);
        }
    }

    /**
     * Render the shortcode.
     */
    public function render_shortcode() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to use this application.', 'skate-management') . '</p>' . wp_login_form(['echo' => false]);
        }

        // Check if user has the correct role
        $user = wp_get_current_user();
        if (!in_array('skate_admin', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<p>' . __('You do not have permission to access this application.', 'skate-management') . '</p>';
        }

        ob_start();
        ?>
        <!-- App HTML Structure -->
        <div id="skate-app-container" class="p-4 md:p-8">
            <div class="max-w-6xl mx-auto">
                
                <!-- Header -->
                <header class="flex flex-col md:flex-row justify-between md:items-center mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Skate Management System</h1>
                        <p class="text-lg text-secondary" id="sms-welcome-message">Welcome!</p>
                    </div>
                    <div>
                        <a href="#" id="sms-logout-link" class="sms-button sms-button-gray mt-4 md:mt-0">Logout</a>
                    </div>
                </header>

                <!-- Tabs -->
                <nav class="flex border-b border-gray-700 mb-6">
                    <button data-tab="dashboard" class="sms-tab active text-lg font-medium px-6 py-3 border-b-2 border-transparent">Dashboard</button>
                    <button data-tab="scan" class="sms-tab text-lg font-medium px-6 py-3 border-b-2 border-transparent">Scan QR</button>
                    <a href="#" id="sms-manage-skates-link" target="_blank" class="sms-tab text-lg font-medium px-6 py-3 border-b-2 border-transparent hidden md:inline-block">Manage Skates</a>
                    <a href="#" id="sms-manage-skatemates-link" target="_blank" class="sms-tab text-lg font-medium px-6 py-3 border-b-2 border-transparent hidden md:inline-block">Manage Skatemates</a>
                </nav>

                <!-- App Content -->
                <main id="sms-app-content">
                    
                    <!-- Dashboard View -->
                    <div id="sms-view-dashboard">
                        <h2 class="text-2xl font-semibold mb-4">Inventory Overview</h2>
                        
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                            <!-- Skates -->
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-secondary">Total Skates</h3>
                                <p id="stat-skates-total" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-green-400">Available Skates</h3>
                                <p id="stat-skates-available" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-yellow-400">Rented Skates</h3>
                                <p id="stat-skates-rented" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-red-400">Skates in Maintenance</h3>
                                <p id="stat-skates-maintenance" class="text-4xl font-bold">0</p>
                            </div>
                            <!-- Skatemates -->
                             <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-secondary">Total Skatemates</h3>
                                <p id="stat-skatemates-total" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-green-400">Available Skatemates</h3>
                                <p id="stat-skatemates-available" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-yellow-400">Rented Skatemates</h3>
                                <p id="stat-skatemates-rented" class="text-4xl font-bold">0</p>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-lg font-semibold text-red-400">Skatemates in Maintenance</h3>
                                <p id="stat-skatemates-maintenance" class="text-4xl font-bold">0</p>
                            </div>
                        </div>

                        <div class="flex justify-start mb-6">
                            <button id="sms-refresh-dashboard" class="sms-button sms-button-primary">Refresh Data</button>
                        </div>

                        <!-- Recent Activity (Simplified) -->
                        <h2 class="text-2xl font-semibold mb-4">Full Inventory List</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-xl font-semibold mb-2">Skates</h3>
                                <ul id="list-skates" class="list-disc pl-5 space-y-1">
                                    <!-- JS will populate this -->
                                    <li>Loading...</li>
                                </ul>
                            </div>
                            <div class="sms-stat-card p-4 rounded-lg">
                                <h3 class="text-xl font-semibold mb-2">Skatemates</h3>
                                <ul id="list-skatemates" class="list-disc pl-5 space-y-1">
                                    <!-- JS will populate this -->
                                    <li>Loading...</li>
                                </ul>
                            </div>
                        </div>

                    </div>

                    <!-- Scan View -->
                    <div id="sms-view-scan" class="sms-hidden">
                        <h2 class="text-2xl font-semibold mb-4 text-center">Scan Item QR Code</h2>
                        <div id="sms-qr-reader"></div>
                        <div id="sms-scan-result" class="text-center text-lg mt-4"></div>
                        <div class="text-center mt-4">
                            <button id="sms-stop-scan-btn" class="sms-button sms-button-red sms-hidden">Stop Scanner</button>
                        </div>
                    </div>

                </main>
            </div>
        </div>

        <!-- Modal Template -->
        <div id="sms-modal" class="sms-modal-backdrop sms-hidden">
            <div class="sms-modal-content">
                <h2 id="sms-modal-title" class="text-2xl font-bold mb-4">Item Action</h2>
                
                <div id="sms-modal-body">
                    <!-- Item Info -->
                    <p class="text-lg"><strong id="sms-modal-item-name"></strong></p>
                    <p class="text-sm text-secondary mb-4">QR ID: <span id="sms-modal-item-qr"></span></p>
                    
                    <!-- Item Status -->
                    <div class="mb-4">
                        <span class="text-lg font-semibold">Current Status:</span>
                        <span id="sms-modal-item-status" class="text-lg font-bold px-3 py-1 rounded-full"></span>
                    </div>

                    <!-- Maintenance Notes Log -->
                    <div id="sms-modal-notes-log" class="mb-4">
                        <h4 class="font-semibold text-secondary">Notes / Maintenance History:</h4>
                        <div class="bg-gray-700 p-2 rounded-md h-24 overflow-y-auto text-sm">
                            <pre id="sms-modal-item-notes" class="whitespace-pre-wrap"></pre>
                        </div>
                    </div>
                    
                    <!-- Action Form -->
                    <div id="sms-modal-form">
                        <div id="sms-form-rent" class="sms-hidden">
                            <h3 class="text-xl font-semibold mb-2">Rent this item?</h3>
                            <p class="text-secondary mb-2">Please perform the "Shake, Rattle, Roll" check before renting.</p>
                            <textarea id="sms-rent-notes" class="sms-modal-input" placeholder="Add optional pre-rental check notes..."></textarea>
                        </div>
                        <div id="sms-form-return" class="sms-hidden">
                            <h3 class="text-xl font-semibold mb-2">Return this item?</h3>
                            <p class="text-secondary mb-2">Please inspect the item for damage before returning to rack.</p>
                        </div>
                         <div id="sms-form-maintenance" class="sms-hidden">
                            <h3 class="text-xl font-semibold mb-2">Log Maintenance</h3>
                            <p class="text-secondary mb-2">Add new notes for the maintenance log. This will be appended to the existing notes.</p>
                            <textarea id="sms-maintenance-notes" class="sms-modal-input" placeholder="e.g., 'Replaced left wheel, bearings OK.'"></textarea>
                        </div>
                        <div id="sms-form-complete-maintenance" class="sms-hidden">
                            <h3 class="text-xl font-semibold mb-2">Complete Maintenance?</h3>
                            <p class="text-secondary mb-2">This will mark the item as 'Available'.</p>
                            <textarea id="sms-complete-maintenance-notes" class="sms-modal-input" placeholder="Add final repair notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer (Buttons) -->
                <div id="sms-modal-footer" class="flex flex-wrap gap-2 justify-end mt-6">
                    <!-- Buttons are added dynamically by JS -->
                    <button data-action="close" class="sms-button sms-button-gray">Close</button>
                </div>

                 <!-- Modal Message Area -->
                <div id="sms-modal-message" class="text-center mt-4"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

