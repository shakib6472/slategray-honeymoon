<?php
/**
 * Admin pages:
 *   - Couple Manager           (top-level menu)
 *     - Withdraw Requests      (submenu)
 *     - User Details           (hidden submenu, opened via row action)
 *
 * Approve / Reject withdraw actions are handled here too, with nonce
 * verification and a safe redirect-after-action pattern.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
   REGISTER ADMIN MENU
========================================================= */
function honeymoon_register_admin_menu() {

    add_menu_page(
        'Couple Manager',
        'Couple Manager',
        'manage_options',
        'hm-users',
        'honeymoon_users_page',
        'dashicons-heart',
        6
    );

    add_submenu_page(
        'hm-users',
        'Parejas',
        'Parejas',
        'manage_options',
        'hm-users',
        'honeymoon_users_page'
    );

    add_submenu_page(
        'hm-users',
        'Solicitudes de retiro',
        'Solicitudes de retiro',
        'manage_options',
        'hm-withdraw',
        'honeymoon_withdraw_page'
    );

    // Hidden details page (parent = null so it does not appear in menu)
    add_submenu_page(
        null,
        'Detalles del usuario',
        'Detalles del usuario',
        'manage_options',
        'hm-view',
        'honeymoon_user_detail_page'
    );
}
add_action( 'admin_menu', 'honeymoon_register_admin_menu' );


/* =========================================================
   COUPLES LIST PAGE
========================================================= */
function honeymoon_users_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No autorizado.' );
    }

    $search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
    $paged    = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
    $per_page = 20;

    // Pull only users that have a honeymoon_code (registered couples)
    $args = array(
        'meta_key'     => 'honeymoon_code',
        'meta_compare' => 'EXISTS',
        'number'       => $per_page,
        'offset'       => ( $paged - 1 ) * $per_page,
        'orderby'      => 'registered',
        'order'        => 'DESC',
    );

    if ( ! empty( $search ) ) {
        // Search across email, name and code via meta + email
        $args['search']         = '*' . esc_attr( $search ) . '*';
        $args['search_columns'] = array( 'user_email', 'user_login', 'display_name' );
    }

    $query = new WP_User_Query( $args );
    $users = $query->get_results();
    $total = $query->get_total();
    ?>

    <div class="wrap hm-wrap">

        <h1 class="hm-page-title">Parejas registradas</h1>

        <form method="get" class="hm-search-form">
            <input type="hidden" name="page" value="hm-users">
            <input type="text" name="s" class="hm-search" placeholder="Buscar por correo, nombre o código..." value="<?php echo esc_attr( $search ); ?>">
            <button type="submit" class="button button-primary">Buscar</button>
            <?php if ( $search ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=hm-users' ) ); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="hm-card">
            <table class="widefat hm-table">
                <thead>
                    <tr>
                        <th>Pareja</th>
                        <th>Correo electrónico</th>
                        <th>Código</th>
                        <th>Regalos</th>
                        <th>Saldo</th>
                        <th>Registrado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ( empty( $users ) ) : ?>
                    <tr><td colspan="7" class="hm-empty">No se encontraron parejas.</td></tr>
                <?php else : ?>

                    <?php foreach ( $users as $user ) :
                        $code    = hm_get_user_code( $user->ID );
                        $couple  = hm_get_couple_name( $user->ID );
                        $gifts   = hm_get_total_gifts( $code );
                        $balance = hm_get_total_balance( $code );
                        $view_url = admin_url( 'admin.php?page=hm-view&user_id=' . $user->ID );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $couple ); ?></strong></td>
                            <td><?php echo esc_html( $user->user_email ); ?></td>
                            <td><span class="hm-code"><?php echo esc_html( $code ); ?></span></td>
                            <td><?php echo esc_html( $gifts ); ?></td>
                            <td><?php echo wp_kses_post( hm_format_price( $balance ) ); ?></td>
                            <td><?php echo esc_html( date_i18n( 'd M Y', strtotime( $user->user_registered ) ) ); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url( $view_url ); ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

        <?php
        // Pagination
        $total_pages = ceil( $total / $per_page );
        if ( $total_pages > 1 ) :
            $base = remove_query_arg( 'paged' );
        ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    echo wp_kses_post( paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%', $base ),
                        'format'    => '',
                        'current'   => $paged,
                        'total'     => $total_pages,
                        'prev_text' => '‹',
                        'next_text' => '›',
                    ) ) );
                    ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <?php
}


