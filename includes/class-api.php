<?php
/**
 * Cost Calculator REST API Management Class
 *
 * Handles all REST API endpoints for the Cost Calculator plugin.
 * Provides secure, well-documented API endpoints for annotation types,
 * FAQ items, settings, and quote requests following WordPress standards.
 *
 * @package CostCalculator
 * @since   2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access denied.' );
}

/**
 * REST API management class for Cost Calculator
 *
 * @since 2.0.0
 */
final class CostCalc_API {

    /**
     * API instance - Singleton pattern
     *
     * @since 2.0.0
     * @var   CostCalc_API|null
     */
    private static $instance = null;

    /**
     * API namespace for all endpoints
     *
     * @since 2.0.0
     * @var   string
     */
    private $namespace = 'cost-calc/v1';

    /**
     * Get API instance - Singleton pattern
     *
     * @since  2.0.0
     * @return CostCalc_API
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize API functionality
     *
     * @since 2.0.0
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register all REST API routes for the Cost Calculator
     *
     * @since 2.0.0
     */
    public function register_routes() {
        // Public cost calculation types endpoint (active only)
        register_rest_route( $this->namespace, '/annotation-types', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_annotation_types' ),
            'permission_callback' => '__return_true',
        ) );
        
        register_rest_route($this->namespace, '/annotation-types/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_annotation_type'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/annotation-types/(?P<id>[a-zA-Z0-9-]+)/toggle', array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_annotation_type'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/annotation-types/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_annotation_type'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // Settings endpoints
        register_rest_route($this->namespace, '/contact-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_contact_settings'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/important-notes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_important_notes'),
            'permission_callback' => '__return_true'
        ));
        
