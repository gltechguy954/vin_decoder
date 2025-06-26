<?php
/**
 * Documentation Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap docs-container">
    <style>
        .docs-container { max-width: 1200px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .docs-header { background: linear-gradient(135deg, #0073aa, #005177); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
        .docs-header h1 { margin: 0; font-size: 2.5em; font-weight: 300; }
        .docs-header p { margin: 10px 0 0; font-size: 1.2em; opacity: 0.9; }
        .docs-nav { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .docs-nav h3 { margin-top: 0; color: #333; }
        .docs-nav ul { margin: 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: 15px; }
        .docs-nav li { background: white; border-radius: 4px; }
        .docs-nav a { display: block; padding: 10px 15px; text-decoration: none; color: #0073aa; font-weight: 500; border-radius: 4px; transition: background 0.2s; }
        .docs-nav a:hover { background: #0073aa; color: white; }
        .docs-section { background: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .docs-section h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 0; }
        .docs-section h3 { color: #333; margin-top: 25px; }
        .docs-section h4 { color: #666; margin-top: 20px; }
        .shortcode-example { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin: 15px 0; font-family: Monaco, Consolas, monospace; }
        .shortcode-example code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-weight: bold; color: #c7254e; }
        .shortcode-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .shortcode-table th, .shortcode-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .shortcode-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .shortcode-table code { background: #f8f9fa; padding: 3px 6px; border-radius: 3px; font-size: 0.9em; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .feature-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #0073aa; }
        .feature-card h4 { margin-top: 0; color: #0073aa; }
        .step-counter { background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
        .workflow-step { display: flex; align-items: flex-start; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .workflow-step-content { flex: 1; }
        .alert { padding: 15px; border-radius: 4px; margin: 15px 0; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
    </style>
    
    <div class="docs-header">
        <h1>🚗 VIN Decoder Plugin Documentation</h1>
        <p>Complete guide to using the VIN Decoder plugin with dynamic field management</p>
    </div>
    
    <div class="docs-nav">
        <h3>Quick Navigation</h3>
        <ul>
            <li><a href="#overview">Plugin Overview</a></li>
            <li><a href="#field-management">Field Management</a></li>
            <li><a href="#getting-started">Getting Started</a></li>
            <li><a href="#shortcodes">Shortcodes</a></li>
            <li><a href="#elementor">Elementor Integration</a></li>
            <li><a href="#ai-features">AI Features</a></li>
            <li><a href="#troubleshooting">Troubleshooting</a></li>
        </ul>
    </div>
    
    <div id="overview" class="docs-section">
        <h2>📋 Plugin Overview</h2>
        <p>The VIN Decoder plugin is a powerful tool designed for automotive websites, car dealerships, and vehicle listing platforms. It automatically decodes Vehicle Identification Numbers (VINs) using the official NHTSA database and creates comprehensive car listings with detailed specifications.</p>
        
        <div class="feature-grid">
            <div class="feature-card">
                <h4>🔧 Dynamic Field Management</h4>
                <p>Add, edit, and remove custom fields without touching code. Full control over field types, groups, and display options.</p>
            </div>
            <div class="feature-card">
                <h4>🔍 NHTSA Integration</h4>
                <p>Connects directly to the National Highway Traffic Safety Administration database for accurate, official vehicle data.</p>
            </div>
            <div class="feature-card">
                <h4>🎨 Elementor Ready</h4>
                <p>All vehicle data fields are available in Elementor's dynamic content system for easy page building.</p>
            </div>
            <div class="feature-card">
                <h4>🤖 AI-Powered</h4>
                <p>Fill missing data automatically using AI search integration with SerpAPI, Google, or OpenAI.</p>
            </div>
        </div>
    </div>
    
    <div id="field-management" class="docs-section">
        <h2>🔧 Field Management System</h2>
        <p>The Field Manager allows you to completely control your car listing fields without writing any code.</p>
        
        <h3>Key Features</h3>
        <ul>
            <li><strong>Visual Field Editor:</strong> Add and configure fields through an intuitive interface</li>
            <li><strong>Field Types:</strong> Text, Number, Textarea, Select, Radio, Checkbox, and Feature Checkboxes</li>
            <li><strong>Field Groups:</strong> Organize fields into logical groups (Specifications, Pricing, Features, etc.)</li>
            <li><strong>Drag & Drop:</strong> Reorder fields and groups easily</li>
            <li><strong>Dynamic Options:</strong> Configure dropdown options for select fields</li>
            <li><strong>AI Integration:</strong> Mark fields as AI-fillable for automatic data population</li>
        </ul>
        
        <h3>Adding a New Field</h3>
        <div class="workflow-step">
            <span class="step-counter">1</span>
            <div class="workflow-step-content">
                <h4>Open Field Manager</h4>
                <p>Navigate to <strong>VIN Decoder → Field Manager</strong> in your WordPress admin.</p>
            </div>
        </div>
        
        <div class="workflow-step">
            <span class="step-counter">2</span>
            <div class="workflow-step-content">
                <h4>Click "Add New Field"</h4>
                <p>Click the blue "Add New Field" button in the top right of the fields section.</p>
            </div>
        </div>
        
        <div class="workflow-step">
            <span class="step-counter">3</span>
            <div class="workflow-step-content">
                <h4>Configure Field Settings</h4>
                <p>Fill in the field details:</p>
                <ul>
                    <li><strong>Field Key:</strong> Unique identifier (e.g., "odometer")</li>
                    <li><strong>Field Label:</strong> Display name (e.g., "Odometer")</li>
                    <li><strong>Field Type:</strong> Choose appropriate type</li>
                    <li><strong>Field Group:</strong> Select which group it belongs to</li>
                    <li><strong>Options:</strong> Configure any additional settings</li>
                </ul>
            </div>
        </div>
        
        <div class="workflow-step">
            <span class="step-counter">4</span>
            <div class="workflow-step-content">
                <h4>Save Field</h4>
                <p>Click "Save Field" to create the field. It will immediately appear in the edit screen for car listings.</p>
            </div>
        </div>
        
        <div class="alert alert-info">
            <strong>💡 Pro Tip:</strong> When you add "odometer" as a field key, you can immediately use it as <code>[car_odometer]</code> shortcode and it will appear in the car listing edit screen!
        </div>
    </div>
    
    <div id="getting-started" class="docs-section">
        <h2>🚀 Getting Started</h2>
        
        <div class="workflow-step">
            <span class="step-counter">1</span>
            <div class="workflow-step-content">
                <h4>Decode a VIN</h4>
                <p>Go to <strong>VIN Decoder</strong> in your WordPress admin and enter a 17-character VIN number.</p>
            </div>
        </div>
        
        <div class="workflow-step">
            <span class="step-counter">2</span>
            <div class="workflow-step-content">
                <h4>Create Car Listing</h4>
                <p>Click "Create Car Listing" to automatically generate a post with all decoded data mapped to your fields.</p>
            </div>
        </div>
        
        <div class="workflow-step">
            <span class="step-counter">3</span>
            <div class="workflow-step-content">
                <h4>Edit & Enhance</h4>
                <p>Edit the listing to add any missing information. Use AI fill buttons to automatically populate fields.</p>
            </div>
        </div>
    </div>
    
    <div id="shortcodes" class="docs-section">
        <h2>📝 Dynamic Shortcodes</h2>
        <p>Every field you create automatically gets its own shortcode following the pattern <code>[car_field_key]</code>.</p>
        
        <h3>Basic Usage</h3>
        <div class="shortcode-example">
            <p>If you have a field with key "odometer", use it as:</p>
            <code>[car_odometer]</code>
            
            <p>With formatting options:</p>
            <code>[car_price currency="€" decimals="0"]</code><br>
            <code>[car_odometer format="number" after=" miles"]</code>
        </div>
        
        <h3>Shortcode Parameters</h3>
        <table class="shortcode-table">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>format</code></td>
                    <td>Format the output (number, html)</td>
                    <td><code>format="number"</code></td>
                </tr>
                <tr>
                    <td><code>default</code></td>
                    <td>Default text if field is empty</td>
                    <td><code>default="N/A"</code></td>
                </tr>
                <tr>
                    <td><code>before</code></td>
                    <td>Text to add before the value</td>
                    <td><code>before="$"</code></td>
                </tr>
                <tr>
                    <td><code>after</code></td>
                    <td>Text to add after the value</td>
                    <td><code>after=" miles"</code></td>
                </tr>
                <tr>
                    <td><code>currency</code></td>
                    <td>Currency symbol for price fields</td>
                    <td><code>currency="€"</code></td>
                </tr>
                <tr>
                    <td><code>decimals</code></td>
                    <td>Number of decimal places</td>
                    <td><code>decimals="0"</code></td>
                </tr>
            </tbody>
        </table>
        
        <h3>Special Shortcodes</h3>
        <table class="shortcode-table">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Parameters</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[carfax_link]</code></td>
                    <td>Generates Carfax report link</td>
                    <td>text, class, target</td>
                </tr>
                <tr>
                    <td><code>[car_gallery]</code></td>
                    <td>Displays car photo gallery</td>
                    <td>columns, size, class</td>
                </tr>
                <tr>
                    <td><code>[car_features_list]</code></td>
                    <td>Lists all features</td>
                    <td>group, show_only, columns</td>
                </tr>
                <tr>
                    <td><code>[car_specifications_table]</code></td>
                    <td>Full specifications table</td>
                    <td>groups, exclude, class</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div id="elementor" class="docs-section">
        <h2>🎨 Elementor Integration</h2>
        <p>All fields are automatically registered for Elementor's dynamic content system.</p>
        
        <h3>Using Dynamic Content</h3>
        <ol>
            <li>Add any Text, Heading, or similar widget</li>
            <li>Click the dynamic content icon (database symbol)</li>
            <li>Select "Post Meta"</li>
            <li>Enter your field key (e.g., "odometer", "price", etc.)</li>
        </ol>
        
        <div class="alert alert-success">
            <strong>✅ Remember:</strong> Use the field key without the "car_" prefix in Elementor. If your shortcode is [car_odometer], use "odometer" in Elementor.
        </div>
    </div>
    
    <div id="ai-features" class="docs-section">
        <h2>🤖 AI-Powered Features</h2>
        
        <h3>Setting Up AI Integration</h3>
        <ol>
            <li>Go to <strong>VIN Decoder → AI Settings</strong></li>
            <li>Choose your preferred search method (SerpAPI recommended)</li>
            <li>Enter your API credentials</li>
            <li>Save settings</li>
        </ol>
        
        <h3>Using AI Fill</h3>
        <p>When editing a car listing, look for the "🤖 Fill with AI" button next to eligible fields. Click it to automatically search for and populate the field value.</p>
        
        <h3>Making Fields AI-Fillable</h3>
        <p>When creating or editing a field in the Field Manager, check the "Enable AI fill button" option to make it AI-fillable.</p>
    </div>
    
    <div id="troubleshooting" class="docs-section">
        <h2>🛠️ Troubleshooting</h2>
        
        <h3>Field Not Appearing</h3>
        <ul>
            <li>Ensure "Show in admin edit screen" is checked in field settings</li>
            <li>Check that the field is assigned to a valid group</li>
            <li>Clear your browser cache and refresh</li>
        </ul>
        
        <h3>Shortcode Not Working</h3>
        <ul>
            <li>Verify you're using the correct format: <code>[car_field_key]</code></li>
            <li>Ensure you're on a car listing post</li>
            <li>Check that the field has a value saved</li>
        </ul>
        
        <h3>AI Fill Not Working</h3>
        <ul>
            <li>Verify API credentials are correct in AI Settings</li>
            <li>Ensure the vehicle has Year, Make, and Model populated</li>
            <li>Check your API usage limits</li>
        </ul>
        
        <div class="alert alert-warning">
            <strong>⚠️ Important:</strong> After adding new fields, you may need to re-save permalinks by going to Settings → Permalinks and clicking "Save Changes".
        </div>
    </div>
</div>