/* =========================================================
   USER DETAIL PAGE
========================================================= */
function honeymoon_user_detail_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No autorizado.' );
    }

    $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;
    $user    = get_user_by( 'id', $user_id );

    if ( ! $user ) {
        echo '<div class="wrap"><p>Usuario no encontrado.</p></div>';
        return;
    }

    $code    = hm_get_user_code( $user_id );
    $couple  = hm_get_couple_name( $user_id );
    $gifts   = hm_get_total_gifts( $code );
    $balance = hm_get_total_balance( $code );
    $orders  = hm_get_orders_by_code( $code, 20 );
    ?>

    <div class="wrap hm-wrap">

        <h1 class="hm-page-title">Detalles de la pareja</h1>

        <a href="<?php echo esc_url( admin_url( 'admin.php?page=hm-users' ) ); ?>" class="hm-back-btn">← Volver</a>

        <div class="hm-card">

            <div class="hm-detail-grid">
                <div>
                    <span class="hm-label">Pareja</span>
                    <strong><?php echo esc_html( $couple ); ?></strong>
                </div>
                <div>
                    <span class="hm-label">Correo</span>
                    <strong><?php echo esc_html( $user->user_email ); ?></strong>
                </div>
                <div>
                    <span class="hm-label">Código</span>
                    <span class="hm-code"><?php echo esc_html( $code ); ?></span>
                </div>
                <div>
                    <span class="hm-label">Regalos</span>
                    <strong><?php echo esc_html( $gifts ); ?></strong>
                </div>
                <div>
                    <span class="hm-label">Saldo total</span>
                    <strong><?php echo wp_kses_post( hm_format_price( $balance ) ); ?></strong>
                </div>
            </div>

        </div>

        <h2 class="hm-section-title">Pedidos recientes</h2>

        <div class="hm-card">

            <table class="widefat hm-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Comprador</th>
                        <th>Regalo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ( empty( $orders ) ) : ?>
                    <tr><td colspan="6" class="hm-empty">Aún no hay pedidos.</td></tr>
                <?php else : ?>

                    <?php foreach ( $orders as $order ) :
                        $items = $order->get_items();
                        $product = '';
                        foreach ( $items as $item ) {
                            $product = $item->get_name();
                            break;
                        }
                        $buyer = $order->get_billing_first_name() ?: 'Invitado';
                    ?>
                        <tr>
                            <td>#<?php echo esc_html( $order->get_id() ); ?></td>
                            <td><?php echo esc_html( $buyer ); ?></td>
                            <td><?php echo esc_html( $product ); ?></td>
                            <td><?php echo wp_kses_post( hm_format_price( $order->get_total() ) ); ?></td>
                            <td><span class="hm-badge status-<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span></td>
                            <td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd M Y' ) : '' ); ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>
    <?php
}


