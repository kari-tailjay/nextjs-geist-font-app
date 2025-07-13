<?php
/**
 * Cost Calculator WordPress Plugin
 *
 * @package           CostCalculator
 * @author            DeeLab
 * @copyright         2025 DeeLab
 * @license           GPL-2.0-or-later
 *
 * Plugin Name:       Cost Calculator
 * Plugin URI:        https://deeplab.ai/plugins/cost-calculator
 * Description:       Professional cost calculator for data annotation services with comprehensive admin management, database storage, and shortcode integration.
 * Version:           2.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            DeeLab
 * Author URI:        https://deeplab.ai
 * Text Domain:       cost-calculator
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network:           false
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// Prevent direct access - WordPress security best practice
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct access denied.' );
}

// Plugin version for cache busting and updates
if ( ! defined( 'COST_CALC_VERSION' ) ) {
    define( 'COST_CALC_VERSION', '2.0.0' );
}

// Plugin directory path - trailing slash for consistency
if ( ! defined( 'COST_CALC_PLUGIN_DIR' ) ) {
    define( 'COST_CALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Plugin URL - trailing slash for consistency
if ( ! defined( 'COST_CALC_PLUGIN_URL' ) ) {
    define( 'COST_CALC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Plugin basename for WordPress hooks
if ( ! defined( 'COST_CALC_PLUGIN_BASENAME' ) ) {
    define( 'COST_CALC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
 * Main Cost Calculator Plugin Class
 *
 * Handles plugin initialization, activation, deactivation, and component loading.
 * Follows WordPress plugin architecture best practices with proper separation of concerns.
 *
 * @since 2.0.0
 */
final class CostCalculatorPlugin {

    /**
     * Plugin instance - Singleton pattern
     *
     * @since 2.0.0
     * @var   CostCalculatorPlugin|null
     */
    private static $instance = null;

    /**
     * Get plugin instance - Singleton pattern for WordPress best practices
     *
     * @since  2.0.0
     * @return CostCalculatorPlugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Private for singleton pattern
     *
     * @since 2.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 2.0.0
     */
    private function init_hooks() {
        // Core initialization
        add_action( 'init', array( $this, 'init' ) );
        
        // Plugin lifecycle hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        // Uninstall hook
        register_uninstall_hook( __FILE__, array( 'CostCalculatorPlugin', 'uninstall' ) );
        
        // Plugin loaded action for extensibility
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
    }

    /**
     * Initialize plugin components
     *
     * Loads all required files and initializes components in proper order.
     * Uses WordPress best practices for file loading and error handling.
     *
     * @since 2.0.0
     */
    public function init() {
        // Verify WordPress environment
        if ( ! $this->check_requirements() ) {
            return;
        }

        // Load core components in dependency order
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Load text domain for internationalization
        $this->load_textdomain();
    }

    /**
     * Check WordPress and PHP requirements
     *
     * @since  2.0.0
     * @return bool True if requirements met, false otherwise
     */
    private function check_requirements() {
        global $wp_version;
        
        // Check WordPress version
        if ( version_compare( $wp_version, '5.0', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
            return false;
        }
        
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return false;
        }
        
        return true;
    }

    /**
     * Load plugin dependencies
     *
     * @since 2.0.0
     */
    private function load_dependencies() {
        $dependencies = array(
            'includes/class-database.php',
            'includes/class-admin.php',
            'includes/class-api.php',
            'includes/class-shortcode.php',
        );

        foreach ( $dependencies as $file ) {
            $filepath = COST_CALC_PLUGIN_DIR . $file;
            
            if ( ! file_exists( $filepath ) ) {
                wp_die(
                    sprintf(
                        /* translators: %s: missing file path */
                        esc_html__( 'Cost Calculator plugin error: Required file %s is missing.', 'cost-calculator' ),
                        esc_html( $file )
                    )
                );
            }
            
            require_once $filepath;
        }
    }

    /**
     * Initialize plugin components
     *
     * @since 2.0.0
     */
    private function init_components() {
        // Initialize in dependency order
        CostCalc_Database::get_instance();
        CostCalc_Admin::get_instance();
        CostCalc_API::get_instance();
        CostCalc_Shortcode::get_instance();
    }

    /**
     * Load plugin text domain for translations
     *
     * @since 2.0.0
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'cost-calculator',
            false,
            dirname( COST_CALC_PLUGIN_BASENAME ) . '/languages/'
        );
    }

    /**
     * Plugin activation handler
     *
     * @since 2.0.0
     */
    public function activate() {
        // Load database class
        require_once COST_CALC_PLUGIN_DIR . 'includes/class-database.php';
        
        // Create database tables
        CostCalc_Database::get_instance()->create_tables();
        
        // Set flag to force data reload and initialize default data
        set_transient( 'cost_calc_force_data_reload', true, 300 ); // 5 minutes
        CostCalc_Database::init_default_data();
        
        // Flush rewrite rules for clean URLs
        flush_rewrite_rules();
        
        // Set activation flag for welcome notice
        set_transient( 'cost_calc_activation_notice', true, 60 );
        
        if ( WP_DEBUG ) {
            error_log( 'Cost Calculator Plugin: Activated with default data initialized' );
        }
    }

    /**
     * Plugin deactivation handler
     *
     * @since 2.0.0
     */
    public function deactivate() {
        // Clean up temporary data
        delete_transient( 'cost_calc_activation_notice' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall handler
     *
     * @since 2.0.0
     */
    public static function uninstall() {
        // Load database class for cleanup
        require_once COST_CALC_PLUGIN_DIR . 'includes/class-database.php';
        
        // Remove database tables and data
        CostCalc_Database::cleanup_on_uninstall();
        
        // Remove all plugin options
        delete_option( 'cost_calc_version' );
        delete_option( 'cost_calc_settings' );
        
        // Clean up transients
        delete_transient( 'cost_calc_activation_notice' );
    }

    /**
     * Plugins loaded hook handler
     *
     * @since 2.0.0
     */
    public function plugins_loaded() {
        /**
         * Fires after Cost Calculator plugin is fully loaded
         *
         * @since 2.0.0
         */
        do_action( 'cost_calculator_loaded' );
    }

    /**
     * WordPress version requirement notice
     *
     * @since 2.0.0
     */
    public function wp_version_notice() {
        $message = sprintf(
            /* translators: %s: required WordPress version */
            esc_html__( 'Cost Calculator requires WordPress %s or higher.', 'cost-calculator' ),
            '5.0'
        );
        printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
    }

    /**
     * PHP version requirement notice
     *
     * @since 2.0.0
     */
    public function php_version_notice() {
        $message = sprintf(
            /* translators: %s: required PHP version */
            esc_html__( 'Cost Calculator requires PHP %s or higher.', 'cost-calculator' ),
            '7.4'
        );
        printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
    }

    /**
     * Prevent cloning - Singleton pattern
     *
     * @since 2.0.0
     */
    private function __clone() {}

    /**
     * Prevent unserialization - Singleton pattern
     *
     * @since 2.0.0
     */
    public function __wakeup() {}
}

/**
 * Initialize the plugin
 *
 * @since 2.0.0
 */
function cost_calculator_init() {
    return CostCalculatorPlugin::get_instance();
}

// Start the plugin
cost_calculator_init();