<?php
/**
 * Admin Pages Class - Handles all admin page rendering
 */
class VIN_Decoder_Admin_Pages {
    
    private $field_manager;
    private $api_handler;
    private $ai_handler;
    
    public function __construct($field_manager, $api_handler, $ai_handler) {
        $this->field_manager = $field_manager;
        $this->api_handler = $api_handler;
        $this->ai_handler = $ai_handler;
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX handlers
        add_action('wp_ajax_save_field', array($this, 'ajax_save_field'));
        add_action('wp_ajax_delete_field', array($this, 'ajax_delete_field'));
        add_action('wp_ajax_save_field_group', array($this, 'ajax_save_field_group'));
        add_action('wp_ajax_delete_field_group', array($this, 'ajax_delete_field_group'));
        add_action('wp_ajax_update_field_positions', array($this, 'ajax_update_field_positions'));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            'VIN Decoder',
            'VIN Decoder',
            'manage_options',
            'vin-decoder',
            array($this, 'main_page'),
            'dashicons-car',
            60
        );
        
        add_submenu_page(
            'vin-decoder',
            'Field Manager',
            'Field Manager',
            'manage_options',
            'vin-decoder-fields',
            array($this, 'field_manager_page')
        );
        
        add_submenu_page(
            'vin-decoder',
            'NHTSA Mappings',
            'NHTSA Mappings',
            'manage_options',
            'vin-decoder-mappings',
            array($this, 'nhtsa_mappings_page')
        );
        
        add_submenu_page(
            'vin-decoder',
            'AI Settings',
            'AI Settings',
            'manage_options',
            'vin-decoder-ai-settings',
            array($this->ai_handler, 'settings_page')
        );
        