/* =========================================================
   WITHDRAW REQUESTS PAGE
========================================================= */
function honeymoon_withdraw_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No autorizado.' );
    }

    $filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'pending';
    $allowed = array( 'pending', 'approved', 'rejected' );
    if ( ! in_array( $filter, $allowed, true ) ) {
        $filter = 'pending';
    }

    // Pull users with the matching withdraw_request meta
    $users = get_users( array(
        'meta_key'   => 'withdraw_request',
        'meta_value' => $filter,
    ) );
    ?>

    <div class="wrap hm-wrap">

        <h1 class="hm-page-title">Solicitudes de retiro</h1>

        <?php settings_errors( 'hm_withdraw' ); ?>

        <ul class="hm-tabs">
            <?php foreach ( $allowed as $tab ) :
                $url    = add_query_arg( array( 'page' => 'hm-withdraw', 'status' => $tab ), admin_url( 'admin.php' ) );
                $active = ( $tab === $filter ) ? 'active' : '';
                $label  = hm_withdraw_label( $tab );
            ?>
                <li class="<?php echo esc_attr( $active ); ?>">
                    <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="hm-card">

            <table class="widefat hm-table">
                <thead>
                    <tr>
                        <th>Pareja</th>
                        <th>Correo</th>
                        <th>Código</th>
                        <th>Cantidad</th>
                        <th>Solicitud</th>
                        <?php if ( $filter === 'pending' ) : ?>
                            <th>Acción</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>

                <?php if ( empty( $users ) ) : ?>
                    <tr><td colspan="6" class="hm-empty">No hay solicitudes <?php echo esc_html( strtolower( hm_withdraw_label( $filter ) ) ); ?>.</td></tr>
                <?php else : ?>

                    <?php foreach ( $users as $user ) :
                        $amount = (float) get_user_meta( $user->ID, 'withdraw_amount', true );
                        $code   = hm_get_user_code( $user->ID );
                        $time   = (int) get_user_meta( $user->ID, 'withdraw_time', true );
                        $couple = hm_get_couple_name( $user->ID );

                        $approve_reset_url = wp_nonce_url(
                            admin_url( 'admin.php?page=hm-withdraw&hm_action=approve&reset=1&user_id=' . $user->ID ),
                            'hm_withdraw_action_' . $user->ID
                        );
                        $approve_keep_url = wp_nonce_url(
                            admin_url( 'admin.php?page=hm-withdraw&hm_action=approve&reset=0&user_id=' . $user->ID ),
                            'hm_withdraw_action_' . $user->ID
                        );
                        $reject_url = wp_nonce_url(
                            admin_url( 'admin.php?page=hm-withdraw&hm_action=reject&user_id=' . $user->ID ),
                            'hm_withdraw_action_' . $user->ID
                        );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $couple ); ?></strong></td>
                            <td><?php echo esc_html( $user->user_email ); ?></td>
                            <td><span class="hm-code"><?php echo esc_html( $code ); ?></span></td>
                            <td><?php echo wp_kses_post( hm_format_price( $amount ) ); ?></td>
                            <td><?php echo $time ? esc_html( date_i18n( 'd M Y H:i', $time ) ) : '—'; ?></td>

                            <?php if ( $filter === 'pending' ) : ?>
                                <td>
                                    <a class="button button-primary button-small" href="<?php echo esc_url( $approve_reset_url ); ?>">Aprobar + Reset</a>
                                    <a class="button button-small" href="<?php echo esc_url( $approve_keep_url ); ?>">Solo Aprobar</a>
                                    <a class="button button-small hm-btn-danger" href="<?php echo esc_url( $reject_url ); ?>" onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>
    <?php
}


/* =========================================================
   APPROVE / REJECT HANDLER
   Verifies nonce, performs action, redirects with notice.
========================================================= */
function honeymoon_handle_withdraw_action() {

    if ( ! isset( $_GET['hm_action'], $_GET['user_id'], $_GET['page'] ) ) {
        return;
    }

    if ( $_GET['page'] !== 'hm-withdraw' ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'No autorizado.' );
    }

    $user_id = absint( $_GET['user_id'] );
    $action  = sanitize_key( $_GET['hm_action'] );
    $nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : '';

    if ( ! wp_verify_nonce( $nonce, 'hm_withdraw_action_' . $user_id ) ) {
        wp_die( 'Token de seguridad inválido.' );
    }

    if ( ! get_user_by( 'id', $user_id ) ) {
        wp_die( 'Usuario no encontrado.' );
    }

    if ( $action === 'approve' ) {

        $reset = isset( $_GET['reset'] ) ? absint( $_GET['reset'] ) : 0;

        update_user_meta( $user_id, 'withdraw_request', 'approved' );
        update_user_meta( $user_id, 'withdraw_time',    time() );

        if ( $reset === 1 ) {
            update_user_meta( $user_id, 'withdraw_amount', 0 );
            update_user_meta( $user_id, 'balance_reset',   1 );
        }

        add_settings_error( 'hm_withdraw', 'approved', 'Solicitud aprobada correctamente.', 'success' );

    } elseif ( $action === 'reject' ) {

        update_user_meta( $user_id, 'withdraw_request', 'rejected' );
        update_user_meta( $user_id, 'withdraw_time',    time() );

        add_settings_error( 'hm_withdraw', 'rejected', 'Solicitud rechazada.', 'success' );
    }

    // Redirect back to remove action params from URL (prevents re-submit on refresh)
    set_transient( 'settings_errors', get_settings_errors(), 30 );
    wp_safe_redirect( admin_url( 'admin.php?page=hm-withdraw' ) );
    exit;
}
add_action( 'admin_init', 'honeymoon_handle_withdraw_action' );