<?php
/**
 * Shortcodes Class - Handles dynamic shortcode generation
 */
class VIN_Decoder_Shortcodes {
    
    private $field_manager;
    
    public function __construct($field_manager) {
        $this->field_manager = $field_manager;
    }
    
    public function init() {
        // Register shortcodes for all fields
        $this->register_field_shortcodes();
        
        // Register special shortcodes
        add_shortcode('carfax_link', array($this, 'carfax_link_shortcode'));
        add_shortcode('car_gallery', array($this, 'car_gallery_shortcode'));
        add_shortcode('car_features_list', array($this, 'car_features_list_shortcode'));
        add_shortcode('car_specifications_table', array($this, 'car_specifications_table_shortcode'));
    }
    
    /**
     * Register shortcodes for all meta fields
     */
    private function register_field_shortcodes() {
        $fields = $this->field_manager->get_all_fields();
        
        foreach ($fields as $field) {
            $shortcode_name = 'car_' . $field['key'];
            
            // Create closure to capture field data
            add_shortcode($shortcode_name, function($atts) use ($field) {
                return $this->render_field_shortcode($field, $atts);
            });
        }
    }
    
    /**
     * Render individual field shortcode
     */
    private function render_field_shortcode($field, $atts) {
        global $post;
        
        // Only work on car_listings posts
        if (!$post || $post->post_type !== 'car_listings') {
            return '';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'format' => '',
            'default' => '',
            'before' => '',
            'after' => '',
            'currency' => '$',
            'decimals' => 2,
            'yes_text' => '✓',
            'no_text' => '☒'
        ), $atts);
        
        // Get field value
        $value = get_post_meta($post->ID, $field['key'], true);
        
        // Return default if empty
        if (empty($value) && $value !== '0') {
            return $atts['default'];
        }
        
        // Format based on field type
        switch ($field['type']) {
            case 'number':
                // Format numbers, especially for pricing
                if (in_array($field['key'], array('price', 'sales_price', 'msrp'))) {
                    $value = $atts['currency'] . number_format($value, $atts['decimals']);
                } elseif ($atts['format'] === 'number') {
                    $value = number_format($value);
                }
                break;
                
            case 'checkbox_array':
                // Handle feature checkboxes
                if (is_array($value)) {
                    if (in_array('yes', $value)) {
                        $value = $atts['yes_text'];
                    } elseif (in_array('no', $value)) {
                        $value = $atts['no_text'];
                    } else {
                        $value = '';
                    }
                }
                break;
                
            case 'select':
                // Get the label for select fields if options are defined
                if (!empty($field['options']) && isset($field['options'][$value])) {
                    $value = $field['options'][$value];
                }
                break;
                
            case 'textarea':
                // Convert line breaks for display
                if ($atts['format'] === 'html') {
                    $value = nl2br(esc_html($value));
                }
                break;
        }
        
        // Apply before/after
        if (!empty($value)) {
            $value = $atts['before'] . $value . $atts['after'];
        }
        
