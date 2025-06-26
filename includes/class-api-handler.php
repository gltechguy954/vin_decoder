<?php
/**
 * API Handler Class - Handles NHTSA API interactions
 */
class VIN_Decoder_API_Handler {
    
    private $field_manager;
    
    public function __construct() {
        $this->field_manager = new VIN_Decoder_Field_Manager();
    }
    
    /**
     * Fetch NHTSA data for a VIN
     */
    public function fetch_nhtsa_data($vin) {
        // Simple caching to prevent duplicate calls
        $cache_key = 'vin_' . $vin;
        $cached = wp_cache_get($cache_key, 'vin_decoder');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json", array(
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'vin_decoder', 3600);
        
        return $data;
    }
    
    /**
     * Create car listing from VIN data
     */
    public function create_car_listing($vin, $nhtsa_data = null) {
        // Use provided data or fetch if not available
        if ($nhtsa_data === null) {
            $nhtsa_data = $this->fetch_nhtsa_data($vin);
        }
        
        // Extract vehicle info for title
        $make = '';
        $model = '';
        $year = '';
        
        // Create formatted content
        $post_content = '';
        $extended_details = '';
        
        if (!empty($nhtsa_data['Results'])) {
            $post_content .= "<h2>Vehicle Specifications</h2>\n<ul>\n";
            
            foreach ($nhtsa_data['Results'] as $result) {
                $nhtsa_field = $result['Variable'];
                $value = trim($result['Value']);
                
                // Extract title components
                switch ($nhtsa_field) {
                    case 'Make':
                        $make = $value;
                        break;
                    case 'Model':
                        $model = $value;
                        break;
                    case 'Model Year':
                        $year = $value;
                        break;
                }
                
                // Add to content if value exists
                if (!empty($value)) {
                    $post_content .= "<li><strong>" . esc_html($nhtsa_field) . ":</strong> " . esc_html($value) . "</li>\n";
                    $extended_details .= $nhtsa_field . ': ' . $value . "\n";
                }
            }
            
            $post_content .= "</ul>\n";
            $post_content .= "<p><em>Vehicle data imported via VIN Decoder on " . current_time('F j, Y \a\t g:i A') . "</em></p>";
        }
        
        // Create post title
        $post_title = trim($year . ' ' . $make . ' ' . $model);
        if (empty($post_title) || $post_title === '  ') {
            $post_title = 'Vehicle - ' . $vin;
        }
        
        // Create the post
        $post_data = array(
            'post_title' => $post_title,
            'post_type' => 'car_listings',
            'post_status' => 'publish',
            'post_content' => $post_content,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        // Map NHTSA data to meta fields
        $this->map_nhtsa_to_meta($post_id, $vin, $nhtsa_data, $extended_details);
        
        return array('success' => true, 'post_id' => $post_id);
    }
    
    /**
     * Map NHTSA data to meta fields
     */
    private function map_nhtsa_to_meta($post_id, $vin, $nhtsa_data, $extended_details) {
        // Always save VIN and extended details
        $meta_updates = array(
            'vin' => $vin,
            'extended_vehicle_details' => $extended_details
        );
        
        // Get field mapping
        $field_mapping = $this->field_manager->get_nhtsa_field_mapping();
        
        if (!empty($nhtsa_data['Results'])) {
            foreach ($nhtsa_data['Results'] as $result) {
                $nhtsa_field = $result['Variable'];
                $value = trim($result['Value']);
                
                // Map to meta field if mapping exists
                if (!empty($value) && isset($field_mapping[$nhtsa_field])) {
                    $meta_key = $field_mapping[$nhtsa_field];
                    $meta_updates[$meta_key] = sanitize_text_field($value);
                }
            }
        }
        
        // Fast bulk update
        if (!empty($meta_updates)) {
            global $wpdb;
            
            $values = array();
            foreach ($meta_updates as $key => $value) {
                $values[] = $wpdb->prepare("(%d, %s, %s)", $post_id, $key, $value);
            }
            
            if (!empty($values)) {
                $query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode(', ', $values);
                $wpdb->query($query);
            }
        }
    }
}
