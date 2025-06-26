<?php
/**
 * Plugin Name: VIN Decoder (NHTSA Only - Optimized)
 * Description: Fast VIN Decoder with NHTSA data only, creates car listing posts with native WordPress meta fields.
 * Version: 3.0
 * Author: UC Dev Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VIN_DECODER_VERSION', '3.0');
define('VIN_DECODER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VIN_DECODER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-field-manager.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-ai-handler.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-admin-pages.php';
require_once VIN_DECODER_PLUGIN_DIR . 'includes/class-vin-decoder.php';

// Initialize the plugin
function vin_decoder_init() {
    $plugin = new VIN_Decoder();
    $plugin->init();
}
add_action('plugins_loaded', 'vin_decoder_init');

// Activation hook
register_activation_hook(__FILE__, 'vin_decoder_activate');
function vin_decoder_activate() {
    // Create default field definitions if they don't exist
    $field_manager = new VIN_Decoder_Field_Manager();
    $field_manager->create_default_fields();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'vin_decoder_deactivate');
function vin_decoder_deactivate() {
    flush_rewrite_rules();
}