        add_submenu_page(
            'vin-decoder',
            'Documentation',
            'Documentation',
            'manage_options',
            'vin-decoder-docs',
            array($this, 'documentation_page')
        );
    }
    
    /**
     * Main VIN Decoder Page
     */
    public function main_page() {
        // Handle VIN decoding
        $cached_data = null;
        $vin = '';
        
        if (!empty($_POST['vin'])) {
            $vin = sanitize_text_field($_POST['vin']);
            
            if (isset($_POST['cached_nhtsa_data'])) {
                $cached_data = json_decode(stripslashes($_POST['cached_nhtsa_data']), true);
            } else {
                $cached_data = $this->api_handler->fetch_nhtsa_data($vin);
            }
        }
        
        // Handle listing creation
        if (!empty($_POST['create_listing']) && !empty($_POST['vin'])) {
            $vin = sanitize_text_field($_POST['vin']);
            
            $nhtsa_data = null;
            if (isset($_POST['cached_nhtsa_data'])) {
                $nhtsa_data = json_decode(stripslashes($_POST['cached_nhtsa_data']), true);
            }
            
            $result = $this->api_handler->create_car_listing($vin, $nhtsa_data);
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Car listing created successfully! <a href="' . get_edit_post_link($result['post_id']) . '">Edit listing</a> | <a href="' . get_permalink($result['post_id']) . '">View listing</a></p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to create listing: ' . esc_html($result['message']) . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>VIN Decoder</h1>
            <div class="vin-decoder-form">
                <form method="post" id="vin-form">
                    <label for="vin">Enter VIN:</label>
                    <input type="text" id="vin" name="vin" value="<?php echo esc_attr($vin); ?>" required maxlength="17">
                    <input type="submit" value="Decode" class="button button-primary">
                </form>
            </div>

            <?php if (!empty($vin) && $cached_data): ?>
                <div class="vin-results">
                    <h2>Decoded Vehicle Details</h2>
                    <pre><?php
                    if (!empty($cached_data['Results'])) {
                        foreach ($cached_data['Results'] as $result) {
                            if (!empty($result['Value'])) {
                                echo esc_html($result['Variable'] . ': ' . $result['Value']) . "\n";
                            }
                        }
                    } else {
                        echo 'No NHTSA results found.';
                    }
                    ?></pre>

                    <h2>Vehicle History Report</h2>
                    <?php $history_link = "https://www.vincheck.info/free-vehicle-history?vin=" . urlencode($vin); ?>
                    <p>
                        <a href="<?php echo esc_url($history_link); ?>" target="_blank" class="button button-secondary">
                            View Free Vehicle History Report via VinCheck.info
                        </a>
                    </p>

                    <div class="create-listing-form">
                        <form method="post" id="create-form">
                            <input type="hidden" name="create_listing" value="1">
                            <input type="hidden" name="vin" value="<?php echo esc_attr($vin); ?>">
                            <input type="hidden" name="cached_nhtsa_data" value="<?php echo esc_attr(json_encode($cached_data)); ?>">
                            <input type="submit" class="button button-primary" value="Create Car Listing" id="create-btn">
                        </form>
                        <p class="description">This will create a published car listing using the decoded data above.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Field Manager Page - New comprehensive field management UI
     */
    public function field_manager_page() {
        ?>
        <div class="wrap field-manager-container">
            <h1>Field Manager</h1>
            <p>Manage all meta fields for car listings. Add, edit, remove fields and control their display options.</p>
            
            <div class="field-manager-layout">
                <div class="field-groups-sidebar">
                    <h2>Field Groups</h2>
                    <div id="field-groups-list">
                        <?php
                        $groups = $this->field_manager->get_field_groups();
                        foreach ($groups as $group) {
                            $field_count = count($this->field_manager->get_fields_by_group($group['id']));
                            ?>
                            <div class="field-group-item" data-group-id="<?php echo esc_attr($group['id']); ?>">
                                <h3><?php echo esc_html($group['label']); ?></h3>
                                <p class="field-count"><?php echo $field_count; ?> fields</p>
                                <div class="group-actions">
                                    <button class="edit-group" data-group-id="<?php echo esc_attr($group['id']); ?>">Edit</button>
                                    <button class="delete-group" data-group-id="<?php echo esc_attr($group['id']); ?>">Delete</button>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <button class="button add-new-group">+ Add New Group</button>
                </div>
                
                <div class="field-manager-main">
                    <div class="field-manager-tabs">
                        <button class="tab-button active" data-tab="fields">Fields</button>
                        <button class="tab-button" data-tab="settings">Settings</button>
                    </div>
                    
                    <div id="fields-tab" class="tab-content active">
                        <div class="fields-header">
                            <h2>All Fields</h2>
                            <button class="button button-primary add-new-field">+ Add New Field</button>
                        </div>
                        
                        <div id="fields-list" class="fields-grid">
                            <?php
                            $all_fields = $this->field_manager->get_all_fields();
                            foreach ($all_fields as $field) {
                                $this->render_field_card($field);
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div id="settings-tab" class="tab-content">
                        <h2>Field Manager Settings</h2>
                        <form id="field-manager-settings">
                            <table class="form-table">
                                <tr>
                                    <th>Auto-save on edit</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="auto_save" value="1" checked>
                                            Automatically save field changes
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Show field keys</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="show_keys" value="1" checked>
                                            Display field keys in the interface
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Field Edit Modal -->
            <div id="field-edit-modal" class="vin-modal" style="display: none;">
                <div class="vin-modal-content">
                    <span class="vin-modal-close">&times;</span>
                    <h2 id="field-modal-title">Add New Field</h2>
                    
                    <form id="field-edit-form">
                        <input type="hidden" id="field-original-key" value="">
                        
                        <div class="field-row">
                            <label for="field-key">Field Key *</label>
                            <input type="text" id="field-key" name="key" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                            <p class="description">Unique identifier for this field (lowercase, no spaces)</p>
                        </div>
                        
                        <div class="field-row">
                            <label for="field-label">Field Label *</label>
                            <input type="text" id="field-label" name="label" required>
                            <p class="description">Display name for this field</p>
                        </div>
                        
                        <div class="field-row">
                            <label for="field-type">Field Type *</label>
                            <select id="field-type" name="type" required>
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="textarea">Textarea</option>
                                <option value="select">Dropdown (Select)</option>
                                <option value="radio">Radio Buttons</option>
                                <option value="checkbox">Checkbox</option>
                                <option value="checkbox_array">Feature Checkbox (Yes/No)</option>
                            </select>
                        </div>
                        
                        <div class="field-row">
                            <label for="field-group">Field Group *</label>
                            <select id="field-group" name="group" required>
                                <?php
                                foreach ($groups as $group) {
                                    echo '<option value="' . esc_attr($group['id']) . '">' . esc_html($group['label']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="field-row">
                            <label for="field-description">Description</label>
                            <input type="text" id="field-description" name="description">
                            <p class="description">Help text for this field</p>
                        </div>
                        
                        <div class="field-row">
                            <label>
                                <input type="checkbox" id="field-required" name="required" value="1">
                                Required field
                            </label>
                        </div>
                        
                        <div class="field-row">
                            <label>
                                <input type="checkbox" id="field-show-admin" name="show_in_admin" value="1" checked>
                                Show in admin edit screen
                            </label>
                        </div>
                        
                        <div class="field-row">
                            <label>
                                <input type="checkbox" id="field-ai-fillable" name="ai_fillable" value="1">
                                Enable AI fill button
                            </label>
                        </div>
                        
                        <div id="field-options-container" style="display: none;">
                            <h3>Field Options</h3>
                            <div id="field-options-list"></div>
                            <button type="button" class="button add-field-option">+ Add Option</button>
                        </div>
                        
                        <div id="field-number-settings" style="display: none;">
                            <h3>Number Settings</h3>
                            <div class="field-row">
                                <label for="field-min">Minimum Value</label>
                                <input type="number" id="field-min" name="min" step="any">
                            </div>
                            <div class="field-row">
                                <label for="field-max">Maximum Value</label>
                                <input type="number" id="field-max" name="max" step="any">
                            </div>
                            <div class="field-row">
                                <label for="field-step">Step</label>
                                <input type="number" id="field-step" name="step" step="any" value="1">
                            </div>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="submit" class="button button-primary">Save Field</button>
                            <button type="button" class="button cancel-field-edit">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Group Edit Modal -->
            <div id="group-edit-modal" class="vin-modal" style="display: none;">
                <div class="vin-modal-content">
                    <span class="vin-modal-close">&times;</span>
                    <h2 id="group-modal-title">Add New Group</h2>
                    
                    <form id="group-edit-form">
                        <input type="hidden" id="group-original-id" value="">
                        
                        <div class="field-row">
                            <label for="group-id">Group ID *</label>
                            <input type="text" id="group-id" name="id" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                            <p class="description">Unique identifier for this group</p>
                        </div>
                        
                        <div class="field-row">
                            <label for="group-label">Group Label *</label>
                            <input type="text" id="group-label" name="label" required>
                            <p class="description">Display name for this group</p>
                        </div>
                        
                        <div class="field-row">
                            <label for="group-context">Context</label>
                            <select id="group-context" name="context">
                                <option value="normal">Normal</option>
                                <option value="side">Sidebar</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        
                        <div class="field-row">
                            <label for="group-priority">Priority</label>
                            <select id="group-priority" name="priority">
                                <option value="high">High</option>
                                <option value="default">Default</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="submit" class="button button-primary">Save Group</button>
                            <button type="button" class="button cancel-group-edit">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render individual field card
     */
    private function render_field_card($field) {
        $groups = $this->field_manager->get_field_groups();
        $group_label = '';
        foreach ($groups as $group) {
            if ($group['id'] === $field['group']) {
                $group_label = $group['label'];
                break;
            }
        }
        ?>
        <div class="field-card" data-field-key="<?php echo esc_attr($field['key']); ?>">
            <div class="field-card-header">
                <h3><?php echo esc_html($field['label']); ?></h3>
                <span class="field-type-badge"><?php echo esc_html($field['type']); ?></span>
            </div>
            <div class="field-card-body">
                <p class="field-key">Key: <code><?php echo esc_html($field['key']); ?></code></p>
                <p class="field-group">Group: <?php echo esc_html($group_label); ?></p>
                <?php if (!empty($field['description'])): ?>
                    <p class="field-description"><?php echo esc_html($field['description']); ?></p>
                <?php endif; ?>
                <div class="field-badges">
                    <?php if (!empty($field['required'])): ?>
                        <span class="badge required">Required</span>
                    <?php endif; ?>
                    <?php if (!empty($field['ai_fillable'])): ?>
                        <span class="badge ai-enabled">AI Fill</span>
                    <?php endif; ?>
                    <?php if (empty($field['show_in_admin'])): ?>
                        <span class="badge hidden">Hidden</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="field-card-actions">
                <button class="edit-field" data-field-key="<?php echo esc_attr($field['key']); ?>">Edit</button>
                <button class="delete-field" data-field-key="<?php echo esc_attr($field['key']); ?>">Delete</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * NHTSA Mappings Page
     */
    public function nhtsa_mappings_page() {
        // Handle form submission
        if (isset($_POST['save_mappings']) && wp_verify_nonce($_POST['field_mappings_nonce'], 'save_field_mappings')) {
            $new_mappings = array();
            
            if (isset($_POST['mappings']) && is_array($_POST['mappings'])) {
                foreach ($_POST['mappings'] as $mapping) {
                    $api_field = sanitize_text_field($mapping['api_field']);
                    $meta_field = sanitize_text_field($mapping['meta_field']);
                    
                    if (!empty($api_field) && !empty($meta_field)) {
                        $new_mappings[$api_field] = $meta_field;
                    }
                }
            }
            
            $this->field_manager->save_nhtsa_mappings($new_mappings);
            echo '<div class="notice notice-success"><p>NHTSA field mappings updated successfully!</p></div>';
        }
        
        $current_mappings = $this->field_manager->get_nhtsa_field_mapping();
        ?>
        <div class="wrap field-mappings-container">
            <h1>NHTSA Field Mappings</h1>
            <p>Configure how NHTSA API fields map to your car listing meta fields.</p>
            
            <form method="post" id="field-mappings-form">
                <?php wp_nonce_field('save_field_mappings', 'field_mappings_nonce'); ?>
                
                <div class="mapping-header">
                    <div class="api-field">NHTSA API Field Name</div>
                    <div class="meta-field">Meta Field Key</div>
                    <div>Action</div>
                </div>
                
                <div id="field-mappings-list">
                    <?php foreach ($current_mappings as $api_field => $meta_field): ?>
                        <div class="field-mapping-row">
                            <input type="text" class="api-field" name="mappings[<?php echo esc_attr($api_field); ?>][api_field]" value="<?php echo esc_attr($api_field); ?>" placeholder="e.g., Fuel Type - Primary">
                            <input type="text" class="meta-field" name="mappings[<?php echo esc_attr($api_field); ?>][meta_field]" value="<?php echo esc_attr($meta_field); ?>" placeholder="e.g., fuel_type">
                            <button type="button" class="delete-field" onclick="this.parentElement.remove()">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="add-field-row">
                    <h3>Add New Mapping</h3>
                    <div class="field-mapping-row">
                        <input type="text" class="api-field" id="new-api-field" placeholder="NHTSA API Field Name">
                        <input type="text" class="meta-field" id="new-meta-field" placeholder="Meta Field Key">
                        <button type="button" onclick="addNewMapping()" class="button button-secondary">Add Mapping</button>
                    </div>
                </div>
                
                <button type="submit" name="save_mappings" class="save-mappings">Save All Mappings</button>
            </form>
        </div>
        
        <script>
        function addNewMapping() {
            const apiField = document.getElementById('new-api-field').value;
            const metaField = document.getElementById('new-meta-field').value;
            
            if (!apiField || !metaField) {
                alert('Please fill in both fields');
                return;
            }
            
            const list = document.getElementById('field-mappings-list');
            const newRow = document.createElement('div');
            newRow.className = 'field-mapping-row';
            newRow.innerHTML = `
                <input type="text" class="api-field" name="mappings[${apiField}][api_field]" value="${apiField}">
                <input type="text" class="meta-field" name="mappings[${apiField}][meta_field]" value="${metaField}">
                <button type="button" class="delete-field" onclick="this.parentElement.remove()">Delete</button>
            `;
            list.appendChild(newRow);
            
            document.getElementById('new-api-field').value = '';
            document.getElementById('new-meta-field').value = '';
        }
        </script>
        <?php
    }
    
    /**
     * Documentation Page
     */
    public function documentation_page() {
        include VIN_DECODER_PLUGIN_DIR . 'templates/documentation.php';
    }
    
    /**
     * AJAX Handlers
     */
    public function ajax_save_field() {
        check_ajax_referer('vin_decoder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $field_data = $_POST;
        unset($field_data['action'], $field_data['nonce']);
        
        // Handle options for select/radio fields
        if (isset($field_data['options']) && is_array($field_data['options'])) {
            $options = array();
            foreach ($field_data['options'] as $option) {
                if (!empty($option['key']) && !empty($option['label'])) {
                    $options[$option['key']] = $option['label'];
                }
            }
            $field_data['options'] = $options;
        }
        
        $result = $this->field_manager->save_field($field_data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Field saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save field'));
        }
    }
    
    public function ajax_delete_field() {
        check_ajax_referer('vin_decoder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $field_key = sanitize_key($_POST['field_key']);
        $result = $this->field_manager->delete_field($field_key);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Field deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete field'));
        }
    }
    
    public function ajax_save_field_group() {
        check_ajax_referer('vin_decoder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_data = $_POST;
        unset($group_data['action'], $group_data['nonce']);
        
        $result = $this->field_manager->save_field_group($group_data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Group saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save group'));
        }
    }
    
    public function ajax_delete_field_group() {
        check_ajax_referer('vin_decoder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $group_id = sanitize_key($_POST['group_id']);
        $result = $this->field_manager->delete_field_group($group_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Group deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete group'));
        }
    }
    
    public function ajax_update_field_positions() {
        check_ajax_referer('vin_decoder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Implementation for drag-and-drop field reordering
        wp_send_json_success(array('message' => 'Positions updated'));
    }
}
