<?php
/**
 * Registration form template - rendered by [honeymoon_registration] shortcode.
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
            <p class="hm-auth-sub">Ya tienes una cuenta activa. No necesitas registrarte de nuevo.</p>
        </div>

        <a href="<?php echo esc_url( site_url( '/dashboard' ) ); ?>" class="hm-btn hm-btn-primary">
            Ir al panel
        </a>

        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="hm-btn hm-btn-ghost">
            Cerrar sesión
        </a>

    </div>

<?php else : ?>

    <!-- Success popup overlay (shown after successful registration) -->
    <div id="hm-success-overlay">

        <div class="hm-success-box">

            <div class="hm-success-icon">✨</div>

            <h2>¡Registro exitoso!</h2>
            <p class="hm-success-sub">Este es tu código único de luna de miel:</p>

            <div class="hm-code-box">
                <input type="text" id="hm-user-code" readonly>
                <button type="button" id="hm-copy-code" title="Copiar código">📋</button>
            </div>

            <p class="hm-success-tip">
                Comparte este código con tus invitados para que puedan regalarte.
            </p>

            <a href="<?php echo esc_url( site_url( '/dashboard' ) ); ?>" class="hm-btn hm-btn-primary">
                Ir al panel
            </a>

        </div>

    </div>

    <!-- Registration form -->
    <div class="hm-auth-card hm-auth-card-wide">

        <div class="hm-auth-header">
            <div class="hm-auth-badge">💍</div>
            <h2>Registra tu pareja</h2>
            <p class="hm-auth-sub">Crea tu cuenta y obtén tu código único de regalo</p>
        </div>

        <form id="hm-registration-form" class="hm-form" novalidate>

            <!-- Husband section -->
            <div class="hm-form-section">
                <h3 class="hm-section-label">Datos del esposo</h3>

                <div class="hm-grid-2">
                    <div class="hm-field">
                        <label for="hm-husband-first">Nombre</label>
                        <input
                            type="text"
                            id="hm-husband-first"
                            name="husband_first_name"
                            placeholder="Nombre del esposo"
                            autocomplete="given-name"
                            required
                        >
                    </div>

                    <div class="hm-field">
                        <label for="hm-husband-last">Apellido</label>
                        <input
                            type="text"
                            id="hm-husband-last"
                            name="husband_last_name"
                            placeholder="Apellido del esposo"
                            autocomplete="family-name"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Wife section -->
            <div class="hm-form-section">
                <h3 class="hm-section-label">Datos de la esposa</h3>

                <div class="hm-grid-2">
                    <div class="hm-field">
                        <label for="hm-wife-first">Nombre</label>
                        <input
                            type="text"
                            id="hm-wife-first"
                            name="wife_first_name"
                            placeholder="Nombre de la esposa"
                            autocomplete="given-name"
                            required
                        >
                    </div>

                    <div class="hm-field">
                        <label for="hm-wife-last">Apellido</label>
                        <input
                            type="text"
                            id="hm-wife-last"
                            name="wife_last_name"
                            placeholder="Apellido de la esposa"
                            autocomplete="family-name"
                            required
                        >
                    </div>
                </div>
            </div>

            <!-- Account section -->
            <div class="hm-form-section">
                <h3 class="hm-section-label">Datos de acceso</h3>

                <div class="hm-field">
                    <label for="hm-email">Correo electrónico</label>
                    <input
                        type="email"
                        id="hm-email"
                        name="email"
                        placeholder="tu@correo.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="hm-grid-2">
                    <div class="hm-field">
                        <label for="hm-password">Contraseña</label>
                        <div class="hm-password-wrapper">
                            <input
                                type="password"
                                id="hm-password"
                                name="password"
                                placeholder="Mínimo 6 caracteres"
                                autocomplete="new-password"
                                required
                            >
                            <span class="hm-toggle-pass" data-target="hm-password">👁</span>
                        </div>
                    </div>

                    <div class="hm-field">
                        <label for="hm-confirm-password">Confirmar contraseña</label>
                        <div class="hm-password-wrapper">
                            <input
                                type="password"
                                id="hm-confirm-password"
                                name="confirm_password"
                                placeholder="Repite la contraseña"
                                autocomplete="new-password"
                                required
                            >
                            <span class="hm-toggle-pass" data-target="hm-confirm-password">👁</span>
                        </div>
                    </div>
                </div>

                <small id="hm-pass-error" class="hm-field-hint"></small>
            </div>

            <button type="submit" class="hm-btn hm-btn-primary hm-btn-block">
                Crear mi cuenta
            </button>

            <p class="hm-auth-footer">
                ¿Ya tienes cuenta?
                <a href="<?php echo esc_url( site_url( '/iniciar-sesion' ) ); ?>">Inicia sesión</a>
            </p>

        </form>

    </div>

<?php endif; ?>
