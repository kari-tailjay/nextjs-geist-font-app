<?php
/**
 * Cost Calculator Admin Management Class
 *
 * Handles the WordPress admin interface for the Cost Calculator plugin.
 * Provides comprehensive admin panel with annotation type management,
 * FAQ system, settings configuration, and appearance customization.
 *
 * @package CostCalculator
 * @since   2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access denied.' );
}

/**
 * Admin management class for Cost Calculator
 *
 * @since 2.0.0
 */
final class CostCalc_Admin {

    /**
     * Admin instance - Singleton pattern
     *
     * @since 2.0.0
     * @var   CostCalc_Admin|null
     */
    private static $instance = null;

    /**
     * Plugin capability required for admin access
     *
     * @since 2.0.0
     * @var   string
     */
    private $capability = 'manage_options';

    /**
     * Get admin instance - Singleton pattern
     *
     * @since  2.0.0
     * @return CostCalc_Admin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize admin functionality
     *
     * @since 2.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks for admin functionality
     *
     * @since 2.0.0
     */
    private function init_hooks() {
        // Admin menu and scripts
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Admin notices
        add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
        
        // Core AJAX endpoints for annotation types
        $this->register_cost_type_ajax();
        
        // FAQ management AJAX endpoints
        $this->register_faq_ajax();
        
        // Settings management AJAX endpoints
        $this->register_settings_ajax();
        
        // Appearance customization AJAX endpoints
        $this->register_appearance_ajax();
        
        // Register quotes management AJAX endpoints
        $this->register_quotes_ajax();
    }

    /**
     * Register annotation type AJAX endpoints
     *
     * @since 2.0.0
     */
    private function register_cost_type_ajax() {
        $annotation_actions = array(
            'save_cost_type',
            'delete_cost_type',
            'toggle_cost_type',
            'get_admin_cost_types',
        );

        foreach ( $annotation_actions as $action ) {
            add_action( "wp_ajax_{$action}", array( $this, "ajax_{$action}" ) );
        }
    }

    /**
     * Register FAQ management AJAX endpoints
     *
     * @since 2.0.0
     */
    private function register_faq_ajax() {
        $faq_actions = array(
            'save_faq_item',
            'delete_faq_item',
            'toggle_faq_item',
            'get_admin_faq_items',
            'reorder_faq_items',
            'update_faq_category',
            'save_faq_settings',
            'get_faq_settings',
        );

        foreach ( $faq_actions as $action ) {
            add_action( "wp_ajax_{$action}", array( $this, "ajax_{$action}" ) );
            // Support legacy action names
            add_action( "wp_ajax_calc_admin_{$action}", array( $this, "ajax_{$action}" ) );
        }
    }

    /**
     * Register settings management AJAX endpoints
     *
     * @since 2.0.0
     */
    private function register_settings_ajax() {
        $settings_actions = array(
            'get_contact_settings',
            'save_contact_settings',
            'get_important_notes',
            'save_important_notes',
            'get_site_settings',
            'save_site_settings',
        );

        foreach ( $settings_actions as $action ) {
            add_action( "wp_ajax_{$action}", array( $this, "ajax_{$action}" ) );
        }
    }

    /**
     * Register appearance customization AJAX endpoints
     *
     * @since 2.0.0
     */
    private function register_appearance_ajax() {
        $appearance_actions = array(
            'save_appearance_settings',
            'reset_appearance_settings',
            'get_appearance_settings',
        );

        foreach ( $appearance_actions as $action ) {
            add_action( "wp_ajax_{$action}", array( $this, "ajax_{$action}" ) );
        }
    }
    
    /**
     * Register quotes management AJAX endpoints
     *
     * @since 2.0.0
     */
    private function register_quotes_ajax() {
        $quotes_actions = array(
            'get_quotes',
            'get_quote_details', 
            'delete_quote',
            'export_quotes_csv',
        );

        foreach ( $quotes_actions as $action ) {
            add_action( "wp_ajax_{$action}", array( $this, "ajax_{$action}" ) );
        }
    }
    
