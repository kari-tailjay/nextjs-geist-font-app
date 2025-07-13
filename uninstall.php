<?php
/**
 * Cost Calculator Plugin Uninstall Script
 *
 * Handles complete cleanup when the plugin is uninstalled.
 * Removes all database tables, options, and transients created by the plugin.
 * Follows WordPress uninstall best practices with security checks.
 *
 * @package CostCalculator
 * @since   2.0.0
 */

// Security check - ensure this is called by WordPress uninstall process
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit( 'Direct access denied.' );
}

// Verify we have the correct plugin file
if ( ! defined( 'COST_CALC_VERSION' ) ) {
    // Load plugin constants if not already defined
    if ( ! defined( 'COST_CALC_PLUGIN_DIR' ) ) {
        define( 'COST_CALC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    }
    
    // Include database class for cleanup methods
    require_once COST_CALC_PLUGIN_DIR . 'includes/class-database.php';
}

/**
 * Perform complete plugin cleanup
 */
function cost_calc_uninstall_cleanup() {
    global $wpdb;

    // Security check - only administrators can uninstall
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions to uninstall plugin.', 'cost-calculator' ) );
    }

    // Remove all plugin database tables
    $tables_to_remove = array(
        $wpdb->prefix . 'annotation_types',
        $wpdb->prefix . 'annotation_faq', 
        $wpdb->prefix . 'annotation_quotes',
        $wpdb->prefix . 'annotation_settings',
    );

    foreach ( $tables_to_remove as $table_name ) {
        $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %s", $table_name ) );
    }

    // Remove all plugin options
    $options_to_remove = array(
        'cost_calc_version',
        'cost_calc_settings',
        'cost_calc_table_creation_log',
        'annotation_calc_version',
        'annotation_calc_db_version',
    );

    foreach ( $options_to_remove as $option_name ) {
        delete_option( $option_name );
        delete_site_option( $option_name ); // For multisite
    }

    // Remove all plugin transients
    $transients_to_remove = array(
        'cost_calc_activation_notice',
        'cost_calc_admin_notices',
    );

    foreach ( $transients_to_remove as $transient_name ) {
        delete_transient( $transient_name );
        delete_site_transient( $transient_name ); // For multisite
    }

    // Clear any cached data
    wp_cache_flush();

    // Log successful cleanup if debugging is enabled
    if ( WP_DEBUG ) {
        error_log( 'Cost Calculator: Plugin uninstalled and all data cleaned up successfully.' );
    }
}

// Execute cleanup
cost_calc_uninstall_cleanup();