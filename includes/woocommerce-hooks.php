<?php
/**
 * WooCommerce integration:
 *   - Adds a "Gift Code" field on checkout
 *   - Validates the code (must belong to a registered couple)
 *   - Saves the code to the order meta
 *   - Displays the code on admin / customer order screens
 *   - Sends a notification email to the couple when a gift is received
 *
 * All UI strings are in Spanish (site language).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
   ADD GIFT CODE FIELD ON CHECKOUT
========================================================= */
function honeymoon_add_checkout_field( $fields ) {

    $fields['billing']['referral_code'] = array(
        'type'        => 'text',
        'label'       => 'Código de regalo',
        'placeholder' => 'Introduce el código de regalo de los novios',
        'required'    => false,
        'class'       => array( 'form-row-wide', 'hm-referral-field' ),
        'priority'    => 120,
    );

    return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'honeymoon_add_checkout_field' );


/* =========================================================
   VALIDATE GIFT CODE AT CHECKOUT
   If a code is entered but does not match any couple, fail validation.
========================================================= */
function honeymoon_validate_checkout_code() {

    if ( empty( $_POST['referral_code'] ) ) {
        return; // Field is optional - skip validation if empty
    }

    $code = strtoupper( sanitize_text_field( $_POST['referral_code'] ) );
    $user = hm_get_user_by_code( $code );

    if ( ! $user ) {
        wc_add_notice( 'El código de regalo introducido no es válido.', 'error' );
    }
}
add_action( 'woocommerce_checkout_process', 'honeymoon_validate_checkout_code' );


/* =========================================================
   SAVE GIFT CODE TO ORDER META
========================================================= */
function honeymoon_save_order_meta( $order, $data ) {

    // Referral code (normalized to uppercase)
    if ( ! empty( $_POST['referral_code'] ) ) {
        $code = strtoupper( sanitize_text_field( $_POST['referral_code'] ) );
        $order->update_meta_data( '_referral_code', $code );
    }

    // Buyer email (works for both guest and logged-in checkouts)
    if ( ! empty( $data['billing_email'] ) ) {
        $order->update_meta_data( '_referral_used_by_email', sanitize_email( $data['billing_email'] ) );
    }
}
add_action( 'woocommerce_checkout_create_order', 'honeymoon_save_order_meta', 20, 2 );


/* =========================================================
   SHOW GIFT CODE ON ADMIN ORDER PAGE
========================================================= */
function honeymoon_admin_order_meta( $order ) {

    $code = $order->get_meta( '_referral_code' );

    if ( empty( $code ) ) {
        return;
    }

    $couple = hm_get_user_by_code( $code );
    $name   = $couple ? hm_get_couple_name( $couple->ID ) : 'N/A';

    echo '<p><strong>Código de regalo:</strong> ' . esc_html( $code ) . '</p>';
    echo '<p><strong>Pareja:</strong> ' . esc_html( $name ) . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'honeymoon_admin_order_meta' );


/* =========================================================
   GIFT NOTIFICATION EMAIL
   Sent to the couple when their code is used in an order.
========================================================= */
function honeymoon_send_gift_email( $order_id ) {

    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $code = $order->get_meta( '_referral_code' );
    if ( empty( $code ) ) {
        return;
    }

    // Find the couple by code
    $user = hm_get_user_by_code( $code );
    if ( ! $user ) {
        return;
    }

    // Buyer info
    $buyer_name = $order->get_billing_first_name();
    if ( empty( $buyer_name ) ) {
        $buyer_name = 'Invitado';
    }

    // First product name
    $product_name = '';
    foreach ( $order->get_items() as $item ) {
        $product_name = $item->get_name();
        break;
    }

    $price = hm_format_price( $order->get_total() );
    $couple = hm_get_couple_name( $user->ID );

    // Build branded HTML email (mint teal + coral palette)
    $subject = '🎁 Has recibido un regalo de luna de miel';
    $message = honeymoon_build_gift_email( $couple, $buyer_name, $product_name, $price );

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
    );

    wp_mail( $user->user_email, $subject, $message, $headers );
}
add_action( 'woocommerce_thankyou', 'honeymoon_send_gift_email' );


/**
 * Build the gift notification email HTML body.
 * Uses the Mi Luna de Miel brand palette.
 */
function honeymoon_build_gift_email( $couple, $buyer_name, $product_name, $price ) {

    $primary = '#98C7BC';
    $accent  = '#F5A88A';
    $cream   = '#FBEFE2';
    $text    = '#2C2C2C';

    ob_start();
    ?>
    <div style="background:<?php echo esc_attr( $cream ); ?>;padding:40px 20px;font-family:'Google Sans',Arial,sans-serif;color:<?php echo esc_attr( $text ); ?>;">

      <table align="center" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);">

        <!-- Header -->
        <tr>
          <td style="background:<?php echo esc_attr( $primary ); ?>;padding:30px 20px;text-align:center;color:#fff;">
            <h1 style="margin:0;font-size:26px;letter-spacing:1px;font-weight:600;">¡Has recibido un regalo!</h1>
            <p style="margin:6px 0 0;font-size:14px;opacity:.9;">Mi Luna de Miel</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:30px;">

            <p style="font-size:16px;margin:0 0 20px;">
              Hola <strong><?php echo esc_html( $couple ); ?></strong>,
            </p>

            <p style="font-size:15px;line-height:1.6;margin:0 0 25px;">
              Alguien especial ha usado tu código de regalo. Aquí están los detalles:
            </p>

            <table width="100%" cellpadding="12" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
              <tr>
                <td style="background:#f7f7f7;font-weight:600;width:40%;border-radius:6px 0 0 6px;">Invitado</td>
                <td style="border-bottom:1px solid #eee;"><?php echo esc_html( $buyer_name ); ?></td>
              </tr>
              <tr>
                <td style="background:#f7f7f7;font-weight:600;border-radius:6px 0 0 6px;">Regalo</td>
                <td style="border-bottom:1px solid #eee;"><?php echo esc_html( $product_name ); ?></td>
              </tr>
              <tr>
                <td style="background:#f7f7f7;font-weight:600;border-radius:6px 0 0 6px;">Monto</td>
                <td style="font-weight:600;color:<?php echo esc_attr( $accent ); ?>;"><?php echo wp_kses_post( $price ); ?></td>
              </tr>
            </table>

            <div style="text-align:center;margin-top:30px;">
              <a href="<?php echo esc_url( site_url( '/dashboard' ) ); ?>"
                 style="display:inline-block;padding:12px 28px;background:<?php echo esc_attr( $accent ); ?>;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">
                Ver mi panel
              </a>
            </div>

            <p style="margin-top:30px;font-size:13px;color:#777;text-align:center;">
              Gracias por confiar en nosotros para tu luna de miel soñada 💝
            </p>

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:<?php echo esc_attr( $primary ); ?>;padding:18px;text-align:center;color:#fff;font-size:12px;">
            © <?php echo esc_html( date( 'Y' ) ); ?> Mi Luna de Miel. Todos los derechos reservados.
          </td>
        </tr>

      </table>

    </div>
    <?php
    return ob_get_clean();
}