        // Admin-only endpoints
        register_rest_route($this->namespace, '/annotation-types/admin', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_annotation_types'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/faq/admin', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_all_faq_items'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // FAQ endpoints
        register_rest_route($this->namespace, '/faq', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_faq_items'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/faq/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_faq_item'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/faq/(?P<id>[a-zA-Z0-9-]+)/toggle', array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_faq_item'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/faq/(?P<id>[a-zA-Z0-9-]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_faq_item'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/faq/reorder', array(
            'methods' => 'POST',
            'callback' => array($this, 'reorder_faq_items'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // Quote requests
        register_rest_route($this->namespace, '/quote-request', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_quote_request'),
            'permission_callback' => '__return_true'
        ));
        
        // Appearance settings
        register_rest_route($this->namespace, '/appearance-settings', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_appearance_settings'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        // Public settings endpoints (GET) + Admin settings endpoints (POST)
        register_rest_route($this->namespace, '/important-notes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_important_notes'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/important-notes', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_important_notes'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/contact-settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_contact_settings'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route($this->namespace, '/contact-settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_contact_settings'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
        
        register_rest_route($this->namespace, '/site-settings', array(
            'methods' => array('GET', 'POST'),
            'callback' => array($this, 'handle_site_settings'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }
    
    public function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    public function get_annotation_types($request) {
        global $wpdb;
        
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        // Only return ACTIVE annotation types for public site
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY name ASC",
            ARRAY_A
        );
        
        error_log("Public API - Active annotation types found: " . count($results));
        
        if ($wpdb->last_error) {
            error_log("Public API - Database error: " . $wpdb->last_error);
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        // Convert language_tiers JSON string back to object
        foreach ($results as &$result) {
            if (!empty($result['language_tiers'])) {
                $result['language_tiers'] = json_decode($result['language_tiers'], true);
            } else {
                $result['language_tiers'] = null;
            }
            
            // Convert string values to appropriate types
            $result['rate'] = floatval($result['rate']);
            $result['alt_rate'] = !empty($result['alt_rate']) ? floatval($result['alt_rate']) : null;
            $result['is_image_based'] = intval($result['is_image_based']) === 1;
            $result['is_active'] = intval($result['is_active']) === 1;
        }
        
        // Disable caching for this endpoint
        nocache_headers();
        
        return rest_ensure_response($results);
    }
    
    public function get_all_annotation_types($request) {
        global $wpdb;
        
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        // Return ALL annotation types for admin panel
        $results = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY name ASC",
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        // Convert language_tiers JSON string back to object
        foreach ($results as &$result) {
            if (!empty($result['language_tiers'])) {
                $result['language_tiers'] = json_decode($result['language_tiers'], true);
            } else {
                $result['language_tiers'] = null;
            }
            
            // Convert string values to appropriate types
            $result['rate'] = floatval($result['rate']);
            $result['alt_rate'] = !empty($result['alt_rate']) ? floatval($result['alt_rate']) : null;
            $result['is_image_based'] = intval($result['is_image_based']) === 1;
            $result['is_active'] = intval($result['is_active']) === 1;
        }
        
        return rest_ensure_response($results);
    }
    
    public function update_annotation_type($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $data = $request->get_json_params();
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        error_log('Updating annotation type: ' . $id);
        error_log('Data received: ' . print_r($data, true));
        
        // Check if annotation type exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %s",
            $id
        ));
        
        if ($exists == 0) {
            return new WP_Error('not_found', 'Annotation type not found', array('status' => 404));
        }
        
        // Prepare data for update
        $update_data = array();
        
        if (isset($data['name'])) $update_data['name'] = sanitize_text_field($data['name']);
        if (isset($data['description'])) $update_data['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['rate'])) $update_data['rate'] = floatval($data['rate']);
        if (isset($data['unit'])) $update_data['unit'] = sanitize_text_field($data['unit']);
        if (isset($data['alt_rate'])) $update_data['alt_rate'] = !empty($data['alt_rate']) ? floatval($data['alt_rate']) : null;
        if (isset($data['alt_unit'])) $update_data['alt_unit'] = !empty($data['alt_unit']) ? sanitize_text_field($data['alt_unit']) : null;
        if (isset($data['is_image_based'])) $update_data['is_image_based'] = !empty($data['is_image_based']) ? 1 : 0;
        if (isset($data['is_active'])) $update_data['is_active'] = !empty($data['is_active']) ? 1 : 0;
        if (isset($data['language_tiers'])) $update_data['language_tiers'] = !empty($data['language_tiers']) ? json_encode($data['language_tiers']) : null;
        
        if (empty($update_data)) {
            return new WP_Error('no_data', 'No data provided for update', array('status' => 400));
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id)
        );
        
        error_log('Update result: ' . ($result !== false ? 'Success' : 'Failed'));
        error_log('SQL Error: ' . $wpdb->last_error);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to update annotation type: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Annotation type updated successfully',
            'rows_affected' => $result
        ));
    }
    
    public function toggle_annotation_type($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        error_log('Toggling annotation type: ' . $id);
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $table_name WHERE id = %s",
            $id
        ));
        
        if ($current_status === null) {
            return new WP_Error('not_found', 'Annotation type not found', array('status' => 404));
        }
        
        // Toggle status
        $new_status = $current_status == 1 ? 0 : 1;
        
        $result = $wpdb->update(
            $table_name,
            array('is_active' => $new_status),
            array('id' => $id)
        );
        
        error_log('Toggle result: ' . ($result !== false ? 'Success' : 'Failed'));
        error_log('New status: ' . $new_status);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to toggle annotation type: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Annotation type toggled successfully',
            'new_status' => $new_status == 1,
            'rows_affected' => $result
        ));
    }
    
    public function delete_annotation_type($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        error_log('Deleting annotation type: ' . $id);
        
        $result = $wpdb->delete($table_name, array('id' => $id));
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to delete annotation type: ' . $wpdb->last_error, array('status' => 500));
        }
        
        if ($result === 0) {
            return new WP_Error('not_found', 'Annotation type not found', array('status' => 404));
        }
        
