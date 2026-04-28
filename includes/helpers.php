<?php
/**
 * Reusable helper functions used across the plugin.
 * All functions are prefixed with hm_ to avoid global namespace collisions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Generate a unique 8-character honeymoon code.
 * Loops until a code that does not exist in user_meta is found.
 *
 * @return string e.g. "A8D2K9LQ"
 */
function hm_generate_unique_code() {

    do {
        $code = strtoupper( wp_generate_password( 8, false, false ) );

        $exists = get_users( array(
            'meta_key'   => 'honeymoon_code',
            'meta_value' => $code,
            'number'     => 1,
            'fields'     => 'ID',
        ) );

    } while ( ! empty( $exists ) );

    return $code;
}


/**
 * Get the honeymoon code for a given user.
 *
 * @param int $user_id
 * @return string Code or empty string.
 */
function hm_get_user_code( $user_id ) {
    return get_user_meta( $user_id, 'honeymoon_code', true );
}


/**
 * Find a user by their honeymoon code.
 *
 * @param string $code
 * @return WP_User|null
 */
function hm_get_user_by_code( $code ) {

    if ( empty( $code ) ) {
        return null;
    }

    $users = get_users( array(
        'meta_key'   => 'honeymoon_code',
        'meta_value' => $code,
        'number'     => 1,
    ) );

    return ! empty( $users ) ? $users[0] : null;
}


/**
 * Get all orders that used a given referral code.
 * Only counts completed and processing orders.
 *
 * @param string $code
 * @param int    $limit  -1 for all
 * @return array WC_Order[]
 */
function hm_get_orders_by_code( $code, $limit = -1 ) {

    if ( empty( $code ) || ! function_exists( 'wc_get_orders' ) ) {
        return array();
    }

    return wc_get_orders( array(
        'limit'      => $limit,
        'status'     => array( 'wc-completed', 'wc-processing' ),
        'meta_query' => array(
            array(
                'key'   => '_referral_code',
                'value' => $code,
            ),
        ),
    ) );
}


/**
 * Count gift orders for a code.
 *
 * @param string $code
 * @return int
 */
function hm_get_total_gifts( $code ) {
    return count( hm_get_orders_by_code( $code ) );
}


/**
 * Calculate the total balance (sum of all order totals) for a code.
 *
 * @param string $code
 * @return float
 */
function hm_get_total_balance( $code ) {

    $orders = hm_get_orders_by_code( $code );
    $total  = 0;

    foreach ( $orders as $order ) {
        $total += (float) $order->get_total();
    }

    return $total;
}


/**
 * Build the formatted couple name "Husband & Wife".
 * Falls back to display name or email prefix if meta is empty.
 *
 * @param int $user_id
 * @return string
 */
function hm_get_couple_name( $user_id ) {

    $husband = get_user_meta( $user_id, 'husband_first_name', true );
    $wife    = get_user_meta( $user_id, 'wife_first_name', true );

    if ( $husband && $wife ) {
        return $husband . ' & ' . $wife;
    }

    if ( $husband ) {
        return $husband;
    }

    if ( $wife ) {
        return $wife;
    }

    $user = get_user_by( 'id', $user_id );
    return $user ? $user->display_name : '';
}


/**
 * Get current withdraw request status for a user.
 * Auto-clears rejected status after 24 hours.
 *
 * @param int $user_id
 * @return string '', 'pending', 'approved', 'rejected'
 */
function hm_get_withdraw_status( $user_id ) {

    $status = get_user_meta( $user_id, 'withdraw_request', true );
    $time   = (int) get_user_meta( $user_id, 'withdraw_time', true );

    // Auto-reset rejected requests after 24 hours
    if ( $status === 'rejected' && $time && ( time() - $time ) > DAY_IN_SECONDS ) {
        delete_user_meta( $user_id, 'withdraw_request' );
        delete_user_meta( $user_id, 'withdraw_time' );
        return '';
    }

    return $status ?: '';
}


/**
 * Render a price using WooCommerce currency formatter when available.
 * Falls back to a simple format otherwise.
 *
 * @param float $amount
 * @return string
 */
function hm_format_price( $amount ) {

    if ( function_exists( 'wc_price' ) ) {
        return wc_price( $amount );
    }

    return '$' . number_format( (float) $amount, 2 );
}


/**
 * Translatable label for a withdraw status (Spanish UI).
 *
 * @param string $status
 * @return string
 */
function hm_withdraw_label( $status ) {

    switch ( $status ) {
        case 'pending':
            return 'Pendiente...';
        case 'approved':
            return 'Aprobado';
        case 'rejected':
            return 'Rechazado';
        default:
            return 'Solicitar Retiro';
    }
}