        return $value;
    }
    
    /**
     * Carfax link shortcode
     */
    public function carfax_link_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'car_listings') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'text' => 'View Carfax Report',
            'class' => 'carfax-link',
            'target' => '_blank'
        ), $atts);
        
        $vin = get_post_meta($post->ID, 'vin', true);
        if (empty($vin)) {
            return '';
        }
        
        $url = 'https://www.carfax.com/VehicleHistory/p/Report.cfx?partner=CVN_0&vin=' . esc_attr($vin);
        
        return sprintf(
            '<a href="%s" class="%s" target="%s" rel="noopener">%s</a>',
            esc_url($url),
            esc_attr($atts['class']),
            esc_attr($atts['target']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Car gallery shortcode
     */
    public function car_gallery_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'car_listings') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'class' => 'car-gallery',
            'columns' => 3,
            'size' => 'thumbnail'
        ), $atts);
        
        $photos = get_post_meta($post->ID, 'car_photos', true);
        if (empty($photos)) {
            return '';
        }
        
        $photo_urls = array_map('trim', explode(',', $photos));
        if (empty($photo_urls)) {
            return '';
        }
        
        $output = '<div class="' . esc_attr($atts['class']) . '" style="display: grid; grid-template-columns: repeat(' . intval($atts['columns']) . ', 1fr); gap: 10px;">';
        
        foreach ($photo_urls as $url) {
            if (!empty($url)) {
                $output .= '<div class="gallery-item">';
                $output .= '<img src="' . esc_url($url) . '" alt="Car photo" style="width: 100%; height: auto;">';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Features list shortcode
     */
    public function car_features_list_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'car_listings') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'group' => '', // specific feature group or empty for all
            'show_only' => 'yes', // yes, no, or both
            'columns' => 2,
            'class' => 'car-features-list'
        ), $atts);
        
        // Get feature fields
        $fields = $this->field_manager->get_all_fields();
        $feature_fields = array_filter($fields, function($field) use ($atts) {
            if ($field['type'] !== 'checkbox_array') {
                return false;
            }
            if (!empty($atts['group']) && $field['group'] !== $atts['group']) {
                return false;
            }
            return true;
        });
        
        if (empty($feature_fields)) {
            return '';
        }
        
        $features_yes = array();
        $features_no = array();
        
        foreach ($feature_fields as $field) {
            $value = get_post_meta($post->ID, $field['key'], true);
            if (is_array($value)) {
                if (in_array('yes', $value)) {
                    $features_yes[] = $field['label'];
                } elseif (in_array('no', $value)) {
                    $features_no[] = $field['label'];
                }
            }
        }
        
        $output = '<div class="' . esc_attr($atts['class']) . '">';
        
        // Show equipped features
        if (($atts['show_only'] === 'yes' || $atts['show_only'] === 'both') && !empty($features_yes)) {
            $output .= '<div class="equipped-features">';
            $output .= '<h4>Equipped Features</h4>';
            $output .= '<ul style="columns: ' . intval($atts['columns']) . ';">';
            foreach ($features_yes as $feature) {
                $output .= '<li>✓ ' . esc_html($feature) . '</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        // Show not equipped features
        if (($atts['show_only'] === 'no' || $atts['show_only'] === 'both') && !empty($features_no)) {
            $output .= '<div class="not-equipped-features">';
            $output .= '<h4>Not Equipped</h4>';
            $output .= '<ul style="columns: ' . intval($atts['columns']) . ';">';
            foreach ($features_no as $feature) {
                $output .= '<li>☒ ' . esc_html($feature) . '</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Specifications table shortcode
     */
    public function car_specifications_table_shortcode($atts) {
        global $post;
        
        if (!$post || $post->post_type !== 'car_listings') {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'groups' => '', // comma-separated group IDs or empty for all
            'exclude' => '', // comma-separated field keys to exclude
            'class' => 'car-specifications-table'
        ), $atts);
        
        // Parse groups and exclusions
        $show_groups = !empty($atts['groups']) ? array_map('trim', explode(',', $atts['groups'])) : array();
        $exclude_fields = !empty($atts['exclude']) ? array_map('trim', explode(',', $atts['exclude'])) : array();
        
        // Get field groups to display
        $groups = $this->field_manager->get_field_groups();
        if (!empty($show_groups)) {
            $groups = array_filter($groups, function($group) use ($show_groups) {
                return in_array($group['id'], $show_groups);
            });
        }
        
        $output = '<table class="' . esc_attr($atts['class']) . '">';
        
        foreach ($groups as $group) {
            // Skip feature groups in spec table
            if ($group['id'] === 'features') {
                continue;
            }
            
            $fields = $this->field_manager->get_fields_by_group($group['id']);
            if (empty($fields)) {
                continue;
            }
            
            // Filter out excluded fields and non-admin fields
            $fields = array_filter($fields, function($field) use ($exclude_fields) {
                return !in_array($field['key'], $exclude_fields) && !empty($field['show_in_admin']);
            });
            
            if (empty($fields)) {
                continue;
            }
            
            $output .= '<thead><tr><th colspan="2">' . esc_html($group['label']) . '</th></tr></thead>';
            $output .= '<tbody>';
            
            foreach ($fields as $field) {
                $value = get_post_meta($post->ID, $field['key'], true);
                
                // Skip empty values
                if (empty($value) && $value !== '0') {
                    continue;
                }
                
                // Format value based on type
                switch ($field['type']) {
                    case 'number':
                        if (in_array($field['key'], array('price', 'sales_price', 'msrp'))) {
                            $value = '$' . number_format($value, 2);
                        }
                        break;
                    case 'select':
                        if (!empty($field['options']) && isset($field['options'][$value])) {
                            $value = $field['options'][$value];
                        }
                        break;
                }
                
                $output .= '<tr>';
                $output .= '<td><strong>' . esc_html($field['label']) . '</strong></td>';
                $output .= '<td>' . esc_html($value) . '</td>';
                $output .= '</tr>';
            }
            
            $output .= '</tbody>';
        }
        
        $output .= '</table>';
        
        return $output;
    }
}
