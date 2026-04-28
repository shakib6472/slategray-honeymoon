<?php
/**
 * Shortcode handlers.
 * Each shortcode loads a template from /user-panel/ and returns its output.
 *
 * Available shortcodes:
 *   [honeymoon_login]        - Login form
 *   [honeymoon_registration] - Registration form
 *   [honeymooon_dashboard]   - User dashboard (legacy spelling kept for backward compat)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Render a template file from the user-panel directory and return the output.
 * Centralized helper to avoid repeating the ob_start/include/ob_get_clean pattern.
 *
 * @param string $template Filename inside HM_PANEL (without leading slash).
 * @return string
 */
function hm_render_template( $template ) {

    $file = HM_PANEL . $template;

    if ( ! file_exists( $file ) ) {
        return '';
    }

    ob_start();
    include $file;
    return ob_get_clean();
}


/**
 * [honeymoon_login] - Render the login form.
 */
function honeymoon_login_shortcode() {
    return hm_render_template( 'login.php' );
}
add_shortcode( 'honeymoon_login', 'honeymoon_login_shortcode' );


/**
 * [honeymoon_registration] - Render the registration form.
 */
function honeymoon_registration_shortcode() {
    return hm_render_template( 'registration.php' );
}
add_shortcode( 'honeymoon_registration', 'honeymoon_registration_shortcode' );


/**
 * [honeymooon_dashboard] - Render the user dashboard.
 * Note: shortcode spelling kept (three "o") for backward compatibility
 * with pages that already embed the original tag.
 */
function honeymoon_dashboard_shortcode() {
    return hm_render_template( 'honeymoon-dashboard.php' );
}
add_shortcode( 'honeymooon_dashboard', 'honeymoon_dashboard_shortcode' );