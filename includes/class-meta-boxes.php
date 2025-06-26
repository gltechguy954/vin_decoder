<?php
/**
 * Meta Boxes Class - Handles dynamic meta box generation
 */
class VIN_Decoder_Meta_Boxes {
    
    private $field_manager;
    
    public function __construct($field_manager) {
        $this->field_manager = $field_manager;
    }
    
    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
    }
    
    /**
     * Add meta boxes dynamically based on field groups
     */
    public function add_meta_boxes() {
        $groups = $this->field_manager->get_field_groups();
        
        foreach ($groups as $group) {
            // Skip if group has no fields
            $fields = $this->field_manager->get_fields_by_group($group['id']);
            if (empty($fields)) {
                continue;
            }
            
            // Only show fields marked as show_in_admin
            $visible_fields = array_filter($fields, function($field) {
                return !empty($field['show_in_admin']);
            });
            
            if (empty($visible_fields)) {
                continue;
            }
            
            add_meta_box(
                'car_' . $group['id'],
                $group['label'],
                array($this, 'render_meta_box'),
                'car_listings',
                $group['context'] ?? 'normal',
                $group['priority'] ?? 'default',
                array('group' => $group)
            );
        }
    }
    
    /**
     * Render meta box content
     */
    public function render_meta_box($post, $metabox) {
        $group = $metabox['args']['group'];
        $fields = $this->field_manager->get_fields_by_group($group['id']);
        
        // Filter to only show admin fields
        $fields = array_filter($fields, function($field) {
            return !empty($field['show_in_admin']);
        });
        
        if (empty($fields)) {
            echo '<p>No fields configured for this group.</p>';
            return;
        }
        
        // Nonce field
        wp_nonce_field('car_listing_meta_nonce', 'car_listing_meta_nonce');
        
        // Special handling for features group
        if ($group['id'] === 'features') {
            $this->render_features_meta_box($post, $fields);
        } elseif ($group['id'] === 'photos') {
            $this->render_photos_meta_box($post, $fields);
        } else {
            $this->render_standard_meta_box($post, $fields);
        }
    }
    
    /**
     * Render standard meta box fields
     */
    private function render_standard_meta_box($post, $fields) {
        echo '<table class="form-table">';
        
        foreach ($fields as $field) {
            $value = get_post_meta($post->ID, $field['key'], true);
            
            echo '<tr>';
            echo '<th><label for="' . esc_attr($field['key']) . '">' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            
            $this->render_field($field, $value, $post->ID);
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    /**
     * Render individual field based on type
     */
    private function render_field($field, $value, $post_id = null) {
        switch ($field['type']) {
            case 'text':
                echo '<input type="text" id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
                
            case 'number':
                $min = isset($field['min']) ? 'min="' . esc_attr($field['min']) . '"' : '';
                $max = isset($field['max']) && $field['max'] !== '' ? 'max="' . esc_attr($field['max']) . '"' : '';
                $step = isset($field['step']) ? 'step="' . esc_attr($field['step']) . '"' : '';
                echo '<input type="number" id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" value="' . esc_attr($value) . '" class="regular-text" ' . $min . ' ' . $max . ' ' . $step . ' />';
                break;
                
            case 'textarea':
                echo '<textarea id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" class="regular-text">';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'radio':
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        echo '<label style="margin-right: 15px;">';
                        echo '<input type="radio" name="' . esc_attr($field['key']) . '" value="' . esc_attr($option_value) . '"' . checked($value, $option_value, false) . ' />';
                        echo ' ' . esc_html($option_label);
                        echo '</label>';
                    }
                }
                break;
                
            case 'checkbox':
                echo '<input type="checkbox" id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" value="1"' . checked($value, '1', false) . ' />';
                break;
        }
        
        // Add field description
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        // Add AI fill button for eligible fields
        if (!empty($field['ai_fillable']) && $post_id) {
            echo '<button type="button" class="ai-fill-btn" onclick="fillFieldWithAI(\'' . esc_attr($field['key']) . '\', ' . $post_id . ', this)">🤖 Fill with AI</button>';
        }
    }
    
    /**
     * Render features meta box
     */
    private function render_features_meta_box($post, $fields) {
        echo '<div class="feature-fields">';
        echo '<table class="form-table">';
        
        foreach ($fields as $field) {
            $value = get_post_meta($post->ID, $field['key'], true);
            $checked_yes = is_array($value) ? in_array('yes', $value) : false;
            $checked_no = is_array($value) ? in_array('no', $value) : false;
            
            echo '<tr>';
            echo '<th><label>' . esc_html($field['label']) . '</label></th>';
            echo '<td>';
            echo '<label style="margin-right: 10px;"><input type="checkbox" name="' . esc_attr($field['key']) . '[]" value="yes"' . checked($checked_yes, true, false) . '> ✓ Yes</label> ';
            echo '<label><input type="checkbox" name="' . esc_attr($field['key']) . '[]" value="no"' . checked($checked_no, true, false) . '> ☒ No</label>';
            
            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render photos meta box
     */
    private function render_photos_meta_box($post, $fields) {
        foreach ($fields as $field) {
            $value = get_post_meta($post->ID, $field['key'], true);
            
            echo '<p><label for="' . esc_attr($field['key']) . '">' . esc_html($field['label']) . '</label></p>';
            echo '<textarea id="' . esc_attr($field['key']) . '" name="' . esc_attr($field['key']) . '" rows="5" style="width: 100%;">' . esc_textarea($value) . '</textarea>';
            
            if (!empty($field['description'])) {
                echo '<p class="description">' . esc_html($field['description']) . '</p>';
            }
        }
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Security checks
        if (!isset($_POST['car_listing_meta_nonce']) || !wp_verify_nonce($_POST['car_listing_meta_nonce'], 'car_listing_meta_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Get all fields
        $fields = $this->field_manager->get_all_fields();
        
        foreach ($fields as $field) {
            $key = $field['key'];
            
            // Skip fields not in admin
            if (empty($field['show_in_admin'])) {
                continue;
            }
            
            // Handle different field types
            switch ($field['type']) {
                case 'checkbox_array':
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, array_map('sanitize_text_field', $_POST[$key]));
                    } else {
                        delete_post_meta($post_id, $key);
                    }
                    break;
                    
                case 'number':
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, floatval($_POST[$key]));
                    }
                    break;
                    
                case 'textarea':
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_textarea_field($_POST[$key]));
                    }
                    break;
                    
                case 'checkbox':
                    update_post_meta($post_id, $key, isset($_POST[$key]) ? '1' : '0');
                    break;
                    
                default:
                    if (isset($_POST[$key])) {
                        update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
                    }
                    break;
            }
        }
    }
}
