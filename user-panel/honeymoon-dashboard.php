<?php
/**
 * User dashboard template - rendered by [honeymooon_dashboard] shortcode.
 * Redirects to the login page if the visitor is not authenticated.
 * Tabs: Panel (overview), Mis Regalos (gifts), Mi Perfil (profile).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Redirect unauthenticated visitors to the login page
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( site_url( '/iniciar-sesion' ) );
    exit;
}

/* ----------------------------------------------------------
   Gather all data we need before rendering any HTML
---------------------------------------------------------- */
$user_id  = get_current_user_id();
$user     = wp_get_current_user();
$couple   = hm_get_couple_name( $user_id );
$code     = hm_get_user_code( $user_id );
$gifts    = hm_get_total_gifts( $code );
$balance  = hm_get_total_balance( $code );
$status   = hm_get_withdraw_status( $user_id );
$w_amount = (float) get_user_meta( $user_id, 'withdraw_amount', true );

// Husband / wife meta for profile form
$husband_first = get_user_meta( $user_id, 'husband_first_name', true );
$husband_last  = get_user_meta( $user_id, 'husband_last_name',  true );
$wife_first    = get_user_meta( $user_id, 'wife_first_name',    true );
$wife_last     = get_user_meta( $user_id, 'wife_last_name',     true );

// One-time balance-reset notice: admin approved + reset the balance
$balance_was_reset = (bool) get_user_meta( $user_id, 'balance_reset', true );
if ( $balance_was_reset ) {
    delete_user_meta( $user_id, 'balance_reset' );
}

// Recent orders for gifts tab (last 20)
$orders = hm_get_orders_by_code( $code, 20 );
?>

<!-- Toast / server alert (shared component) -->
<div id="hm-server-alert" class="hm-alert">
    <span class="hm-alert-icon">ℹ️</span>
    <p class="hm-alert-message">Por favor completa todos los campos.</p>
</div>

<!-- Loader overlay -->
<div class="hm-loader-overlay">
    <div class="hm-loader"></div>
</div>

