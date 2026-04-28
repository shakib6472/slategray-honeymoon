<?php
/**
 * AJAX endpoint handlers.
 *
 * All handlers verify the 'honeymoon_nonce' nonce before doing any work.
 * Errors are returned to the user in Spanish (site language); function
 * names and inline comments stay in English.
 *
 * Endpoints:
 *   honeymoon_login_user        - Authenticate user (email + password)
 *   honeymoon_register_user     - Register new couple
 *   honeymoon_update_profile    - Update logged-in user's profile
 *   honeymoon_withdraw_request  - Submit a withdraw request
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Verify the nonce sent from the frontend forms.
 * Sends a JSON error and dies if the check fails.
 */
function hm_verify_nonce() {

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

    if ( ! wp_verify_nonce( $nonce, 'honeymoon_nonce' ) ) {
        wp_send_json_error( 'Sesión expirada. Recarga la página e inténtalo de nuevo.' );
    }
}


/* =========================================================
   LOGIN
========================================================= */
function honeymoon_login_user() {

    hm_verify_nonce();

    // Note: passwords must NOT pass through sanitize_text_field
    // because it strips special characters that are valid in passwords.
    $email    = sanitize_email( $_POST['email'] ?? '' );
    $password = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
    $remember = ! empty( $_POST['remember'] );

    // Required fields
    if ( empty( $email ) || empty( $password ) ) {
        wp_send_json_error( 'Por favor introduce el correo y la contraseña.' );
    }

    // Basic email format check
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Dirección de correo electrónico no válida.' );
    }

    // Resolve email to a user (we use email as the login identifier)
    $user = get_user_by( 'email', $email );

    if ( ! $user ) {
        wp_send_json_error( 'No existe ninguna cuenta con este correo.' );
    }

    // Authenticate using WP signon
    $creds = array(
        'user_login'    => $user->user_login,
        'user_password' => $password,
        'remember'      => $remember,
    );

    $signon = wp_signon( $creds, is_ssl() );

    if ( is_wp_error( $signon ) ) {
        wp_send_json_error( 'Contraseña incorrecta. Inténtalo de nuevo.' );
    }

    // Success
    wp_send_json_success( array(
        'message'  => 'Sesión iniciada correctamente.',
        'redirect' => site_url( '/dashboard' ),
    ) );
}
add_action( 'wp_ajax_nopriv_honeymoon_login_user', 'honeymoon_login_user' );
add_action( 'wp_ajax_honeymoon_login_user',        'honeymoon_login_user' );


/* =========================================================
   REGISTRATION
========================================================= */
function honeymoon_register_user() {

    hm_verify_nonce();

    // Sanitize inputs (passwords kept raw to preserve special chars)
    $husband_first_name = sanitize_text_field( $_POST['husband_first_name'] ?? '' );
    $husband_last_name  = sanitize_text_field( $_POST['husband_last_name']  ?? '' );
    $wife_first_name    = sanitize_text_field( $_POST['wife_first_name']    ?? '' );
    $wife_last_name     = sanitize_text_field( $_POST['wife_last_name']     ?? '' );
    $email              = sanitize_email( $_POST['email'] ?? '' );
    $password           = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
    $confirm_password   = isset( $_POST['confirm_password'] ) ? (string) $_POST['confirm_password'] : '';

    // Required fields
    if (
        empty( $husband_first_name ) ||
        empty( $husband_last_name )  ||
        empty( $wife_first_name )    ||
        empty( $wife_last_name )     ||
        empty( $email )              ||
        empty( $password )           ||
        empty( $confirm_password )
    ) {
        wp_send_json_error( 'Todos los campos son obligatorios.' );
    }

    // Email format
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'Dirección de correo electrónico no válida.' );
    }

    // Password match
    if ( $password !== $confirm_password ) {
        wp_send_json_error( 'Las contraseñas no coinciden.' );
    }

    // Minimum password strength (basic length check)
    if ( strlen( $password ) < 6 ) {
        wp_send_json_error( 'La contraseña debe tener al menos 6 caracteres.' );
    }

    // Email already in use
    if ( email_exists( $email ) ) {
        wp_send_json_error( 'Este correo ya está registrado.' );
    }

    // Create the WordPress user (email used as username)
    $user_id = wp_create_user( $email, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( $user_id->get_error_message() );
    }

    // Generate unique honeymoon code
    $unique_code = hm_generate_unique_code();

    // Save couple meta
    update_user_meta( $user_id, 'husband_first_name', $husband_first_name );
    update_user_meta( $user_id, 'husband_last_name',  $husband_last_name );
    update_user_meta( $user_id, 'wife_first_name',    $wife_first_name );
    update_user_meta( $user_id, 'wife_last_name',     $wife_last_name );
    update_user_meta( $user_id, 'honeymoon_code',     $unique_code );

    // Friendly display name "Husband & Wife"
    wp_update_user( array(
        'ID'           => $user_id,
        'display_name' => $husband_first_name . ' & ' . $wife_first_name,
    ) );

    // Auto-login the new user
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true, is_ssl() );
    do_action( 'wp_login', $email, get_user_by( 'id', $user_id ) );

    wp_send_json_success( array(
        'message'  => 'Registro exitoso.',
        'code'     => $unique_code,
        'redirect' => site_url( '/dashboard' ),
    ) );
}
add_action( 'wp_ajax_nopriv_honeymoon_register_user', 'honeymoon_register_user' );
add_action( 'wp_ajax_honeymoon_register_user',        'honeymoon_register_user' );


