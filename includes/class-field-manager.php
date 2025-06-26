<?php
/**
 * Field Manager Class - Handles all field definitions and management
 */
class VIN_Decoder_Field_Manager {
    
    private $fields_option_key = 'vin_decoder_field_definitions';
    private $field_groups_key = 'vin_decoder_field_groups';
    
    /**
     * Get all field definitions
     */
    public function get_all_fields() {
        $fields = get_option($this->fields_option_key, array());
        
        // If no fields exist, create defaults
        if (empty($fields)) {
            $this->create_default_fields();
            $fields = get_option($this->fields_option_key, array());
        }
        
        return $fields;
    }
    
    /**
     * Get fields by group
     */
    public function get_fields_by_group($group_id) {
        $all_fields = $this->get_all_fields();
        $grouped_fields = array();
        
        foreach ($all_fields as $field) {
            if (isset($field['group']) && $field['group'] === $group_id) {
                $grouped_fields[] = $field;
            }
        }
        
        return $grouped_fields;
    }
    
    /**
     * Get all field groups
     */
    public function get_field_groups() {
        $groups = get_option($this->field_groups_key, array());
        
        if (empty($groups)) {
            $groups = $this->get_default_groups();
            update_option($this->field_groups_key, $groups);
        }
        
        return $groups;
    }
    
    /**
     * Get a single field definition
     */
    public function get_field($field_key) {
        $fields = $this->get_all_fields();
        
        foreach ($fields as $field) {
            if ($field['key'] === $field_key) {
                return $field;
            }
        }
        
        return null;
    }
    
