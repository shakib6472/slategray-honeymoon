<?php
/**
 * Login form template - rendered by [honeymoon_login] shortcode.
 * If the user is already logged in, shows a friendly card with a
 * button to the dashboard instead of the form.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
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


<?php if ( is_user_logged_in() ) :
    $current = wp_get_current_user();
    $couple  = hm_get_couple_name( $current->ID );
?>

    <!-- Already logged in state -->
    <div class="hm-auth-card hm-logged-card">

        <div class="hm-auth-header">
            <div class="hm-auth-badge">💝</div>
            <h2>¡Hola, <?php echo esc_html( $couple ?: $current->display_name ); ?>!</h2>
            <p class="hm-auth-sub">Ya tienes una sesión activa.</p>
        </div>

        <a href="<?php echo esc_url( site_url( '/dashboard' ) ); ?>" class="hm-btn hm-btn-primary">
            Ir al panel
        </a>

        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="hm-btn hm-btn-ghost">
            Cerrar sesión
        </a>

    </div>

<?php else : ?>

    <!-- Login form -->
    <div class="hm-auth-card">

        <div class="hm-auth-header">
            <div class="hm-auth-badge">🌿</div>
            <h2>Iniciar sesión</h2>
            <p class="hm-auth-sub">Accede a tu panel de luna de miel</p>
        </div>

        <form id="hm-login-form" class="hm-form" novalidate>

            <div class="hm-field">
                <label for="hm-login-email">Correo electrónico</label>
                <input
                    type="email"
                    id="hm-login-email"
                    name="email"
                    placeholder="tu@correo.com"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="hm-field">
                <label for="hm-login-password">Contraseña</label>
                <div class="hm-password-wrapper">
                    <input
                        type="password"
                        id="hm-login-password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <span class="hm-toggle-pass" data-target="hm-login-password">👁</span>
                </div>
            </div>

            <div class="hm-field-row">
                <label class="hm-checkbox">
                    <input type="checkbox" name="remember" value="1">
                    <span>Recuérdame</span>
                </label>

                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="hm-forgot-link">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit" class="hm-btn hm-btn-primary hm-btn-block">
                Iniciar sesión
            </button>

            <p class="hm-auth-footer">
                ¿No tienes cuenta?
                <a href="<?php echo esc_url( site_url( '/registro-mi-luna' ) ); ?>">Regístrate aquí</a>
            </p>

        </form>

    </div>

<?php endif; ?>