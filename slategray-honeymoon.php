<?php
/**
 * Plugin Name: HoneyMoon By Shakib
 * Plugin URI:  https://www.facebook.com/proshanto.das.176081/
 * Description: Honeymoon couple registration, gift code system, dashboard & withdraw manager. Shortcodes: [honeymoon_login], [honeymoon_registration], [honeymooon_dashboard]
 * Version:     2.0.0
 * Author:      Kanai
 * Author URI:  https://www.facebook.com/proshanto.das.176081/
 * Text Domain: slategray-honeymoon
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Block direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
   PLUGIN CONSTANTS
========================================================= */
define( 'HM_VERSION',  '2.0.0' );
define( 'HM_FILE',     __FILE__ );
define( 'HM_PATH',     plugin_dir_path( __FILE__ ) );
define( 'HM_URL',      plugin_dir_url( __FILE__ ) );
define( 'HM_INCLUDES', HM_PATH . 'includes/' );
define( 'HM_PANEL',    HM_PATH . 'user-panel/' );
define( 'HM_ASSETS',   HM_URL  . 'assets/' );


/* =========================================================
   LOAD MODULES
========================================================= */
require_once HM_INCLUDES . 'helpers.php';
require_once HM_INCLUDES . 'shortcodes.php';
require_once HM_INCLUDES . 'ajax-handlers.php';
require_once HM_INCLUDES . 'woocommerce-hooks.php';
require_once HM_INCLUDES . 'admin-pages.php';


/* =========================================================
   ENQUEUE FRONTEND ASSETS
========================================================= */
function honeymoon_frontend_assets() {

    // Google Sans font (brand font) + Dancing Script for decorative headings
    wp_enqueue_style(
        'honeymoon-google-font',
        'https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Dancing+Script:wght@500;600;700&display=swap',
        array(),
        HM_VERSION
    );

    // Dashicons for sidebar icons on frontend
    wp_enqueue_style( 'dashicons' );

    // Main frontend stylesheet
    wp_enqueue_style(
        'honeymoon-frontend',
        HM_ASSETS . 'css/frontend.css',
        array( 'honeymoon-google-font' ),
        HM_VERSION
    );

    // Main frontend script
    wp_enqueue_script(
        'honeymoon-frontend',
        HM_ASSETS . 'js/frontend.js',
        array( 'jquery' ),
        HM_VERSION,
        true
    );

    // Pass AJAX URL + nonce + redirect URLs to JS
    wp_localize_script(
        'honeymoon-frontend',
        'honeymoon_ajax',
        array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'honeymoon_nonce' ),
            'dashboard_url' => site_url( '/dashboard' ),
            'login_url'     => site_url( '/iniciar-sesion' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'honeymoon_frontend_assets' );


/* =========================================================
   ENQUEUE ADMIN ASSETS  (only on plugin pages)
========================================================= */
function honeymoon_admin_assets( $hook ) {

    // Load only on Honeymoon admin pages to keep WP admin clean
    if ( strpos( $hook, 'hm-' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'honeymoon-admin',
        HM_ASSETS . 'css/admin.css',
        array(),
        HM_VERSION
    );

    wp_enqueue_script(
        'honeymoon-admin',
        HM_ASSETS . 'js/admin.js',
        array( 'jquery' ),
        HM_VERSION,
        true
    );

    wp_localize_script(
        'honeymoon-admin',
        'honeymoon_admin',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'honeymoon_admin_nonce' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'honeymoon_admin_assets' );