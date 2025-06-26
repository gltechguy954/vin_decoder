<?php
/**
 * Main VIN Decoder Plugin Class
 */
class VIN_Decoder {
    
    private $field_manager;
    private $api_handler;
    private $ai_handler;
    private $meta_boxes;
    private $shortcodes;
    private $admin_pages;
    
    public function __construct() {
        $this->field_manager = new VIN_Decoder_Field_Manager();
        $this->api_handler = new VIN_Decoder_API_Handler();
        $this->ai_handler = new VIN_Decoder_AI_Handler();
        $this->meta_boxes = new VIN_Decoder_Meta_Boxes($this->field_manager);
        $this->shortcodes = new VIN_Decoder_Shortcodes($this->field_manager);
        $this->admin_pages = new VIN_Decoder_Admin_Pages($this->field_manager, $this->api_handler, $this->ai_handler);
    }
    
    public function init() {
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        
        // Initialize components
        $this->meta_boxes->init();
        $this->shortcodes->init();
        $this->admin_pages->init();
        $this->ai_handler->init();
        
        // Register meta fields for REST API/Elementor
        add_action('init', array($this, 'register_meta_fields'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    public function register_post_type() {
        register_post_type('car_listings', array(
            'labels' => array(
                'name' => 'Car Listings',
                'singular_name' => 'Car Listing',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Car Listing',
                'edit_item' => 'Edit Car Listing',
                'new_item' => 'New Car Listing',
                'view_item' => 'View Car Listing',
                'search_items' => 'Search Car Listings',
                'not_found' => 'No car listings found',
                'not_found_in_trash' => 'No car listings found in trash',
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-car',
            'menu_position' => 5,
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'car-listings'),
        ));
    }
    
    public function register_meta_fields() {
        $fields = $this->field_manager->get_all_fields();
        
        foreach ($fields as $field) {
            $args = array(
                'show_in_rest' => true,
                'single' => true,
                'type' => $this->get_rest_field_type($field['type']),
                'description' => $field['label'],
                'auth_callback' => function() { 
                    return current_user_can('edit_posts'); 
                }
            );
            
            // Set sanitize callback based on field type
            switch ($field['type']) {
                case 'number':
                    $args['sanitize_callback'] = 'floatval';
                    break;
                case 'checkbox_array':
                    $args['single'] = false;
                    $args['sanitize_callback'] = array($this, 'sanitize_array_field');
                    break;
                case 'textarea':
                    $args['sanitize_callback'] = 'sanitize_textarea_field';
                    break;
                default:
                    $args['sanitize_callback'] = 'sanitize_text_field';
            }
            
            register_post_meta('car_listings', $field['key'], $args);
        }
    }
    
    private function get_rest_field_type($field_type) {
        switch ($field_type) {
            case 'number':
                return 'number';
            case 'checkbox_array':
                return 'array';
            default:
                return 'string';
        }
    }
    
    public function sanitize_array_field($value) {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }
        return array();
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages and car_listings edit pages
        $screen = get_current_screen();
        $is_our_page = (isset($_GET['page']) && strpos($_GET['page'], 'vin-decoder') !== false);
        $is_car_listing = ($screen && $screen->post_type === 'car_listings');
        
        if ($is_our_page || $is_car_listing) {
            wp_enqueue_style(
                'vin-decoder-admin', 
                VIN_DECODER_PLUGIN_URL . 'assets/css/admin-style.css', 
                array(), 
                VIN_DECODER_VERSION
            );
            
            wp_enqueue_script(
                'vin-decoder-admin', 
                VIN_DECODER_PLUGIN_URL . 'assets/js/admin-script.js', 
                array('jquery', 'jquery-ui-sortable'), 
                VIN_DECODER_VERSION, 
                true
            );
            
            wp_localize_script('vin-decoder-admin', 'vinDecoderAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vin_decoder_nonce')
            ));
        }
    }
}
