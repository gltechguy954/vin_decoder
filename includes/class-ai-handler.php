<?php
/**
 * AI Handler Class - Handles AI-powered field filling
 */
class VIN_Decoder_AI_Handler {
    
    private $field_manager;
    
    public function __construct() {
        $this->field_manager = new VIN_Decoder_Field_Manager();
    }
    
    public function init() {
        // AJAX handler for AI field filling
        add_action('wp_ajax_fill_field_with_ai', array($this, 'fill_field_with_ai_ajax'));
    }
    
    /**
     * Settings page for AI configuration
     */
    public function settings_page() {
        // Handle form submission
        if (isset($_POST['save_ai_settings']) && wp_verify_nonce($_POST['ai_settings_nonce'], 'save_ai_settings')) {
            update_option('vin_decoder_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            update_option('vin_decoder_serp_api_key', sanitize_text_field($_POST['serp_api_key']));
            update_option('vin_decoder_google_cse_id', sanitize_text_field($_POST['google_cse_id']));
            update_option('vin_decoder_google_api_key', sanitize_text_field($_POST['google_api_key']));
            update_option('vin_decoder_ai_search_method', sanitize_text_field($_POST['ai_search_method']));
            echo '<div class="notice notice-success"><p>AI settings saved successfully!</p></div>';
        }
        
        $openai_key = get_option('vin_decoder_openai_api_key', '');
        $serp_key = get_option('vin_decoder_serp_api_key', '');
        $google_cse_id = get_option('vin_decoder_google_cse_id', '');
        $google_api_key = get_option('vin_decoder_google_api_key', '');
        $search_method = get_option('vin_decoder_ai_search_method', 'serp');
        
        ?>
        <div class="wrap">
            <h1>🤖 AI Settings</h1>
            <p>Configure AI and search API settings for auto-filling missing vehicle data.</p>
            
            <form method="post">
                <?php wp_nonce_field('save_ai_settings', 'ai_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Search Method</th>
                        <td>
                            <select name="ai_search_method">
                                <option value="serp" <?php selected($search_method, 'serp'); ?>>SerpAPI (Recommended)</option>
                                <option value="google" <?php selected($search_method, 'google'); ?>>Google Custom Search</option>
                                <option value="openai" <?php selected($search_method, 'openai'); ?>>OpenAI (GPT)</option>
                            </select>
                            <p class="description">Choose your preferred method for searching vehicle data.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">SerpAPI Key</th>
                        <td>
                            <input type="password" name="serp_api_key" value="<?php echo esc_attr($serp_key); ?>" class="regular-text" />
                            <p class="description">Get your free API key from <a href="https://serpapi.com" target="_blank">SerpAPI.com</a> (1000 free searches/month)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="password" name="openai_api_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" />
                            <p class="description">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Google API Key</th>
                        <td>
                            <input type="password" name="google_api_key" value="<?php echo esc_attr($google_api_key); ?>" class="regular-text" />
                            <p class="description">Get your API key from <a href="https://console.developers.google.com" target="_blank">Google Cloud Console</a></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Google Custom Search Engine ID</th>
                        <td>
                            <input type="text" name="google_cse_id" value="<?php echo esc_attr($google_cse_id); ?>" class="regular-text" />
                            <p class="description">Create a custom search engine at <a href="https://cse.google.com" target="_blank">Google CSE</a></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_ai_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
            
            <div class="alert alert-info">
                <h3>🔍 How AI Fill Works</h3>
                <p>When you click "Fill with AI" next to a field, the plugin searches for vehicle data and extracts the answer automatically.</p>
                
                <h4>API Recommendations:</h4>
                <ul>
                    <li><strong>SerpAPI:</strong> Best accuracy, 1000 free searches/month</li>
                    <li><strong>OpenAI:</strong> Very accurate, requires API credits</li>
                    <li><strong>Google CSE:</strong> Good accuracy, 100 free searches/day</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for AI field filling
     */
    public function fill_field_with_ai_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vin_decoder_nonce')) {
            wp_die('Security check failed');
        }
        
        $field_name = sanitize_text_field($_POST['field_name']);
        $post_id = intval($_POST['post_id']);
        
        // Get vehicle details
        $year = get_post_meta($post_id, 'year', true);
        $make = get_post_meta($post_id, 'make', true);
        $model = get_post_meta($post_id, 'model', true);
        $trim = get_post_meta($post_id, 'trim', true);
        
        if (empty($year) || empty($make) || empty($model)) {
            wp_send_json_error('Missing vehicle information');
            return;
        }
        
        // Search for the field value
        $search_result = $this->search_vehicle_data($year, $make, $model, $trim, $field_name);
        
        if ($search_result) {
            wp_send_json_success(array('value' => $search_result));
        } else {
            wp_send_json_error('No data found');
        }
    }
    
    /**
     * Search for vehicle data using configured API
     */
    private function search_vehicle_data($year, $make, $model, $trim, $field_name) {
        $search_method = get_option('vin_decoder_ai_search_method', 'serp');
        $search_query = $this->build_search_query($year, $make, $model, $trim, $field_name);
        
        switch ($search_method) {
            case 'serp':
                return $this->search_with_serpapi($search_query, $field_name);
            case 'openai':
                return $this->search_with_openai($year, $make, $model, $trim, $field_name);
            case 'google':
                return $this->search_with_google_cse($search_query, $field_name);
            default:
                return false;
        }
    }
    
    /**
     * Build search query
     */
    private function build_search_query($year, $make, $model, $trim, $field_name) {
        $vehicle_name = trim("$year $make $model $trim");
        
        $field_queries = array(
            'horsepower' => "$vehicle_name horsepower hp",
            'torque' => "$vehicle_name torque lb-ft",
            'zero_to_sixty' => "$vehicle_name 0-60 mph time acceleration",
            'engine_configuration' => "$vehicle_name engine type configuration",
            'transmission' => "$vehicle_name transmission type",
            'fuel_type' => "$vehicle_name fuel type gas diesel electric",
            'drive_type' => "$vehicle_name drivetrain FWD RWD AWD 4WD",
            'seating_capacity' => "$vehicle_name seating capacity seats",
            'mpg_gas_equivalent' => "$vehicle_name MPG fuel economy",
            'estimated_electric_range' => "$vehicle_name electric range miles",
            'exterior_color' => "$vehicle_name exterior colors available",
            'interior_color' => "$vehicle_name interior colors available",
            'cargo_space' => "$vehicle_name cargo space cubic feet",
        );
        
        return isset($field_queries[$field_name]) ? $field_queries[$field_name] : "$vehicle_name $field_name";
    }
    
    /**
     * Search using SerpAPI
     */
    private function search_with_serpapi($query, $field_name) {
        $api_key = get_option('vin_decoder_serp_api_key');
        if (empty($api_key)) {
            return false;
        }
        
        $url = "https://serpapi.com/search.json?" . http_build_query(array(
            'engine' => 'google',
            'q' => $query,
            'api_key' => $api_key,
            'num' => 5
        ));
        
        $response = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $this->extract_value_from_search_results($data, $field_name);
    }
    
    /**
     * Search using OpenAI
     */
    private function search_with_openai($year, $make, $model, $trim, $field_name) {
        $api_key = get_option('vin_decoder_openai_api_key');
        if (empty($api_key)) {
            return false;
        }
        
        $vehicle_name = trim("$year $make $model $trim");
        $prompt = $this->build_openai_prompt($vehicle_name, $field_name);
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => 50,
                'temperature' => 0.1
            ))
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
        