    /**
     * Add or update a field
     */
    public function save_field($field_data) {
        $fields = $this->get_all_fields();
        $field_key = sanitize_key($field_data['key']);
        
        // Validate required fields
        if (empty($field_key) || empty($field_data['label'])) {
            return false;
        }
        
        // Sanitize field data
        $clean_field = array(
            'key' => $field_key,
            'label' => sanitize_text_field($field_data['label']),
            'type' => sanitize_text_field($field_data['type']),
            'group' => sanitize_text_field($field_data['group']),
            'description' => sanitize_text_field($field_data['description'] ?? ''),
            'required' => !empty($field_data['required']),
            'show_in_admin' => !empty($field_data['show_in_admin']),
            'ai_fillable' => !empty($field_data['ai_fillable']),
            'position' => intval($field_data['position'] ?? 999)
        );
        
        // Handle options for select/radio fields
        if (in_array($clean_field['type'], array('select', 'radio', 'checkbox_array'))) {
            $clean_field['options'] = $this->sanitize_options($field_data['options'] ?? array());
        }
        
        // Handle other field-specific settings
        if ($clean_field['type'] === 'number') {
            $clean_field['min'] = floatval($field_data['min'] ?? 0);
            $clean_field['max'] = floatval($field_data['max'] ?? '');
            $clean_field['step'] = floatval($field_data['step'] ?? 1);
        }
        
        // Update or add field
        $found = false;
        foreach ($fields as $index => $field) {
            if ($field['key'] === $field_key) {
                $fields[$index] = $clean_field;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $fields[] = $clean_field;
        }
        
        // Sort by position
        usort($fields, function($a, $b) {
            return ($a['position'] ?? 999) - ($b['position'] ?? 999);
        });
        
        return update_option($this->fields_option_key, $fields);
    }
    
    /**
     * Delete a field
     */
    public function delete_field($field_key) {
        $fields = $this->get_all_fields();
        $updated_fields = array();
        
        foreach ($fields as $field) {
            if ($field['key'] !== $field_key) {
                $updated_fields[] = $field;
            }
        }
        
        return update_option($this->fields_option_key, $updated_fields);
    }
    
    /**
     * Save field group
     */
    public function save_field_group($group_data) {
        $groups = $this->get_field_groups();
        $group_id = sanitize_key($group_data['id']);
        
        if (empty($group_id) || empty($group_data['label'])) {
            return false;
        }
        
        $clean_group = array(
            'id' => $group_id,
            'label' => sanitize_text_field($group_data['label']),
            'position' => intval($group_data['position'] ?? 999),
            'context' => sanitize_text_field($group_data['context'] ?? 'normal'),
            'priority' => sanitize_text_field($group_data['priority'] ?? 'high')
        );
        
        // Update or add group
        $found = false;
        foreach ($groups as $index => $group) {
            if ($group['id'] === $group_id) {
                $groups[$index] = $clean_group;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $groups[] = $clean_group;
        }
        
        // Sort by position
        usort($groups, function($a, $b) {
            return ($a['position'] ?? 999) - ($b['position'] ?? 999);
        });
        
        return update_option($this->field_groups_key, $groups);
    }
    
    /**
     * Delete a field group
     */
    public function delete_field_group($group_id) {
        $groups = $this->get_field_groups();
        $updated_groups = array();
        
        foreach ($groups as $group) {
            if ($group['id'] !== $group_id) {
                $updated_groups[] = $group;
            }
        }
        
        // Also delete all fields in this group
        $fields = $this->get_all_fields();
        $updated_fields = array();
        
        foreach ($fields as $field) {
            if (!isset($field['group']) || $field['group'] !== $group_id) {
                $updated_fields[] = $field;
            }
        }
        
        update_option($this->fields_option_key, $updated_fields);
        return update_option($this->field_groups_key, $updated_groups);
    }
    
    /**
     * Get NHTSA field mapping
     */
    public function get_nhtsa_field_mapping() {
        $custom_mappings = get_option('vin_decoder_custom_mappings', array());
        
        if (!empty($custom_mappings)) {
            return $custom_mappings;
        }
        
        // Default mappings
        return array(
            'Make' => 'make',
            'Model' => 'model',
            'Model Year' => 'year',
            'Trim' => 'trim',
            'Body Class' => 'body_class',
            'Drive Type' => 'drive_type',
            'Engine Number of Cylinders' => 'engine_cylinders',
            'Displacement (L)' => 'displacement_l',
            'Fuel Type - Primary' => 'fuel_type',
            'Engine Configuration' => 'engine_configuration',
            'Engine Brake (hp) From' => 'horsepower',
            'Transmission Style' => 'transmission',
            'Number of Seats' => 'seating_capacity',
        );
    }
    
    /**
     * Save NHTSA field mappings
     */
    public function save_nhtsa_mappings($mappings) {
        return update_option('vin_decoder_custom_mappings', $mappings);
    }
    
    /**
     * Sanitize field options
     */
    private function sanitize_options($options) {
        if (!is_array($options)) {
            return array();
        }
        
        $clean_options = array();
        foreach ($options as $key => $label) {
            $clean_key = sanitize_key($key);
            if (!empty($clean_key)) {
                $clean_options[$clean_key] = sanitize_text_field($label);
            }
        }
        
        return $clean_options;
    }
    
    /**
     * Get default field groups
     */
    private function get_default_groups() {
        return array(
            array(
                'id' => 'specifications',
                'label' => 'Specifications',
                'position' => 1,
                'context' => 'normal',
                'priority' => 'high'
            ),
            array(
                'id' => 'pricing',
                'label' => 'Pricing',
                'position' => 2,
                'context' => 'normal',
                'priority' => 'high'
            ),
            array(
                'id' => 'colors',
                'label' => 'Colors',
                'position' => 3,
                'context' => 'normal',
                'priority' => 'high'
            ),
            array(
                'id' => 'drive',
                'label' => 'Drive Information',
                'position' => 4,
                'context' => 'normal',
                'priority' => 'high'
            ),
            array(
                'id' => 'performance',
                'label' => 'Performance',
                'position' => 5,
                'context' => 'normal',
                'priority' => 'default'
            ),
            array(
                'id' => 'measurements',
                'label' => 'Measurements',
                'position' => 6,
                'context' => 'normal',
                'priority' => 'default'
            ),
            array(
                'id' => 'features',
                'label' => 'Features',
                'position' => 7,
                'context' => 'normal',
                'priority' => 'default'
            ),
            array(
                'id' => 'photos',
                'label' => 'Photos',
                'position' => 8,
                'context' => 'side',
                'priority' => 'default'
            )
        );
    }
    
    /**
     * Create default field definitions
     */
    public function create_default_fields() {
        $default_fields = array(
            // Specifications
            array(
                'key' => 'vin',
                'label' => 'VIN',
                'type' => 'text',
                'group' => 'specifications',
                'description' => 'Vehicle Identification Number',
                'required' => true,
                'show_in_admin' => true,
                'position' => 1
            ),
            array(
                'key' => 'year',
                'label' => 'Year',
                'type' => 'number',
                'group' => 'specifications',
                'description' => 'Model Year',
                'required' => true,
                'show_in_admin' => true,
                'min' => 1900,
                'max' => 2050,
                'position' => 2
            ),
            array(
                'key' => 'make',
                'label' => 'Make',
                'type' => 'text',
                'group' => 'specifications',
                'description' => 'Vehicle Manufacturer',
                'required' => true,
                'show_in_admin' => true,
                'position' => 3
            ),
            array(
                'key' => 'model',
                'label' => 'Model',
                'type' => 'text',
                'group' => 'specifications',
                'description' => 'Vehicle Model',
                'required' => true,
                'show_in_admin' => true,
                'position' => 4
            ),
            array(
                'key' => 'trim',
                'label' => 'Trim',
                'type' => 'text',
                'group' => 'specifications',
                'description' => 'Trim Level',
                'show_in_admin' => true,
                'position' => 5
            ),
            array(
                'key' => 'stock_number',
                'label' => 'Stock Number',
                'type' => 'text',
                'group' => 'specifications',
                'description' => 'Dealer Stock Number',
                'show_in_admin' => true,
                'position' => 6
            ),
            array(
                'key' => 'body_class',
                'label' => 'Body Class',
                'type' => 'select',
                'group' => 'specifications',
                'description' => 'Vehicle Body Style',
                'show_in_admin' => true,
                'position' => 7,
                'options' => array(
                    '' => 'Select Body Class',
                    'sedan' => 'Sedan',
                    'coupe' => 'Coupe',
                    'hatchback' => 'Hatchback',
                    'wagon' => 'Wagon',
                    'suv' => 'SUV',
                    'crossover' => 'Crossover',
                    'truck' => 'Truck',
                    'van' => 'Van',
                    'minivan' => 'Minivan',
                    'convertible' => 'Convertible',
                    'roadster' => 'Roadster',
                    'pickup' => 'Pickup Truck'
                )
            ),
            
            // Pricing
            array(
                'key' => 'price',
                'label' => 'Price',
                'type' => 'number',
                'group' => 'pricing',
                'description' => 'Asking Price',
                'show_in_admin' => true,
                'position' => 1,
                'min' => 0,
                'step' => 0.01
            ),
            array(
                'key' => 'sales_price',
                'label' => 'Sales Price',
                'type' => 'number',
                'group' => 'pricing',
                'description' => 'Discounted Sales Price',
                'show_in_admin' => true,
                'position' => 2,
                'min' => 0,
                'step' => 0.01
            ),
            array(
                'key' => 'msrp',
                'label' => 'MSRP',
                'type' => 'number',
                'group' => 'pricing',
                'description' => 'Manufacturer Suggested Retail Price',
                'show_in_admin' => true,
                'position' => 3,
                'min' => 0,
                'step' => 0.01
            ),
            
            // Colors
            array(
                'key' => 'exterior_color',
                'label' => 'Exterior Color',
                'type' => 'select',
                'group' => 'colors',
                'description' => 'Exterior Paint Color',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 1,
                'options' => array(
                    '' => 'Select Exterior Color',
                    'black' => 'Black',
                    'white' => 'White',
                    'silver' => 'Silver',
                    'gray' => 'Gray',
                    'blue' => 'Blue',
                    'red' => 'Red',
                    'green' => 'Green',
                    'brown' => 'Brown',
                    'gold' => 'Gold',
                    'yellow' => 'Yellow',
                    'orange' => 'Orange',
                    'purple' => 'Purple',
                    'maroon' => 'Maroon',
                    'tan' => 'Tan',
                    'beige' => 'Beige'
                )
            ),
            array(
                'key' => 'interior_color',
                'label' => 'Interior Color',
                'type' => 'select',
                'group' => 'colors',
                'description' => 'Interior Upholstery Color',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 2,
                'options' => array(
                    '' => 'Select Interior Color',
                    'black' => 'Black',
                    'gray' => 'Gray',
                    'beige' => 'Beige',
                    'tan' => 'Tan',
                    'brown' => 'Brown',
                    'white' => 'White',
                    'red' => 'Red',
                    'blue' => 'Blue',
                    'cream' => 'Cream',
                    'charcoal' => 'Charcoal'
                )
            ),
            
            // Drive Information
            array(
                'key' => 'engine_configuration',
                'label' => 'Engine Configuration',
                'type' => 'text',
                'group' => 'drive',
                'description' => 'Engine Type Configuration',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 1
            ),
            array(
                'key' => 'drive_type',
                'label' => 'Drive Type',
                'type' => 'select',
                'group' => 'drive',
                'description' => 'Drivetrain Type',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 2,
                'options' => array(
                    '' => 'Select Drive Type',
                    'fwd' => 'Front-Wheel Drive (FWD)',
                    'rwd' => 'Rear-Wheel Drive (RWD)',
                    'awd' => 'All-Wheel Drive (AWD)',
                    '4wd' => '4-Wheel Drive (4WD)',
                    'part_time_4wd' => 'Part-Time 4WD'
                )
            ),
            array(
                'key' => 'transmission',
                'label' => 'Transmission',
                'type' => 'select',
                'group' => 'drive',
                'description' => 'Transmission Type',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 3,
                'options' => array(
                    '' => 'Select Transmission',
                    'automatic' => 'Automatic',
                    'manual' => 'Manual',
                    'cvt' => 'CVT (Continuously Variable)',
                    'electric_motor' => 'Electric Motor',
                    'hybrid' => 'Hybrid',
                    'dual_clutch' => 'Dual-Clutch',
                    'semi_automatic' => 'Semi-Automatic'
                )
            ),
            array(
                'key' => 'fuel_type',
                'label' => 'Fuel Type',
                'type' => 'select',
                'group' => 'drive',
                'description' => 'Primary Fuel Type',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 4,
                'options' => array(
                    '' => 'Select Fuel Type',
                    'gasoline' => 'Gasoline',
                    'diesel' => 'Diesel',
                    'electric' => 'Electric',
                    'hybrid' => 'Hybrid',
                    'plug_in_hybrid' => 'Plug-in Hybrid',
                    'ethanol' => 'Ethanol (E85)',
                    'natural_gas' => 'Natural Gas (CNG)',
                    'propane' => 'Propane (LPG)',
                    'hydrogen' => 'Hydrogen Fuel Cell'
                )
            ),
            
            // Performance
            array(
                'key' => 'horsepower',
                'label' => 'Horsepower',
                'type' => 'text',
                'group' => 'performance',
                'description' => 'Engine Horsepower',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 1
            ),
            array(
                'key' => 'torque',
                'label' => 'Torque',
                'type' => 'text',
                'group' => 'performance',
                'description' => 'Engine Torque',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 2
            ),
            array(
                'key' => 'zero_to_sixty',
                'label' => '0-60 mph',
                'type' => 'text',
                'group' => 'performance',
                'description' => '0-60 mph time',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 3
            ),
            array(
                'key' => 'mpg_gas_equivalent',
                'label' => 'MPG - Gas Equivalent',
                'type' => 'text',
                'group' => 'performance',
                'description' => 'Miles Per Gallon',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 4
            ),
            array(
                'key' => 'estimated_electric_range',
                'label' => 'Estimated Electric Range',
                'type' => 'text',
                'group' => 'performance',
                'description' => 'Electric Range in Miles',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 5
            ),
            
            // Measurements
            array(
                'key' => 'seating_capacity',
                'label' => 'Seating Capacity',
                'type' => 'number',
                'group' => 'measurements',
                'description' => 'Number of Seats',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 1,
                'min' => 1,
                'max' => 20
            ),
            array(
                'key' => 'number_of_keys',
                'label' => 'Number of Keys',
                'type' => 'number',
                'group' => 'measurements',
                'description' => 'Number of Keys Included',
                'show_in_admin' => true,
                'position' => 2,
                'min' => 0,
                'max' => 10
            ),
            array(
                'key' => 'cargo_space',
                'label' => 'Cargo Space',
                'type' => 'text',
                'group' => 'measurements',
                'description' => 'Cargo Space (cubic feet)',
                'show_in_admin' => true,
                'ai_fillable' => true,
                'position' => 3
            ),
            
            // Features
            array(
                'key' => 'cruise_control',
                'label' => 'Cruise Control',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Cruise Control System',
                'show_in_admin' => true,
                'position' => 1
            ),
            array(
                'key' => 'apple_carplay',
                'label' => 'Apple CarPlay',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Apple CarPlay Support',
                'show_in_admin' => true,
                'position' => 2
            ),
            array(
                'key' => 'android_auto',
                'label' => 'Android Auto',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Android Auto Support',
                'show_in_admin' => true,
                'position' => 3
            ),
            array(
                'key' => 'backup_camera',
                'label' => 'Backup Camera',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Rear View Camera',
                'show_in_admin' => true,
                'position' => 4
            ),
            array(
                'key' => 'heated_seats',
                'label' => 'Heated Seats',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Heated Seating',
                'show_in_admin' => true,
                'position' => 5
            ),
            array(
                'key' => 'sunroof',
                'label' => 'Sunroof',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Sunroof/Moonroof',
                'show_in_admin' => true,
                'position' => 6
            ),
            array(
                'key' => 'leather_seats',
                'label' => 'Leather Seats',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Genuine Leather Seating',
                'show_in_admin' => true,
                'position' => 7
            ),
            array(
                'key' => 'navigation_system',
                'label' => 'Navigation System',
                'type' => 'checkbox_array',
                'group' => 'features',
                'description' => 'Built-in GPS Navigation',
                'show_in_admin' => true,
                'position' => 8
            ),
            
            // Photos
            array(
                'key' => 'car_photos',
                'label' => 'Car Photos',
                'type' => 'textarea',
                'group' => 'photos',
                'description' => 'Car photo URLs (comma-separated)',
                'show_in_admin' => true,
                'position' => 1
            ),
            
            // Extended Details
            array(
                'key' => 'extended_vehicle_details',
                'label' => 'Extended Vehicle Details',
                'type' => 'textarea',
                'group' => 'specifications',
                'description' => 'Complete NHTSA data',
                'show_in_admin' => false,
                'position' => 999
            )
        );
        
        update_option($this->fields_option_key, $default_fields);
    }
}