<!-- Dashboard wrapper -->
<div class="hm-dashboard">

    <!-- ===================== SIDEBAR ===================== -->
    <aside class="hm-sidebar">

        <div class="hm-sidebar-brand">
            <span class="hm-sidebar-logo">💝</span>
            <span>Mi Luna de Miel</span>
        </div>

        <nav>
            <ul class="hm-sidebar-nav">
                <li class="hm-nav-item active" data-tab="overview">
                    <span class="dashicons dashicons-dashboard"></span>
                    Panel
                </li>
                <li class="hm-nav-item" data-tab="gifts">
                    <span class="dashicons dashicons-heart"></span>
                    Mis Regalos
                    <?php if ( $gifts > 0 ) : ?>
                        <span class="hm-nav-badge"><?php echo esc_html( $gifts ); ?></span>
                    <?php endif; ?>
                </li>
                <li class="hm-nav-item" data-tab="profile">
                    <span class="dashicons dashicons-admin-users"></span>
                    Mi Perfil
                </li>
            </ul>
        </nav>

        <div class="hm-sidebar-footer">
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="hm-logout-link">
                <span class="dashicons dashicons-migrate"></span>
                Cerrar sesión
            </a>
        </div>

    </aside>


    <!-- ===================== CONTENT ===================== -->
    <main class="hm-dashboard-content">

        <!-- -------- OVERVIEW TAB -------- -->
        <div class="hm-tab active" id="overview">

            <div class="hm-content-header">
                <div>
                    <h2 class="hm-page-heading">
                        ¡Bienvenidos, <?php echo esc_html( $couple ); ?>! 👋
                    </h2>
                    <p class="hm-page-sub">Aquí puedes gestionar tus regalos de luna de miel.</p>
                </div>
            </div>

            <?php if ( $balance_was_reset ) : ?>
                <div class="hm-notice hm-notice-success">
                    ✅ Tu saldo ha sido reiniciado por el administrador.
                </div>
            <?php endif; ?>

            <!-- Stats cards -->
            <div class="hm-stats-grid">

                <div class="hm-stat-card">
                    <div class="hm-stat-icon">🎁</div>
                    <div class="hm-stat-body">
                        <span class="hm-stat-label">Total de regalos</span>
                        <span class="hm-stat-value"><?php echo esc_html( $gifts ); ?></span>
                    </div>
                </div>

                <div class="hm-stat-card hm-stat-card-code">
                    <div class="hm-stat-icon">🔑</div>
                    <div class="hm-stat-body">
                        <span class="hm-stat-label">Tu código único</span>
                        <div class="hm-inline-code">
                            <span id="hm-dashboard-code" class="hm-stat-value hm-code-display">
                                <?php echo esc_html( $code ); ?>
                            </span>
                            <button type="button" class="hm-copy-btn" data-copy="<?php echo esc_attr( $code ); ?>" title="Copiar código">
                                📋
                            </button>
                        </div>
                    </div>
                </div>

                <div class="hm-stat-card">
                    <div class="hm-stat-icon">💰</div>
                    <div class="hm-stat-body">
                        <span class="hm-stat-label">Saldo total</span>
                        <span class="hm-stat-value">
                            <?php echo wp_kses_post( hm_format_price( $balance ) ); ?>
                        </span>
                    </div>
                </div>

            </div>

            <!-- Withdraw section -->
            <div class="hm-withdraw-card">

                <div class="hm-withdraw-info">
                    <h4>Solicitud de retiro</h4>

                    <?php if ( $status === 'pending' ) : ?>
                        <p>Tu solicitud de <strong><?php echo wp_kses_post( hm_format_price( $w_amount ) ); ?></strong> está siendo procesada.</p>
                    <?php elseif ( $status === 'approved' ) : ?>
                        <p>Tu última solicitud fue <strong>aprobada</strong>. ¡Felicidades!</p>
                    <?php elseif ( $status === 'rejected' ) : ?>
                        <p>Tu solicitud fue rechazada. Puedes volver a intentarlo en 24 horas.</p>
                    <?php else : ?>
                        <p>Puedes solicitar el retiro de tu saldo acumulado: <strong><?php echo wp_kses_post( hm_format_price( $balance ) ); ?></strong></p>
                    <?php endif; ?>
                </div>

                <button
                    type="button"
                    id="hm-withdraw-btn"
                    class="hm-btn hm-btn-withdraw <?php echo esc_attr( $status ); ?>"
                    <?php echo ( $status === 'pending' || $status === 'rejected' ) ? 'disabled' : ''; ?>
                    data-status="<?php echo esc_attr( $status ); ?>"
                >
                    <?php echo esc_html( hm_withdraw_label( $status ) ); ?>
                </button>

            </div>

        </div><!-- / #overview -->


        <!-- -------- GIFTS TAB -------- -->
        <div class="hm-tab" id="gifts">

            <div class="hm-content-header">
                <h2 class="hm-page-heading">Mis Regalos</h2>
                <p class="hm-page-sub">Historial de regalos recibidos con tu código.</p>
            </div>

            <div class="hm-gift-list">

                <?php if ( empty( $orders ) ) : ?>

                    <div class="hm-empty-state">
                        <div class="hm-empty-icon">🎀</div>
                        <p>Aún no has recibido regalos.</p>
                        <small>Comparte tu código <strong><?php echo esc_html( $code ); ?></strong> con tus invitados.</small>
                    </div>

                <?php else : ?>

                    <?php foreach ( $orders as $order ) :
                        $name    = $order->get_billing_first_name() ?: 'Invitado';
                        $email   = $order->get_billing_email();
                        $avatar  = get_avatar_url( $email, array( 'size' => 48, 'default' => 'mp' ) );
                        $price   = hm_format_price( $order->get_total() );
                        $date    = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd M Y' ) : '';

                        // First product name
                        $product = '';
                        foreach ( $order->get_items() as $item ) {
                            $product = $item->get_name();
                            break;
                        }
                    ?>

                        <div class="hm-gift-item">
                            <img
                                src="<?php echo esc_url( $avatar ); ?>"
                                alt="<?php echo esc_attr( $name ); ?>"
                                class="hm-gift-avatar"
                            >
                            <div class="hm-gift-details">
                                <strong class="hm-gift-name"><?php echo esc_html( $name ); ?></strong>
                                <span class="hm-gift-product"><?php echo esc_html( $product ); ?></span>
                            </div>
                            <div class="hm-gift-meta">
                                <span class="hm-gift-price"><?php echo wp_kses_post( $price ); ?></span>
                                <span class="hm-gift-date"><?php echo esc_html( $date ); ?></span>
                            </div>
                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div><!-- / #gifts -->


        <!-- -------- PROFILE TAB -------- -->
        <div class="hm-tab" id="profile">

            <div class="hm-content-header">
                <h2 class="hm-page-heading">Mi Perfil</h2>
                <p class="hm-page-sub">Actualiza los datos de tu cuenta.</p>
            </div>

            <div class="hm-profile-card">

                <form id="hm-profile-form" class="hm-form" novalidate>

                    <div class="hm-form-section">
                        <h3 class="hm-section-label">Datos del esposo</h3>
                        <div class="hm-grid-2">
                            <div class="hm-field">
                                <label>Nombre</label>
                                <input type="text" name="husband_first_name"
                                    value="<?php echo esc_attr( $husband_first ); ?>"
                                    placeholder="Nombre del esposo">
                            </div>
                            <div class="hm-field">
                                <label>Apellido</label>
                                <input type="text" name="husband_last_name"
                                    value="<?php echo esc_attr( $husband_last ); ?>"
                                    placeholder="Apellido del esposo">
                            </div>
                        </div>
                    </div>

                    <div class="hm-form-section">
                        <h3 class="hm-section-label">Datos de la esposa</h3>
                        <div class="hm-grid-2">
                            <div class="hm-field">
                                <label>Nombre</label>
                                <input type="text" name="wife_first_name"
                                    value="<?php echo esc_attr( $wife_first ); ?>"
                                    placeholder="Nombre de la esposa">
                            </div>
                            <div class="hm-field">
                                <label>Apellido</label>
                                <input type="text" name="wife_last_name"
                                    value="<?php echo esc_attr( $wife_last ); ?>"
                                    placeholder="Apellido de la esposa">
                            </div>
                        </div>
                    </div>

                    <div class="hm-form-section">
                        <h3 class="hm-section-label">Correo electrónico</h3>
                        <div class="hm-field">
                            <label>Correo</label>
                            <input type="email" name="email"
                                value="<?php echo esc_attr( $user->user_email ); ?>"
                                placeholder="tu@correo.com"
                                autocomplete="email">
                        </div>
                    </div>

                    <button type="submit" class="hm-btn hm-btn-primary">
                        Guardar cambios
                    </button>

                </form>

            </div>

        </div><!-- / #profile -->

    </main>

</div><!-- / .hm-dashboard -->