        return false;
    }
    
    /**
     * Search using Google Custom Search
     */
    private function search_with_google_cse($query, $field_name) {
        $api_key = get_option('vin_decoder_google_api_key');
        $cse_id = get_option('vin_decoder_google_cse_id');
        
        if (empty($api_key) || empty($cse_id)) {
            return false;
        }
        
        $url = "https://www.googleapis.com/customsearch/v1?" . http_build_query(array(
            'key' => $api_key,
            'cx' => $cse_id,
            'q' => $query,
            'num' => 5
        ));
        
        $response = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['items'])) {
            $text = '';
            foreach ($data['items'] as $item) {
                $text .= ' ' . (isset($item['snippet']) ? $item['snippet'] : '');
            }
            return $this->extract_field_value($text, $field_name);
        }
        
        return false;
    }
    
    /**
     * Build OpenAI prompt
     */
    private function build_openai_prompt($vehicle_name, $field_name) {
        $prompts = array(
            'horsepower' => "What is the horsepower of the $vehicle_name? Respond with just the number.",
            'torque' => "What is the torque of the $vehicle_name? Respond with just the number and unit (e.g., 300 lb-ft).",
            'zero_to_sixty' => "What is the 0-60 mph time of the $vehicle_name? Respond with just the time in seconds.",
            'transmission' => "What transmission type does the $vehicle_name have? Respond briefly.",
            'fuel_type' => "What fuel type does the $vehicle_name use? Respond with: gasoline, diesel, electric, hybrid, or plug_in_hybrid.",
            'drive_type' => "What drivetrain does the $vehicle_name have? Respond with: fwd, rwd, awd, or 4wd.",
            'seating_capacity' => "How many seats does the $vehicle_name have? Respond with just the number.",
            'cargo_space' => "What is the cargo space of the $vehicle_name in cubic feet? Respond with just the number.",
        );
        
        return isset($prompts[$field_name]) ? $prompts[$field_name] : "What is the $field_name of the $vehicle_name? Respond briefly.";
    }
    
    /**
     * Extract value from search results
     */
    private function extract_value_from_search_results($data, $field_name) {
        if (!isset($data['organic_results'])) {
            return false;
        }
        
        $text = '';
        foreach ($data['organic_results'] as $result) {
            $text .= ' ' . (isset($result['snippet']) ? $result['snippet'] : '');
            $text .= ' ' . (isset($result['title']) ? $result['title'] : '');
        }
        
        return $this->extract_field_value($text, $field_name);
    }
    
    /**
     * Extract field value from text
     */
    private function extract_field_value($text, $field_name) {
        $patterns = array(
            'horsepower' => '/(\d+)\s*(?:hp|horsepower|bhp)/i',
            'torque' => '/(\d+)\s*(?:lb-?ft|pound-?feet)/i',
            'zero_to_sixty' => '/(?:0-60|zero.to.sixty).*?(\d+\.?\d*)\s*(?:seconds?|sec)/i',
            'seating_capacity' => '/(\d+)\s*(?:seat|passenger)/i',
            'cargo_space' => '/(\d+\.?\d*)\s*(?:cubic feet|cu\.?\s*ft)/i',
        );
        
        if (isset($patterns[$field_name])) {
            if (preg_match($patterns[$field_name], $text, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
}