/* =========================================================
   UPDATE PROFILE
========================================================= */
function honeymoon_update_profile() {

    hm_verify_nonce();

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Debes iniciar sesión para actualizar tu perfil.' );
    }

    $user_id = get_current_user_id();

    $husband_first_name = sanitize_text_field( $_POST['husband_first_name'] ?? '' );
    $husband_last_name  = sanitize_text_field( $_POST['husband_last_name']  ?? '' );
    $wife_first_name    = sanitize_text_field( $_POST['wife_first_name']    ?? '' );
    $wife_last_name     = sanitize_text_field( $_POST['wife_last_name']     ?? '' );
    $email              = sanitize_email( $_POST['email'] ?? '' );

    // Validate email
    if ( empty( $email ) || ! is_email( $email ) ) {
        wp_send_json_error( 'Dirección de correo electrónico no válida.' );
    }

    // Block changing email to one already used by another account
    $existing = email_exists( $email );
    if ( $existing && (int) $existing !== $user_id ) {
        wp_send_json_error( 'Este correo ya está en uso por otra cuenta.' );
    }

    // Update couple meta
    update_user_meta( $user_id, 'husband_first_name', $husband_first_name );
    update_user_meta( $user_id, 'husband_last_name',  $husband_last_name );
    update_user_meta( $user_id, 'wife_first_name',    $wife_first_name );
    update_user_meta( $user_id, 'wife_last_name',     $wife_last_name );

    // Update email + display name
    wp_update_user( array(
        'ID'           => $user_id,
        'user_email'   => $email,
        'display_name' => $husband_first_name . ' & ' . $wife_first_name,
    ) );

    wp_send_json_success( 'Perfil actualizado correctamente.' );
}
add_action( 'wp_ajax_honeymoon_update_profile', 'honeymoon_update_profile' );


/* =========================================================
   WITHDRAW REQUEST
========================================================= */
function honeymoon_withdraw_request() {

    hm_verify_nonce();

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'Debes iniciar sesión.' );
    }

    $user_id = get_current_user_id();
    $status  = hm_get_withdraw_status( $user_id );

    // Block duplicate request when one is already pending
    if ( $status === 'pending' ) {
        wp_send_json_error( 'Ya tienes una solicitud pendiente.' );
    }

    $code    = hm_get_user_code( $user_id );
    $balance = hm_get_total_balance( $code );

    if ( $balance <= 0 ) {
        wp_send_json_error( 'No tienes saldo disponible para retirar.' );
    }

    update_user_meta( $user_id, 'withdraw_request', 'pending' );
    update_user_meta( $user_id, 'withdraw_amount',  $balance );
    update_user_meta( $user_id, 'withdraw_time',    time() );

    wp_send_json_success( array(
        'message' => 'Solicitud de retiro enviada correctamente.',
        'status'  => 'pending',
        'label'   => hm_withdraw_label( 'pending' ),
    ) );
}
add_action( 'wp_ajax_honeymoon_withdraw_request', 'honeymoon_withdraw_request' );