        error_log('Delete result: Success - ' . $result . ' rows affected');
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Annotation type deleted successfully'
        ));
    }
    
    public function get_contact_settings($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        $settings = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
            'contact_settings'
        ));
        
        if (!$settings) {
            // Return default settings
            return rest_ensure_response(array(
                'contactEnabled' => true,
                'buttonText' => "Let's Talk",
                'buttonUrl' => 'https://calendly.com/hannah_tailjay/30min',
                'title' => 'Still have questions?',
                'description' => 'Our team is here to help you understand our services and find the right solution for your project.'
            ));
        }
        
        return rest_ensure_response(json_decode($settings, true));
    }
    
    public function get_important_notes($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        $settings = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
            'important_notes'
        ));
        
        if (!$settings) {
            // Return default settings - OFFICIAL CONTENT
            return rest_ensure_response(array(
                'enabled' => true,
                'title' => 'Important Notes',
                'content' => '<p><strong>Platform Requirements:</strong> Clients provide annotation platform access or DeeLab can suggest/set up platforms as a separate project.</p><p><strong>Data Access:</strong> Clients provide data via cloud folder with access rights or connect cloud to annotation platform.</p>'
            ));
        }
        
        return rest_ensure_response(json_decode($settings, true));
    }
    
    public function get_faq_items($request) {
        global $wpdb;
        
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        $results = $wpdb->get_results(
            "SELECT * FROM $faq_table WHERE is_active = 1 ORDER BY category ASC, order_num ASC",
            ARRAY_A
        );
        
        return rest_ensure_response($results ? $results : array());
    }
    
    public function get_all_faq_items($request) {
        global $wpdb;
        
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        $results = $wpdb->get_results(
            "SELECT * FROM $faq_table ORDER BY order_num ASC, question ASC",
            ARRAY_A
        );
        
        if ($wpdb->last_error) {
            return new WP_Error('db_error', 'Database error: ' . $wpdb->last_error, array('status' => 500));
        }
        
        // Convert string values to appropriate types
        foreach ($results as &$result) {
            $result['order_num'] = intval($result['order_num']);
            $result['is_active'] = intval($result['is_active']) === 1;
        }
        
        return rest_ensure_response($results);
    }
    
    public function submit_quote_request($request) {
        global $wpdb;
        
        $data = $request->get_json_params();
        $quote_table = CostCalc_Database::get_table_name('cost_calc_quotes');
        
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'company' => !empty($data['company']) ? sanitize_text_field($data['company']) : null,
            'message' => !empty($data['message']) ? sanitize_textarea_field($data['message']) : null,
            'selected_types' => json_encode($data['selected_types']),
            'total_cost' => floatval($data['total_cost']),
            'status' => 'pending'
        );
        
        $result = $wpdb->insert($quote_table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to submit quote request: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Quote request submitted successfully'
        ));
    }
    
    public function handle_appearance_settings($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        
        if ($request->get_method() === 'POST') {
            // Save appearance settings
            $settings = json_decode($request->get_body(), true);
            
            if (!$settings) {
                return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
            }
            
            $settings_json = json_encode($settings);
            
            $result = $wpdb->replace(
                $settings_table,
                array(
                    'setting_key' => 'appearance_settings',
                    'setting_value' => $settings_json
                ),
                array('%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', 'Failed to save appearance settings: ' . $wpdb->last_error, array('status' => 500));
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Appearance settings saved successfully'
            ));
            
        } else {
            // Get appearance settings
            $settings = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'appearance_settings'
            ));
            
            if (!$settings) {
                // Return default settings
                return rest_ensure_response(array(
                    'primaryColor' => '#2563eb',
                    'secondaryColor' => '#64748b',
                    'backgroundColor' => '#ffffff',
                    'textColor' => '#1f2937',
                    'cardBackground' => '#f8fafc',
                    'fontFamily' => 'system',
                    'fontSize' => '16px',
                    'containerWidth' => '1000px',
                    'cardSpacing' => 'normal',
                    'borderRadius' => '8px',
                    'customCss' => ''
                ));
            }
            
            return rest_ensure_response(json_decode($settings, true));
        }
    }
    


    public function save_important_notes($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        
        // Save important notes settings
        $settings = json_decode($request->get_body(), true);
        
        if (!$settings) {
            return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
        }
        
        $settings_json = json_encode($settings);
        
        $result = $wpdb->replace(
            $settings_table,
            array(
                'setting_key' => 'important_notes',
                'setting_value' => $settings_json
            ),
            array('%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save important notes: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Important notes saved successfully'
        ));
    }
    


    public function save_contact_settings($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        
        // Save contact settings
        $settings = json_decode($request->get_body(), true);
        
        if (!$settings) {
            return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
        }
        
        $settings_json = json_encode($settings);
        
        $result = $wpdb->replace(
            $settings_table,
            array(
                'setting_key' => 'contact_settings',
                'setting_value' => $settings_json
            ),
            array('%s', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save contact settings: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Contact settings saved successfully'
        ));
    }
    
    public function handle_site_settings($request) {
        global $wpdb;
        
        $settings_table = CostCalc_Database::get_table_name('cost_calc_settings');
        
        if ($request->get_method() === 'POST') {
            // Save site settings
            $settings = json_decode($request->get_body(), true);
            
            if (!$settings) {
                return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
            }
            
            $settings_json = json_encode($settings);
            
            $result = $wpdb->replace(
                $settings_table,
                array(
                    'setting_key' => 'site_settings',
                    'setting_value' => $settings_json
                ),
                array('%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('db_error', 'Failed to save site settings: ' . $wpdb->last_error, array('status' => 500));
            }
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Site settings saved successfully'
            ));
            
        } else {
            // Get site settings
            $settings = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'site_settings'
            ));
            
            if (!$settings) {
                // Return default settings
                return rest_ensure_response(array(
                    'showCalculatorHeader' => true,
                    'calculatorTitle' => 'Cost Calculator',
                    'calculatorDescription' => 'Get instant pricing estimates for your data annotation projects.',
                    'notificationEmails' => '',
                    'theme' => 'light',
                    'enableAnimations' => true
                ));
            }
            
            return rest_ensure_response(json_decode($settings, true));
        }
    }
    
    public function handle_faq_items($request) {
        if ($request->get_method() === 'POST') {
            return $this->create_faq_item($request);
        } else {
            return $this->get_faq_items($request);
        }
    }
    
    public function create_faq_item($request) {
        global $wpdb;
        
        $data = $request->get_json_params();
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        $insert_data = array(
            'id' => sanitize_text_field($data['id']),
            'question' => sanitize_text_field($data['question']),
            'answer' => sanitize_textarea_field($data['answer']),
            'category' => sanitize_text_field($data['category']),
            'order_num' => intval($data['order']),
            'is_active' => $data['isActive'] ? 1 : 0
        );
        
        $result = $wpdb->replace($faq_table, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save FAQ item: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'FAQ item saved successfully'
        ));
    }
    
    public function update_faq_item($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $data = $request->get_json_params();
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        $update_data = array(
            'question' => sanitize_text_field($data['question']),
            'answer' => sanitize_textarea_field($data['answer']),
            'category' => sanitize_text_field($data['category']),
            'order_num' => intval($data['order']),
            'is_active' => $data['isActive'] ? 1 : 0
        );
        
        $result = $wpdb->update(
            $faq_table,
            $update_data,
            array('id' => $id),
            array('%s', '%s', '%s', '%d', '%d'),
            array('%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to update FAQ item: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'FAQ item updated successfully'
        ));
    }
    
    public function toggle_faq_item($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $faq_table WHERE id = %s",
            $id
        ));
        
        if ($current_status === null) {
            return new WP_Error('not_found', 'FAQ item not found', array('status' => 404));
        }
        
        $new_status = $current_status == 1 ? 0 : 1;
        
        $result = $wpdb->update(
            $faq_table,
            array('is_active' => $new_status),
            array('id' => $id),
            array('%d'),
            array('%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to toggle FAQ item: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'FAQ item status updated successfully',
            'new_status' => $new_status == 1
        ));
    }
    
    public function delete_faq_item($request) {
        global $wpdb;
        
        $id = $request->get_param('id');
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        $result = $wpdb->delete(
            $faq_table,
            array('id' => $id),
            array('%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to delete FAQ item: ' . $wpdb->last_error, array('status' => 500));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'FAQ item deleted successfully'
        ));
    }
    
    public function reorder_faq_items($request) {
        global $wpdb;
        
        $data = json_decode($request->get_body(), true);
        $items = $data['items'];
        
        if (!$items || !is_array($items)) {
            return new WP_Error('invalid_data', 'Invalid items data', array('status' => 400));
        }
        
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        foreach ($items as $item) {
            $wpdb->update(
                $faq_table,
                array('order_num' => $item['order']),
                array('id' => $item['id']),
                array('%d'),
                array('%s')
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'FAQ order updated successfully'
        ));
    }
}