    /**
     * Add admin menu pages to WordPress dashboard
     *
     * Creates main menu item with comprehensive sub-menu structure for
     * all plugin management functions following WordPress admin standards.
     *
     * @since 2.0.0
     */
    public function add_admin_menu() {
        // Main menu page - Position 25 for optimal placement
        $main_page = add_menu_page(
            __( 'Cost Calculator', 'cost-calculator' ),
            __( 'Cost Calculator', 'cost-calculator' ),
            $this->capability,
            'cost-calculator',
            array( $this, 'admin_page' ),
            'dashicons-calculator',
            25
        );

        // Submenu pages for comprehensive plugin management
        $submenu_pages = array(
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Annotation Types', 'cost-calculator' ),
                'menu_title' => __( 'Annotation Types', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator',
                'callback'   => array( $this, 'admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Quote Requests', 'cost-calculator' ),
                'menu_title' => __( 'Quote Requests', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-quotes',
                'callback'   => array( $this, 'quotes_admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Important Notes', 'cost-calculator' ),
                'menu_title' => __( 'Important Notes', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-important-notes',
                'callback'   => array( $this, 'important_notes_admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'FAQ Management', 'cost-calculator' ),
                'menu_title' => __( 'FAQ Management', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-faq',
                'callback'   => array( $this, 'faq_admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Contact Settings', 'cost-calculator' ),
                'menu_title' => __( 'Contact Settings', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-contact',
                'callback'   => array( $this, 'contact_admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Site Settings', 'cost-calculator' ),
                'menu_title' => __( 'Site Settings', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-site',
                'callback'   => array( $this, 'site_settings_admin_page' ),
            ),
            array(
                'parent'     => 'cost-calculator',
                'page_title' => __( 'Appearance', 'cost-calculator' ),
                'menu_title' => __( 'Appearance', 'cost-calculator' ),
                'capability' => $this->capability,
                'menu_slug'  => 'cost-calculator-appearance',
                'callback'   => array( $this, 'appearance_admin_page' ),
            ),
        );

        // Register all submenu pages
        foreach ( $submenu_pages as $page ) {
            add_submenu_page(
                $page['parent'],
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback']
            );
        }

        // Add contextual help for main page
        add_action( "load-{$main_page}", array( $this, 'add_contextual_help' ) );
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * Loads necessary JavaScript and CSS files only on plugin admin pages.
     * Includes proper dependency management and localization for AJAX calls.
     *
     * @since 2.0.0
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts( $hook ) {
        // Define plugin admin page hooks
        $plugin_pages = array(
            'toplevel_page_cost-calculator',
            'cost-calculator_page_cost-calculator-quotes',
            'cost-calculator_page_cost-calculator-important-notes',
            'cost-calculator_page_cost-calculator-faq',
            'cost-calculator_page_cost-calculator-contact',
            'cost-calculator_page_cost-calculator-site',
            'cost-calculator_page_cost-calculator-appearance',
        );

        // Only load on plugin pages
        if ( ! in_array( $hook, $plugin_pages, true ) ) {
            return;
        }

        // Core WordPress dependencies
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        // Admin framework CSS - Load first for foundational styling
        wp_enqueue_style(
            'cost-calc-admin-framework',
            COST_CALC_PLUGIN_URL . 'assets/css/admin-framework.css',
            array(),
            COST_CALC_VERSION
        );

        // Admin specific CSS
        wp_enqueue_style(
            'cost-calc-admin',
            COST_CALC_PLUGIN_URL . 'assets/css/admin.css',
            array( 'cost-calc-admin-framework' ),
            COST_CALC_VERSION
        );

        // Admin JavaScript with proper dependencies
        wp_enqueue_script(
            'cost-calc-admin',
            COST_CALC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            COST_CALC_VERSION,
            true
        );

        // Localize script with all necessary data for AJAX operations
        wp_localize_script( 'cost-calc-admin', 'costCalcAdmin', array(
            'apiUrl'    => rest_url( 'cost-calc/v1/' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'ajaxNonce' => wp_create_nonce( 'cost_calc_admin_nonce' ),
            'pluginUrl' => COST_CALC_PLUGIN_URL,
            'version'   => COST_CALC_VERSION,
            'strings'   => array(
                'confirm_delete'   => __( 'Are you sure you want to delete this item?', 'cost-calculator' ),
                'save_success'     => __( 'Settings saved successfully.', 'cost-calculator' ),
                'save_error'       => __( 'Error saving settings. Please try again.', 'cost-calculator' ),
                'loading'          => __( 'Loading...', 'cost-calculator' ),
                'no_items'         => __( 'No items found.', 'cost-calculator' ),
            ),
        ) );

        // Add color picker if on appearance page
        if ( false !== strpos( $hook, 'appearance' ) ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
        }

        // Add media uploader if needed
        if ( false !== strpos( $hook, 'appearance' ) || false !== strpos( $hook, 'site' ) ) {
            wp_enqueue_media();
        }
    }

    /**
     * Show admin notices
     *
     * @since 2.0.0
     */
    public function show_admin_notices() {
        // Show activation notice
        if ( get_transient( 'cost_calc_activation_notice' ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php 
                    printf(
                        /* translators: %s: Plugin settings page URL */
                        esc_html__( 'Cost Calculator plugin activated successfully! %s to get started.', 'cost-calculator' ),
                        '<a href="' . esc_url( admin_url( 'admin.php?page=cost-calculator' ) ) . '">' . esc_html__( 'Visit Settings', 'cost-calculator' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
            delete_transient( 'cost_calc_activation_notice' );
        }
    }

    /**
     * Add contextual help to admin pages
     *
     * @since 2.0.0
     */
    public function add_contextual_help() {
        $screen = get_current_screen();
        
        $screen->add_help_tab( array(
            'id'      => 'cost-calc-overview',
            'title'   => __( 'Overview', 'cost-calculator' ),
            'content' => '<p>' . __( 'The Cost Calculator allows you to manage annotation types, pricing, and display settings for your data annotation services.', 'cost-calculator' ) . '</p>',
        ) );

        $screen->add_help_tab( array(
            'id'      => 'cost-calc-annotation-types',
            'title'   => __( 'Annotation Types', 'cost-calculator' ),
            'content' => '<p>' . __( 'Add, edit, and manage the annotation services you offer. Set pricing, descriptions, and active status for each type.', 'cost-calculator' ) . '</p>',
        ) );

        $screen->set_help_sidebar(
            '<p><strong>' . __( 'For more information:', 'cost-calculator' ) . '</strong></p>' .
            '<p><a href="https://deeplab.ai/support" target="_blank">' . __( 'Plugin Support', 'cost-calculator' ) . '</a></p>'
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Cost Calculator Management</h1>
            
            <div class="admin-content">
                <div class="admin-header">
                    <h2>Annotation Types</h2>
                    <button id="add-new-type" class="button button-primary">Add New Type</button>
                </div>
                
                <div class="admin-filters">
                    <div class="calc-status-filters">
                        <div class="calc-status-box active" data-filter="all">
                            <span class="calc-status-label">Total</span>
                            <span class="calc-status-count" id="total-count">0</span>
                        </div>
                        <div class="calc-status-box" data-filter="active">
                            <span class="calc-status-label">Active</span>
                            <span class="calc-status-count" id="active-count">0</span>
                        </div>
                        <div class="calc-status-box" data-filter="inactive">
                            <span class="calc-status-label">Inactive</span>
                            <span class="calc-status-count" id="inactive-count">0</span>
                        </div>
                    </div>
                    <input type="text" id="search-types" placeholder="Search types..." />
                </div>
                
                <div class="annotation-types-list" id="annotation-types-list">
                    <div class="loading">Loading annotation types...</div>
                </div>
            </div>
            
            <!-- Edit Modal -->
            <div id="edit-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modal-title">Edit Annotation Type</h3>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="edit-form">
                            <input type="hidden" id="edit-id" />
                            
                            <div class="form-group">
                                <label for="edit-name">Name</label>
                                <input type="text" id="edit-name" class="calc-input" required />
                            </div>
                            
                            <div class="form-group">
                                <label for="edit-description">Description</label>
                                <textarea id="edit-description" rows="5" class="calc-textarea"></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-rate">Rate</label>
                                    <input type="number" id="edit-rate" class="calc-input" step="0.01" min="0" required />
                                </div>
                                <div class="form-group">
                                    <label for="edit-unit">Unit</label>
                                    <input type="text" id="edit-unit" class="calc-input" placeholder="per image" required />
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="edit-alt-rate">Alternative Rate (optional)</label>
                                    <input type="number" id="edit-alt-rate" class="calc-input" step="0.01" min="0" />
                                </div>
                                <div class="form-group">
                                    <label for="edit-alt-unit">Alternative Unit</label>
                                    <input type="text" id="edit-alt-unit" class="calc-input" placeholder="per hour" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="edit-image-based" />
                                    Image-based annotation
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="edit-active" />
                                    Active
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" id="modal-cancel">Cancel</button>
                        <button type="button" class="button button-primary" id="modal-save">Save Changes</button>
                    </div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <div id="admin-messages"></div>
        </div>
        <?php
    }
    
    public function ajax_save_cost_type() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $rate = floatval($_POST['rate']);
        $unit = sanitize_text_field($_POST['unit']);
        $alt_rate = !empty($_POST['alt_rate']) ? floatval($_POST['alt_rate']) : null;
        $alt_unit = !empty($_POST['alt_unit']) ? sanitize_text_field($_POST['alt_unit']) : null;
        $is_image_based = isset($_POST['is_image_based']) && $_POST['is_image_based'] === 'true' ? 1 : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'true' ? 1 : 0;
        
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        $data = array(
            'name' => $name,
            'description' => $description,
            'rate' => $rate,
            'unit' => $unit,
            'alt_rate' => $alt_rate,
            'alt_unit' => $alt_unit,
            'is_image_based' => $is_image_based,
            'is_active' => $is_active
        );
        
        // Check if this is an update or insert
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE id = %s",
            $id
        ));
        
        if ($exists > 0) {
            // Update existing
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $id)
            );
        } else {
            // Insert new
            $data['id'] = $id;
            $result = $wpdb->insert($table_name, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('Annotation type saved successfully');
        } else {
            wp_send_json_error('Failed to save annotation type: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_toggle_cost_type() {
        // Verify nonce for security  
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['type_id']);
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$table_name} WHERE id = %s",
            $id
        ));
        
        if ($current_status === null) {
            wp_send_json_error('Annotation type not found');
            return;
        }
        
        // Toggle status
        $new_status = $current_status == 1 ? 0 : 1;
        
        $result = $wpdb->update(
            $table_name,
            array('is_active' => $new_status),
            array('id' => $id),
            array('%d'),
            array('%s')
        );
        
        error_log("Toggle annotation type - ID: {$id}, new_status: {$new_status}, result: {$result}, error: " . $wpdb->last_error);
        
        if ($result !== false) {
            // Clear any WordPress cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            wp_send_json_success(array(
                'message' => 'Status toggled successfully',
                'new_status' => $new_status == 1,
                'type_id' => $id
            ));
        } else {
            wp_send_json_error('Failed to toggle status: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_delete_cost_type() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['type_id']);
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        // Check if annotation type is inactive (required before deletion)
        $is_active = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$table_name} WHERE id = %s",
            $id
        ));
        
        if ($is_active === null) {
            wp_send_json_error('Annotation type not found');
            return;
        }
        
        if ($is_active == 1) {
            wp_send_json_error('Cannot delete active annotation type. Please deactivate it first.');
            return;
        }
        
        error_log("Attempting to delete annotation type: {$id}");
        
        $result = $wpdb->delete(
            $table_name, 
            array('id' => $id),
            array('%s')
        );
        
        error_log("Delete annotation type - ID: {$id}, result: {$result}, error: " . $wpdb->last_error);
        
        if ($result !== false && $result > 0) {
            // Clear any WordPress cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            wp_send_json_success('Annotation type deleted successfully');
        } else {
            wp_send_json_error('Failed to delete annotation type: ' . ($wpdb->last_error ?: 'No rows affected'));
        }
    }
    
    // FAQ Admin Page
    public function faq_admin_page() {
        ?>
        <div class="wrap">
            <h1>FAQ Management</h1>
            <p>Manage frequently asked questions displayed on the calculator.</p>
            
            <div class="cost-calc-admin-container">
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">FAQ Section Configuration</h2>
                        <p class="calc-card-description">Configure how the FAQ section appears and manage its title.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="faq-enabled" class="calc-field-label">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Enable FAQ Section
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="faq-enabled" name="faq_enabled" checked>
                                            <span class="calc-toggle-slider"></span>
                                        </label>

                                    </div>
                                    <p class="calc-field-description">
                                        Show or hide the FAQ section on the calculator page.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="faq-title" class="calc-field-label">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        FAQ Section Title
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="faq-title" name="faq_title" class="calc-input" placeholder="Frequently Asked Questions" value="Frequently Asked Questions">
                                    <p class="calc-field-description">The heading displayed at the top of the FAQ section.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-faq-settings">
                                <span class="dashicons dashicons-saved"></span>
                                Save FAQ Settings
                            </button>
                        </div>
                        <div class="calc-save-status" id="faq-settings-save-status"></div>
                    </div>
                </div>
            </div>
            
            <div class="admin-content">
                <div class="admin-header">
                    <h2>FAQ Items</h2>
                    <button id="add-new-faq" class="button button-primary">Add New FAQ</button>
                </div>
                
                <div class="admin-filters">
                    <div class="calc-status-filters">
                        <div class="calc-status-box active" data-filter="all">
                            <span class="calc-status-label">Total</span>
                            <span class="calc-status-count" id="faq-total-count">0</span>
                        </div>
                        <div class="calc-status-box" data-filter="active">
                            <span class="calc-status-label">Active</span>
                            <span class="calc-status-count" id="faq-active-count">0</span>
                        </div>
                        <div class="calc-status-box" data-filter="inactive">
                            <span class="calc-status-label">Inactive</span>
                            <span class="calc-status-count" id="faq-inactive-count">0</span>
                        </div>
                    </div>
                    <select id="faq-category-filter">
                        <option value="all">All Categories</option>
                        <option value="General">General</option>
                        <option value="Pricing">Pricing</option>
                        <option value="Quality">Quality</option>
                        <option value="Technical">Technical</option>
                    </select>
                    <input type="text" id="search-faq" placeholder="Search FAQs..." />
                </div>
                
                <div class="faq-items-list" id="faq-items-list">
                    <div class="loading">Loading FAQ items...</div>
                </div>
            </div>
            
            <!-- FAQ Edit Modal -->
            <div id="faq-edit-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="faq-modal-title">Edit FAQ Item</h3>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="faq-edit-form">
                            <input type="hidden" id="faq-edit-id" />
                            
                            <div class="form-group">
                                <label for="faq-edit-question">Question</label>
                                <input type="text" id="faq-edit-question" required />
                            </div>
                            
                            <div class="form-group">
                                <label for="faq-edit-answer">Answer</label>
                                <textarea id="faq-edit-answer" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="faq-edit-category">Category</label>
                                    <select id="faq-edit-category">
                                        <option value="General">General</option>
                                        <option value="Pricing">Pricing</option>
                                        <option value="Quality">Quality</option>
                                        <option value="Technical">Technical</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="faq-edit-order">Order</label>
                                    <input type="number" id="faq-edit-order" min="0" value="0" />
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="faq-edit-active" />
                                    Active
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" id="faq-modal-cancel">Cancel</button>
                        <button type="button" class="button button-primary" id="faq-modal-save">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Contact Settings Admin Page
    public function contact_admin_page() {
        ?>
        <div class="wrap">
            <h1>Contact Settings</h1>
            <p>Configure the contact section displayed on the calculator.</p>
            
            <div class="cost-calc-admin-container">
                <!-- Contact Section Configuration Card -->
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">Contact Section Configuration</h2>
                        <p class="calc-card-description">Manage how the contact section appears and manage its title.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="show-contact" class="calc-field-label">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Enable Contact Section
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="show-contact" name="contact_enabled" />
                                            <span class="calc-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <p class="calc-field-description">Show or hide the contact section on the calculator page.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-title" class="calc-field-label">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        Section Title
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="contact-title" name="contact_title" class="calc-input" placeholder="Get Your Custom Quote" />
                                    <p class="calc-field-description">The heading displayed at the top of the contact section.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-description" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Section Description
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <textarea id="contact-description" name="contact_description" class="calc-textarea" rows="4" placeholder="Contact our team for personalized pricing based on your specific annotation requirements."></textarea>
                                    <p class="calc-field-description">Text displayed below the title in the contact section.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-button-text" class="calc-field-label">
                                        <span class="dashicons dashicons-button"></span>
                                        Button Text
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="contact-button-text" name="contact_button_text" class="calc-input" placeholder="Schedule Consultation" />
                                    <p class="calc-field-description">Text displayed on the contact button.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-button-url" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        Button URL
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="url" id="contact-button-url" name="contact_button_url" class="calc-input" placeholder="https://calendly.com/your-link" />
                                    <p class="calc-field-description">URL the contact button links to (Calendly, contact form, etc.).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-contact-settings-page">
                                <span class="dashicons dashicons-saved"></span>
                                Save Contact Settings
                            </button>
                        </div>
                        <div class="calc-save-status" id="contact-settings-save-status"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Settings Admin Page (for compatibility)
    public function settings_admin_page() {
        // Redirect to contact settings for now
        $this->contact_admin_page();
    }
    
    // FAQ AJAX Handlers
    public function ajax_save_faq_item() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id']);
        $question = sanitize_text_field($_POST['question']);
        $answer = sanitize_textarea_field($_POST['answer']);
        $category = sanitize_text_field($_POST['category']);
        $order_num = intval($_POST['order']);
        // Fix checkbox handling - ensure proper boolean conversion
        $is_active_raw = $_POST['isActive'] ?? 'false';
        $is_active = ($is_active_raw === true || $is_active_raw === 'true' || $is_active_raw === '1' || $is_active_raw === 1) ? 1 : 0;
        
        // Debug logging
        error_log("FAQ Save - ID: {$id}, isActive raw: " . var_export($is_active_raw, true) . ", converted: {$is_active}");
        
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        // Check if this is an update or insert
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$faq_table} WHERE id = %s",
            $id
        ));
        
        $data = array(
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'order_num' => $order_num,
            'is_active' => $is_active
        );
        
        if ($exists > 0) {
            // Update existing
            $result = $wpdb->update($faq_table, $data, array('id' => $id));
        } else {
            // Insert new
            $data['id'] = $id;
            $result = $wpdb->insert($faq_table, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success('FAQ item saved successfully');
        } else {
            wp_send_json_error('Failed to save FAQ item: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_toggle_faq_item() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id']);
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        // Get current status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$faq_table} WHERE id = %s",
            $id
        ));
        
        if ($current_status === null) {
            wp_send_json_error('FAQ item not found');
            return;
        }
        
        // Toggle status
        $new_status = $current_status == 1 ? 0 : 1;
        
        $result = $wpdb->update(
            $faq_table,
            array('is_active' => $new_status),
            array('id' => $id),
            array('%d'),
            array('%s')
        );
        
        error_log("Toggle FAQ - ID: {$id}, new_status: {$new_status}, result: {$result}, error: " . $wpdb->last_error);
        
        if ($result !== false) {
            // Clear any WordPress cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            wp_send_json_success(array(
                'message' => 'FAQ status toggled successfully',
                'new_status' => $new_status == 1
            ));
        } else {
            wp_send_json_error('Failed to toggle FAQ status: ' . $wpdb->last_error);
        }
    }
    
    public function ajax_delete_faq_item() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id']);
        $faq_table = CostCalc_Database::get_table_name('cost_calc_faq');
        
        // Check if FAQ item is inactive (required before deletion)
        $is_active = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM {$faq_table} WHERE id = %s",
            $id
        ));
        
        if ($is_active === null) {
            wp_send_json_error('FAQ item not found');
            return;
        }
        
        if ($is_active == 1) {
            wp_send_json_error('Cannot delete active FAQ item. Please deactivate it first.');
            return;
        }
        
        error_log("Attempting to delete FAQ item: {$id}");
        
        $result = $wpdb->delete(
            $faq_table, 
            array('id' => $id),
            array('%s')
        );
        
        error_log("Delete FAQ - ID: {$id}, result: {$result}, error: " . $wpdb->last_error);
        
        if ($result !== false && $result > 0) {
            // Clear any WordPress cache
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            wp_send_json_success('FAQ item deleted successfully');
        } else {
            wp_send_json_error('Failed to delete FAQ item: ' . ($wpdb->last_error ?: 'No rows affected'));
        }
    }
    
    // Appearance Admin Page
    public function appearance_admin_page() {
        ?>
        <div class="wrap">
            <h1>Calculator Appearance</h1>
            <p>Configure how the calculator appears on your website. Choose between WordPress global theme styling or custom appearance.</p>
            
            <div class="cost-calc-admin-container">
                <!-- Style Settings -->
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            Style Settings
                        </h2>
                        <p class="calc-card-description">Choose how the calculator should inherit styling from your WordPress theme.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="use-custom-styles" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-customizer"></span>
                                        Use Custom Styles
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="use-custom-styles" name="use_custom_styles">
                                            <span class="calc-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <p class="calc-field-description">
                                        <strong>Off:</strong> Use WordPress global theme styles (recommended)<br>
                                        <strong>On:</strong> Use custom styling options below
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-appearance">
                                <span class="dashicons dashicons-saved"></span>
                                Save Appearance Settings
                            </button>
                            <button type="button" class="calc-btn calc-btn-secondary" id="reset-appearance">
                                <span class="dashicons dashicons-backup"></span>
                                Reset to Default
                            </button>
                        </div>
                        <div class="calc-save-status" id="appearance-save-status"></div>
                    </div>
                </div>

                <!-- Custom Styling Options (Hidden by default) -->
                <div class="calc-admin-card" id="custom-styling-options" style="display: none;">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">
                            <span class="dashicons dashicons-admin-tools"></span>
                            Custom Styling Options
                        </h2>
                        <p class="calc-card-description">Customize the appearance when not using WordPress global theme.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <!-- Color Scheme Section -->
                            <div class="calc-form-section">
                                <h3 class="calc-section-title">
                                    <span class="dashicons dashicons-admin-appearance"></span>
                                    Color Scheme
                                </h3>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="primary-color" class="calc-field-label">Primary Color</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <input type="color" id="primary-color" value="#2563eb" class="calc-color-input" />
                                        <p class="calc-field-description">Main accent color for buttons and highlights</p>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="secondary-color" class="calc-field-label">Secondary Color</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <input type="color" id="secondary-color" value="#64748b" class="calc-color-input" />
                                        <p class="calc-field-description">Secondary elements and borders</p>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="background-color" class="calc-field-label">Background Color</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <input type="color" id="background-color" value="#ffffff" class="calc-color-input" />
                                        <p class="calc-field-description">Main background color</p>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="text-color" class="calc-field-label">Text Color</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <input type="color" id="text-color" value="#1f2937" class="calc-color-input" />
                                        <p class="calc-field-description">Primary text color</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Header Style Section -->
                            <div class="calc-form-section">
                                <h3 class="calc-section-title">
                                    <span class="dashicons dashicons-admin-customizer"></span>
                                    Header Style Options
                                </h3>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="header-style-solid" class="calc-field-label">Header Background Style</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <div class="calc-radio-group">
                                            <label class="calc-radio-option">
                                                <input type="radio" name="header-style" value="solid" id="header-style-solid" checked />
                                                <span class="calc-radio-label">Solid Color Background</span>
                                            </label>
                                            <label class="calc-radio-option">
                                                <input type="radio" name="header-style" value="gradient" id="header-style-gradient" />
                                                <span class="calc-radio-label">Gradient Background</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div id="gradient-controls" style="display: none;">
                                    <div class="calc-form-group">
                                        <div class="calc-field-header">
                                            <label for="gradient-start" class="calc-field-label">Gradient Start Color</label>
                                        </div>
                                        <div class="calc-field-content">
                                            <input type="color" id="gradient-start" value="#667eea" class="calc-color-input" />
                                        </div>
                                    </div>
                                    <div class="calc-form-group">
                                        <div class="calc-field-header">
                                            <label for="gradient-end" class="calc-field-label">Gradient End Color</label>
                                        </div>
                                        <div class="calc-field-content">
                                            <input type="color" id="gradient-end" value="#764ba2" class="calc-color-input" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Typography Section -->
                            <div class="calc-form-section">
                                <h3 class="calc-section-title">
                                    <span class="dashicons dashicons-editor-textcolor"></span>
                                    Typography
                                </h3>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="font-family" class="calc-field-label">Font Family</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <select id="font-family" class="calc-input">
                                            <option value="system">System Default</option>
                                            <option value="Arial, sans-serif">Arial</option>
                                            <option value="Helvetica, sans-serif">Helvetica</option>
                                            <option value="Georgia, serif">Georgia</option>
                                            <option value="Times New Roman, serif">Times New Roman</option>
                                            <option value="Roboto, sans-serif">Roboto (Google Fonts)</option>
                                            <option value="Open Sans, sans-serif">Open Sans (Google Fonts)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="font-size" class="calc-field-label">Base Font Size</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <select id="font-size" class="calc-input">
                                            <option value="14px">Small (14px)</option>
                                            <option value="16px" selected>Medium (16px)</option>
                                            <option value="18px">Large (18px)</option>
                                            <option value="20px">Extra Large (20px)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Layout Section -->
                            <div class="calc-form-section">
                                <h3 class="calc-section-title">
                                    <span class="dashicons dashicons-layout"></span>
                                    Layout & Spacing
                                </h3>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="container-width" class="calc-field-label">Container Width</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <select id="container-width" class="calc-input">
                                            <option value="800px">Narrow (800px)</option>
                                            <option value="1000px" selected>Medium (1000px)</option>
                                            <option value="1200px">Wide (1200px)</option>
                                            <option value="100%">Full Width</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="card-spacing" class="calc-field-label">Card Spacing</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <select id="card-spacing" class="calc-input">
                                            <option value="compact">Compact</option>
                                            <option value="normal" selected>Normal</option>
                                            <option value="spacious">Spacious</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="border-radius" class="calc-field-label">Border Radius</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <select id="border-radius" class="calc-input">
                                            <option value="0px">Square (0px)</option>
                                            <option value="4px">Small (4px)</option>
                                            <option value="8px" selected>Medium (8px)</option>
                                            <option value="12px">Large (12px)</option>
                                            <option value="20px">Extra Large (20px)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom CSS Section -->
                            <div class="calc-form-section">
                                <h3 class="calc-section-title">
                                    <span class="dashicons dashicons-editor-code"></span>
                                    Custom CSS
                                </h3>
                                
                                <div class="calc-form-group">
                                    <div class="calc-field-header">
                                        <label for="custom-css" class="calc-field-label">Additional CSS</label>
                                    </div>
                                    <div class="calc-field-content">
                                        <textarea id="custom-css" rows="15" class="calc-textarea" placeholder="/* Add your custom CSS here */
.cost-calculator {
    /* Your custom styles */
}"></textarea>
                                        <p class="calc-field-description">Add custom CSS to further customize the calculator appearance</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Save Actions -->
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-appearance-settings">
                                <span class="dashicons dashicons-saved"></span>
                                Save Appearance Settings
                            </button>
                            <button type="button" class="calc-btn calc-btn-secondary" id="reset-appearance-settings">
                                <span class="dashicons dashicons-undo"></span>
                                Reset to Default
                            </button>
                        </div>
                        <div class="calc-save-status" id="appearance-save-status"></div>
                    </div>
                </div>
            </div>
            
            <!-- JavaScript for toggling custom styles -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const useCustomStylesToggle = document.getElementById('use-custom-styles');
                const customStylingOptions = document.getElementById('custom-styling-options');
                
                // Toggle custom styling options visibility
                function toggleCustomStyling() {
                    if (useCustomStylesToggle.checked) {
                        customStylingOptions.style.display = 'block';
                    } else {
                        customStylingOptions.style.display = 'none';
                    }
                }
                
                // Initial state
                toggleCustomStyling();
                
                // Event listener
                useCustomStylesToggle.addEventListener('change', toggleCustomStyling);
            });
            </script>
        </div>
        <?php
    }
    
    public function quotes_admin_page() {
        ?>
        <div class="wrap">
            <div class="calc-admin-header">
                <h1 class="calc-admin-title">
                    <span class="dashicons dashicons-email-alt"></span>
                    Quote Requests
                </h1>
                <p class="calc-admin-description">View and manage quote requests submitted through your cost calculator.</p>
            </div>
            
            <div class="calc-admin-cards">
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">
                            <span class="dashicons dashicons-list-view"></span>
                            Submitted Quote Requests
                        </h2>
                        <p class="calc-card-description">All quote requests submitted by potential clients.</p>
                    </div>
                    
                    <div class="calc-card-content">
                        <div class="calc-quotes-controls">
                            <div class="calc-filter-section">
                                <label for="date-filter">Filter by Date:</label>
                                <select id="date-filter" class="calc-input">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div class="calc-search-section">
                                <input type="text" id="search-quotes" class="calc-input" placeholder="Search quotes by name, email, or company..." />
                            </div>
                            <div class="calc-export-section">
                                <button type="button" id="export-quotes" class="calc-btn calc-btn-secondary">
                                    <span class="dashicons dashicons-download"></span>
                                    Export CSV
                                </button>
                            </div>
                        </div>
                        
                        <div class="calc-quotes-table-container">
                            <table class="calc-quotes-table" id="quotes-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Company</th>
                                        <th>Selected Types</th>
                                        <th>Total Cost</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="quotes-table-body">
                                    <tr class="loading-row">
                                        <td colspan="7" class="loading-cell">Loading quote requests...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quote Details Modal -->
            <div id="quote-details-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Quote Request Details</h3>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div id="quote-details-content">
                            <!-- Quote details will be loaded here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" id="close-quote-details">Close</button>
                        <button type="button" class="button button-primary" id="contact-client">Contact Client</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .calc-quotes-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .calc-filter-section,
        .calc-search-section,
        .calc-export-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .calc-search-section {
            flex: 1;
            min-width: 250px;
        }
        
        .calc-quotes-table-container {
            overflow-x: auto;
            border: 1px solid var(--wp-admin-gray-300);
            border-radius: var(--wp-admin-radius-md);
        }
        
        .calc-quotes-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .calc-quotes-table th,
        .calc-quotes-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--wp-admin-gray-200);
        }
        
        .calc-quotes-table th {
            background: var(--wp-admin-gray-50);
            font-weight: 600;
            color: var(--wp-admin-gray-700);
        }
        
        .calc-quotes-table tbody tr:hover {
            background: var(--wp-admin-gray-50);
        }
        
        .loading-cell {
            text-align: center;
            padding: 2rem;
            color: var(--wp-admin-gray-500);
        }
        
        .quote-types-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .quote-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .quote-actions .button {
            padding: 4px 8px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    public function important_notes_admin_page() {
        ?>
        <div class="wrap">
            <div class="calc-admin-header">
                <h1 class="calc-admin-title">
                    <span class="dashicons dashicons-info"></span>
                    Important Notes Settings
                </h1>
                <p class="calc-admin-subtitle">Configure the important notes section that appears on your calculator to guide users and set expectations.</p>
            </div>
            
            <div class="calc-admin-container">
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">Section Configuration</h2>
                        <p class="calc-card-description">Control the visibility and content of the important notes section.</p>
                    </div>
                    
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="notes-enabled" class="calc-field-label">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Enable Important Notes Section
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="notes-enabled" name="notes_enabled" value="1">
                                            <span class="calc-toggle-slider"></span>
                                        </label>

                                    </div>
                                    <p class="calc-field-description">Show or hide the important notes section on the calculator interface.</p>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="notes-title" class="calc-field-label">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        Section Title
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="notes-title" name="notes_title" class="calc-input" placeholder="Important Notes">
                                    <p class="calc-field-description">The heading displayed above the notes content. Keep it clear and attention-grabbing.</p>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="notes-content" class="calc-field-label">
                                        <span class="dashicons dashicons-edit"></span>
                                        Notes Content
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-editor-container">
                                        <div class="calc-editor-toolbar">
                                        <button type="button" class="calc-editor-btn" data-tag="strong" title="Bold">
                                            <span class="dashicons dashicons-editor-bold"></span>
                                        </button>
                                        <button type="button" class="calc-editor-btn" data-tag="em" title="Italic">
                                            <span class="dashicons dashicons-editor-italic"></span>
                                        </button>
                                        <button type="button" class="calc-editor-btn" data-action="ul" title="Bullet List">
                                            <span class="dashicons dashicons-editor-ul"></span>
                                        </button>
                                        <button type="button" class="calc-editor-btn" data-action="link" title="Add Link">
                                            <span class="dashicons dashicons-admin-links"></span>
                                        </button>
                                        <button type="button" class="calc-editor-btn" data-action="preview" title="Preview">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </button>
                                    </div>
                                    <textarea id="notes-content" name="notes_content" rows="15" class="calc-editor-textarea" style="min-height: 200px;"></textarea>
                                    <div id="notes-preview" class="calc-editor-preview" style="display: none;">
                                        <div class="calc-preview-content"></div>
                                    </div>
                                    <p class="calc-field-description">
                                        <span class="dashicons dashicons-info-outline"></span>
                                        HTML formatting is supported. Use the toolbar or HTML tags like &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;a&gt; for rich formatting.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-important-notes">
                                <span class="dashicons dashicons-saved"></span>
                                Save Important Notes
                            </button>
                            <button type="button" class="calc-btn calc-btn-secondary" id="reset-important-notes">
                                <span class="dashicons dashicons-undo"></span>
                                Reset to Default
                            </button>
                        </div>
                        <div class="calc-save-status" id="notes-save-status"></div>
                    </div>
                </div>
                
                <div class="calc-admin-card calc-preview-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">Live Preview</h2>
                        <p class="calc-card-description">See how your important notes will appear on the calculator.</p>
                    </div>
                    <div class="calc-card-content">
                        <div id="notes-live-preview" class="calc-notes-preview">
                            <div class="preview-important-notes">
                                <h3 id="preview-title">Important Notes</h3>
                                <div id="preview-content" class="preview-content">
                                    <p>Configure your important notes content above to see the preview.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function contact_settings_admin_page() {
        ?>
        <div class="wrap">
            <h1>Contact Settings</h1>
            <p>Configure the contact section displayed on the calculator.</p>
            
            <div class="cost-calc-admin-container">
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">Contact Section Configuration</h2>
                        <p class="calc-card-description">Manage how the contact section appears to users on the calculator.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-enabled" class="calc-field-label">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Enable Contact Section
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="contact-enabled" name="contact_enabled" checked>
                                            <span class="calc-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <p class="calc-field-description">
                                        Show or hide the contact section on the calculator page.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-title" class="calc-field-label">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        Section Title
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="contact-title" name="contact_title" class="calc-input" placeholder="Get Your Custom Quote">
                                    <p class="calc-field-description">The heading displayed in the contact section.</p>
                                </div>
                            </div>
                    
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-description" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Section Description
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <textarea id="contact-description" name="contact_description" rows="4" class="calc-textarea" placeholder="Ready to start your annotation project? Schedule a consultation to discuss your specific requirements and get a detailed quote."></textarea>
                                    <p class="calc-field-description">Text displayed below the title.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-button-text" class="calc-field-label">
                                        <span class="dashicons dashicons-button"></span>
                                        Button Text
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="contact-button-text" name="contact_button_text" class="calc-input" placeholder="Schedule Consultation">
                                    <p class="calc-field-description">Text displayed on the contact button.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="contact-button-url" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        Button URL
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="url" id="contact-button-url" name="contact_button_url" class="calc-input" placeholder="https://calendly.com/your-link">
                                    <p class="calc-field-description">URL the contact button links to (Calendly, contact form, etc.).</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-contact-settings">
                                <span class="dashicons dashicons-saved"></span>
                                Save Contact Settings
                            </button>
                            <button type="button" class="calc-btn calc-btn-secondary" id="reset-contact-settings">
                                <span class="dashicons dashicons-undo"></span>
                                Reset to Default
                            </button>
                        </div>
                        <div class="calc-save-status" id="contact-save-status"></div>
                    </div>
                </div>
            </div>
            

        </div>
        <?php
    }
    
    public function site_settings_admin_page() {
        ?>
        <div class="wrap">
            <h1>Site Settings</h1>
            <p>Configure general settings for the annotation calculator. Colors will follow your WordPress theme's global color scheme.</p>
            
            <div class="cost-calc-admin-container">
                <!-- Shortcode Instructions -->
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">
                            <span class="dashicons dashicons-shortcode"></span>
                            How to Use the Calculator
                        </h2>
                        <p class="calc-card-description">Embed the cost calculator on any page or post using the shortcode below.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label class="calc-field-label">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Shortcode for Pages/Posts
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-shortcode-display">
                                        <code>[cost_calculator]</code>
                                        <button type="button" class="calc-copy-btn" onclick="navigator.clipboard.writeText('[cost_calculator]')" title="Copy shortcode">
                                            <span class="dashicons dashicons-admin-page"></span>
                                        </button>
                                    </div>
                                    <p class="calc-field-description">
                                        Copy this shortcode and paste it into any WordPress page or post where you want the calculator to appear.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label class="calc-field-label">
                                        <span class="dashicons dashicons-admin-appearance"></span>
                                        Usage Instructions
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <ul class="calc-instructions-list">
                                        <li>Go to Pages → Add New or edit an existing page</li>
                                        <li>Add the shortcode <code>[cost_calculator]</code> where you want the calculator</li>
                                        <li>Publish or update the page</li>
                                        <li>The calculator will appear with all your configured settings</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="calc-admin-card">
                    <div class="calc-card-header">
                        <h2 class="calc-card-title">Calculator Title & Description</h2>
                        <p class="calc-card-description">Configure the main heading and description shown on the calculator page.</p>
                    </div>
                    <div class="calc-card-content">
                        <div class="calc-form-grid">
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="show-calculator-header" class="calc-field-label">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Show Calculator Header
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="show-calculator-header" name="show_calculator_header" checked>
                                            <span class="calc-toggle-slider"></span>
                                        </label>

                                    </div>
                                    <p class="calc-field-description">
                                        Show or hide the title and description section on the calculator page.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="site-title" class="calc-field-label">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        Calculator Title
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <input type="text" id="site-title" name="site_title" class="calc-input large-text" placeholder="Cost Calculator">
                                    <p class="calc-field-description">The main title displayed at the top of the calculator.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="title-alignment" class="calc-field-label">
                                        <span class="dashicons dashicons-align-center"></span>
                                        Title Alignment
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <select id="title-alignment" name="title_alignment" class="calc-input">
                                        <option value="left">Left</option>
                                        <option value="center" selected>Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                    <p class="calc-field-description">Choose how the calculator title should be aligned.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="site-description" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-page"></span>
                                        Calculator Description
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <textarea id="site-description" name="site_description" rows="4" class="calc-textarea" placeholder="Calculate the cost of your annotation project with our transparent pricing tool. Get instant estimates for various annotation types and request a detailed quote."></textarea>
                                    <p class="calc-field-description">Description text displayed below the title.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="notification-emails" class="calc-field-label">
                                        <span class="dashicons dashicons-email"></span>
                                        Notification Email Addresses
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <textarea id="notification-emails" name="notification_emails" rows="3" class="calc-textarea" placeholder="admin@yoursite.com&#10;sales@yoursite.com"></textarea>
                                    <p class="calc-field-description">Email addresses to notify when quote requests are submitted (one per line).</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="theme-setting" class="calc-field-label">
                                        <span class="dashicons dashicons-admin-appearance"></span>
                                        Default Theme
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <select id="theme-setting" name="theme_setting" class="calc-input">
                                        <option value="light">Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Auto (System Preference)</option>
                                    </select>
                                    <p class="calc-field-description">Default color theme for the calculator.</p>
                                </div>
                            </div>
                            
                            <div class="calc-form-group">
                                <div class="calc-field-header">
                                    <label for="enable-animations" class="calc-field-label">
                                        <span class="dashicons dashicons-controls-play"></span>
                                        Enable Animations
                                    </label>
                                </div>
                                <div class="calc-field-content">
                                    <div class="calc-toggle-inline">
                                        <label class="calc-toggle-switch">
                                            <input type="checkbox" id="enable-animations" name="enable_animations" value="1">
                                            <span class="calc-toggle-slider"></span>
                                        </label>

                                    </div>
                                    <p class="calc-field-description">Enable smooth animations and transitions in the calculator.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="calc-card-footer">
                        <div class="calc-action-buttons">
                            <button type="button" class="calc-btn calc-btn-primary" id="save-site-settings">
                                <span class="dashicons dashicons-saved"></span>
                                Save Site Settings
                            </button>
                            <button type="button" class="calc-btn calc-btn-secondary" id="reset-site-settings">
                                <span class="dashicons dashicons-backup"></span>
                                Reset to Default
                            </button>
                        </div>
                        <div class="calc-save-status" id="site-settings-save-status"></div>
                    </div>
                </div>
            </div>
            

        </div>
        <?php
    }
    
    // Appearance AJAX Handlers
    public function ajax_save_appearance_settings() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Get settings from POST data
        $settings = array(
            'primary_color' => sanitize_hex_color($_POST['primary_color'] ?? '#2563eb'),
            'secondary_color' => sanitize_hex_color($_POST['secondary_color'] ?? '#10b981'),
            'background_color' => sanitize_hex_color($_POST['background_color'] ?? '#f8fafc'),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#1f2937'),
            'border_radius' => intval($_POST['border_radius'] ?? 8),
            'font_size' => intval($_POST['font_size'] ?? 16),
            'spacing' => intval($_POST['spacing'] ?? 16),
            'shadow_enabled' => isset($_POST['shadow_enabled']),
            'animations_enabled' => isset($_POST['animations_enabled']),
            'custom_css' => wp_kses_post($_POST['custom_css'] ?? '')
        );
        
        // Save to database using settings table
        global $wpdb;
        $settings_table = $wpdb->prefix . 'annotation_settings';
        
        foreach ($settings as $key => $value) {
            $wpdb->replace(
                $settings_table,
                array(
                    'setting_key' => 'appearance_' . $key,
                    'setting_value' => $value,
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Appearance settings saved successfully',
            'settings' => $settings
        ));
    }
    
    public function ajax_reset_appearance_settings() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Default appearance settings
        $default_settings = array(
            'primary_color' => '#2563eb',
            'secondary_color' => '#10b981',
            'background_color' => '#f8fafc',
            'text_color' => '#1f2937',
            'border_radius' => 8,
            'font_size' => 16,
            'spacing' => 16,
            'shadow_enabled' => true,
            'animations_enabled' => true,
            'custom_css' => ''
        );
        
        // Reset to defaults in database
        global $wpdb;
        $settings_table = $wpdb->prefix . 'annotation_settings';
        
        foreach ($default_settings as $key => $value) {
            $wpdb->replace(
                $settings_table,
                array(
                    'setting_key' => 'appearance_' . $key,
                    'setting_value' => $value,
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Appearance settings reset to default',
            'settings' => $default_settings
        ));
    }
    
    public function ajax_get_appearance_settings() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Get settings from database
        global $wpdb;
        $settings_table = $wpdb->prefix . 'annotation_settings';
        
        $default_settings = array(
            'primary_color' => '#2563eb',
            'secondary_color' => '#10b981',
            'background_color' => '#f8fafc',
            'text_color' => '#1f2937',
            'border_radius' => 8,
            'font_size' => 16,
            'spacing' => 16,
            'shadow_enabled' => true,
            'animations_enabled' => true,
            'custom_css' => ''
        );
        
        $settings = array();
        foreach ($default_settings as $key => $default_value) {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
                'appearance_' . $key
            ));
            
            $settings[$key] = $value !== null ? $value : $default_value;
        }
        
        wp_send_json_success($settings);
    }
    
    public function ajax_get_admin_cost_types() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = CostCalc_Database::get_table_name('cost_calc_types');
        
        // Get ALL annotation types for admin (both active and inactive)
        $results = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY name ASC",
            ARRAY_A
        );
        
        // Convert database format to expected format
        $cost_types = array();
        foreach ($results as $row) {
            $cost_types[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'rate' => $row['rate'],
                'unit' => $row['unit'],
                'alt_rate' => $row['alt_rate'],
                'alt_unit' => $row['alt_unit'],
                'is_image_based' => (bool)$row['is_image_based'],
                'is_active' => (bool)$row['is_active'],
                'language_tiers' => $row['language_tiers'] ? json_decode($row['language_tiers'], true) : null,
                'updated_at' => $row['updated_at']
            );
        }
        
        wp_send_json_success($cost_types);
    }
    
    // FAQ AJAX endpoints
    public function ajax_get_admin_faq_items() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = CostCalc_Database::get_table_name('cost_calc_faq');
        
        // Check if FAQ table exists and has data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        
        // If no FAQ items exist, initialize default data
        if ($count == 0) {
            error_log('FAQ table is empty, initializing default FAQ items');
            CostCalc_Database::init_default_faq_items();
        }
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$table_name} ORDER BY order_num ASC, question ASC",
            ARRAY_A
        );
        
        wp_send_json_success($results);
    }
    
    public function ajax_reorder_faq_items() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $items = json_decode(stripslashes($_POST['items']), true);
        
        if (!$items || !is_array($items)) {
            wp_send_json_error('Invalid items data');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_faq';
        
        foreach ($items as $item) {
            $wpdb->update(
                $table_name,
                array('order_num' => $item['order']),
                array('id' => $item['id'])
            );
        }
        
        wp_send_json_success('FAQ order updated successfully');
    }
    
    public function ajax_update_faq_category() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $id = sanitize_text_field($_POST['id']);
        $category = sanitize_text_field($_POST['category']);
        
        $faq_table = $wpdb->prefix . 'annotation_faq';
        
        $result = $wpdb->update(
            $faq_table,
            array('category' => $category),
            array('id' => $id)
        );
        
        if ($result !== false) {
            wp_send_json_success('FAQ category updated successfully');
        } else {
            wp_send_json_error('Failed to update FAQ category: ' . $wpdb->last_error);
        }
    }
    
    // Settings AJAX endpoints
    public function ajax_get_contact_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_contact';
        
        $result = $wpdb->get_row("SELECT * FROM $table_name WHERE id = '1'", ARRAY_A);
        
        if (!$result) {
            // Return default values
            $result = array(
                'contactEnabled' => true,
                'title' => 'Get Your Custom Quote',
                'description' => 'Contact our team for personalized pricing based on your specific annotation requirements.',
                'buttonText' => 'Schedule Consultation',
                'buttonUrl' => 'https://calendly.com/your-link'
            );
        } else {
            // Ensure contactEnabled is properly set from database
            $result['contactEnabled'] = isset($result['contact_enabled']) ? (bool)$result['contact_enabled'] : true;
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_save_contact_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_contact';
        
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'button_text' => sanitize_text_field($_POST['buttonText']),
            'button_url' => esc_url_raw($_POST['buttonUrl']),
            'contact_enabled' => isset($_POST['contactEnabled']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $wpdb->replace($table_name, array_merge($data, array('id' => '1')));
        
        wp_send_json_success('Contact settings saved successfully');
    }
    
    public function ajax_get_important_notes() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_notes';
        
        $result = $wpdb->get_row("SELECT * FROM $table_name WHERE id = '1'", ARRAY_A);
        
        if (!$result) {
            // Return default values
            $result = array(
                'title' => 'Important Notes',
                'content' => '<ul><li><strong>Data Access:</strong> Clients provide annotation platform access or DeeLab suggests/sets up platforms as separate project</li><li><strong>Project Scope:</strong> Pricing covers annotation work only, platform setup quoted separately if needed</li><li><strong>Data Requirements:</strong> Clients provide data via cloud folder with access rights or connect cloud to annotation platform</li></ul>',
                'enabled' => true
            );
        }
        
        wp_send_json_success($result);
    }
    
    public function ajax_save_important_notes() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_notes';
        
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'content' => wp_kses_post($_POST['content']),
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $wpdb->replace($table_name, array_merge($data, array('id' => '1')));
        
        wp_send_json_success('Important notes saved successfully');
    }
    
    public function ajax_get_site_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_settings';
        
        $settings = array();
        $defaults = array(
            'title' => 'DeeLab Cost Calculator',
            'description' => 'Get instant pricing estimates for your data annotation projects.',
            'notificationEmails' => '',
            'theme' => 'light',
            'enableAnimations' => true,
            'titleAlignment' => 'center',
            'showCalculatorHeader' => true
        );
        
        foreach ($defaults as $key => $default_value) {
            $value = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $table_name WHERE setting_key = %s",
                'site_' . $key
            ));
            
            $settings[$key] = $value !== null ? $value : $default_value;
        }
        
        wp_send_json_success($settings);
    }
    
    public function ajax_save_site_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'annotation_settings';
        
        $settings = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'notificationEmails' => sanitize_textarea_field($_POST['notificationEmails']),
            'theme' => sanitize_text_field($_POST['theme']),
            'enableAnimations' => isset($_POST['enableAnimations']) ? 1 : 0,
            'titleAlignment' => sanitize_text_field($_POST['titleAlignment'] ?? 'center'),
            'showCalculatorHeader' => isset($_POST['showCalculatorHeader']) ? 1 : 0
        );
        
        foreach ($settings as $key => $value) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_key' => 'site_' . $key,
                    'setting_value' => $value,
                    'updated_at' => current_time('mysql')
                )
            );
        }
        
        wp_send_json_success('Site settings saved successfully');
    }
    
    // FAQ Settings AJAX Handlers
    public function ajax_save_faq_settings() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $faq_enabled = isset($_POST['faqEnabled']) ? (bool)$_POST['faqEnabled'] : true;
        $faq_title = sanitize_text_field($_POST['faqTitle']) ?: 'Frequently Asked Questions';
        
        update_option('annotation_calc_faq_enabled', $faq_enabled);
        update_option('annotation_calc_faq_title', $faq_title);
        
        wp_send_json_success('FAQ settings saved successfully');
    }
    
    public function ajax_get_faq_settings() {
        check_ajax_referer('cost_calc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $settings = array(
            'faqEnabled' => get_option('annotation_calc_faq_enabled', true),
            'faqTitle' => get_option('annotation_calc_faq_title', 'Frequently Asked Questions')
        );
        
        wp_send_json_success($settings);
    }
    
    // FAQ Management AJAX Handlers removed (duplicates eliminated)
    
    // Quote Management AJAX Handlers
    
    public function ajax_get_quotes() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $db = CostCalc_Database::get_instance();
        
        // Get filters from request
        $filters = array();
        if (!empty($_POST['date_filter'])) {
            $filters['date_filter'] = sanitize_text_field($_POST['date_filter']);
        }
        if (!empty($_POST['search'])) {
            $filters['search'] = sanitize_text_field($_POST['search']);
        }
        
        $quotes = $db->get_all_quotes($filters);
        
        wp_send_json_success(array(
            'quotes' => $quotes,
            'total' => count($quotes)
        ));
    }
    
    public function ajax_get_quote_details() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $quote_id = intval($_POST['quote_id']);
        if (!$quote_id) {
            wp_send_json_error('Invalid quote ID');
        }
        
        $db = CostCalc_Database::get_instance();
        $quote = $db->get_quote_by_id($quote_id);
        
        if (!$quote) {
            wp_send_json_error('Quote not found');
        }
        
        wp_send_json_success($quote);
    }
    
    public function ajax_delete_quote() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $quote_id = intval($_POST['quote_id']);
        if (!$quote_id) {
            wp_send_json_error('Invalid quote ID');
        }
        
        $db = CostCalc_Database::get_instance();
        $result = $db->delete_quote($quote_id);
        
        if ($result) {
            wp_send_json_success('Quote deleted successfully');
        } else {
            wp_send_json_error('Failed to delete quote');
        }
    }
    
    public function ajax_export_quotes_csv() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cost_calc_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $db = CostCalc_Database::get_instance();
        $quotes = $db->get_all_quotes();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="quote_requests_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Date',
            'Name', 
            'Email',
            'Company',
            'Phone',
            'Selected Types',
            'Total Cost',
            'Message'
        ));
        
        // CSV data
        foreach ($quotes as $quote) {
            $selected_types = is_array($quote['selected_types']) 
                ? implode('; ', array_map(function($type) { 
                    return $type['name'] . ' (' . $type['quantity'] . ')'; 
                }, $quote['selected_types']))
                : '';
                
            fputcsv($output, array(
                $quote['created_at'],
                $quote['name'],
                $quote['email'],
                $quote['company'],
                $quote['phone'],
                $selected_types,
                '$' . number_format($quote['total_cost'], 2),
                $quote['message']
            ));
        }
        
        fclose($output);
        exit;
    }
}