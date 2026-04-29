/**
 * Mi Luna de Miel — Frontend JavaScript
 * Handles: Login · Registration · Profile · Withdraw · UI helpers
 *
 * Requires: jQuery (loaded by WordPress), honeymoon_ajax (wp_localize_script)
 * honeymoon_ajax.ajax_url  — WP AJAX endpoint
 * honeymoon_ajax.nonce     — Security nonce
 * honeymoon_ajax.dashboard_url
 * honeymoon_ajax.login_url
 */

jQuery( document ).ready( function ( $ ) {

    'use strict';

    /* ============================================================
       HELPERS
    ============================================================ */

    /**
     * Show the toast alert.
     * @param {string} message
     * @param {'error'|'ok'} type
     */
    function showAlert( message, type ) {
        var $alert = $( '#hm-server-alert' );

        $alert
            .removeClass( 'hm-alert-error hm-alert-ok hm-alert-show' )
            .find( '.hm-alert-message' ).text( message );

        // Update icon based on type
        $alert.find( '.hm-alert-icon' ).text( type === 'ok' ? '✅' : '❌' );

        $alert.addClass( 'hm-alert-' + type + ' hm-alert-show' );

        // Auto-hide after 4 seconds
        clearTimeout( $alert.data( 'hideTimer' ) );
        $alert.data( 'hideTimer', setTimeout( function () {
            $alert.removeClass( 'hm-alert-show' );
        }, 4000 ) );
    }

    /** Show the full-page loading overlay. */
    function showLoader() {
        $( '.hm-loader-overlay' ).addClass( 'hm-loading' );
    }

    /** Hide the full-page loading overlay. */
    function hideLoader() {
        $( '.hm-loader-overlay' ).removeClass( 'hm-loading' );
    }

    /**
     * Build the base POST data object shared by all AJAX calls.
     * Always includes the nonce.
     * @param {string} action  WP AJAX action name
     * @param {Object} extra   Additional key-value pairs
     * @returns {Object}
     */
    function buildPostData( action, extra ) {
        return $.extend( {
            action: action,
            nonce:  honeymoon_ajax.nonce
        }, extra );
    }


    /* ============================================================
       PASSWORD TOGGLE  (works for any input with data-target)
    ============================================================ */
    $( document ).on( 'click', '.hm-toggle-pass', function () {
        var targetId = $( this ).data( 'target' );
        var $input   = $( '#' + targetId );

        if ( ! $input.length ) { return; }

        var isPassword = $input.attr( 'type' ) === 'password';
        $input.attr( 'type', isPassword ? 'text' : 'password' );
        $( this ).css( 'opacity', isPassword ? '1' : '0.5' );
    } );


    /* ============================================================
       COPY TO CLIPBOARD  (generic — code box + stat card)
    ============================================================ */

    // Registration success popup — copy button
    $( document ).on( 'click', '#hm-copy-code', function () {
        var code = $( '#hm-user-code' ).val();
        copyToClipboard( code, $( this ) );
    } );

    // Dashboard stat card — copy button (data-copy attribute)
    $( document ).on( 'click', '.hm-copy-btn', function () {
        var code = $( this ).data( 'copy' );
        copyToClipboard( code, $( this ) );
    } );

    /**
     * Copy text to clipboard and give brief visual feedback on the button.
     * @param {string} text
     * @param {jQuery} $btn
     */
    function copyToClipboard( text, $btn ) {
        if ( ! text ) { return; }

        var original = $btn.text();

        // Modern clipboard API with legacy execCommand fallback
        if ( navigator.clipboard && window.isSecureContext ) {
            navigator.clipboard.writeText( text ).then( function () {
                $btn.text( '✅' );
                setTimeout( function () { $btn.text( original ); }, 2000 );
            } );
        } else {
            // Fallback for HTTP or older browsers
            var $temp = $( '<input>' );
            $( 'body' ).append( $temp );
            $temp.val( text ).select();
            document.execCommand( 'copy' );
            $temp.remove();
            $btn.text( '✅' );
            setTimeout( function () { $btn.text( original ); }, 2000 );
        }
    }


    /* ============================================================
       DASHBOARD TAB NAVIGATION
    ============================================================ */
    $( document ).on( 'click', '.hm-nav-item[data-tab]', function () {
        var tab = $( this ).data( 'tab' );

        // Update active nav item
        $( '.hm-nav-item' ).removeClass( 'active' );
        $( this ).addClass( 'active' );

        // Show the matching tab panel, hide the rest
        $( '.hm-tab' ).removeClass( 'active' );
        $( '#' + tab ).addClass( 'active' );

        // Persist active tab in sessionStorage so refresh keeps position
        try { sessionStorage.setItem( 'hm_active_tab', tab ); } catch ( e ) {}
    } );

    // Restore tab on page load
    try {
        var savedTab = sessionStorage.getItem( 'hm_active_tab' );
        if ( savedTab && $( '#' + savedTab ).length ) {
            $( '.hm-nav-item' ).removeClass( 'active' );
            $( '.hm-nav-item[data-tab="' + savedTab + '"]' ).addClass( 'active' );
            $( '.hm-tab' ).removeClass( 'active' );
            $( '#' + savedTab ).addClass( 'active' );
        }
    } catch ( e ) {}


    /* ============================================================
       LOGIN FORM
    ============================================================ */
    $( '#hm-login-form' ).on( 'submit', function ( e ) {
        e.preventDefault();

        var email    = $( this ).find( '[name="email"]' ).val().trim();
        var password = $( this ).find( '[name="password"]' ).val();
        var remember = $( this ).find( '[name="remember"]' ).is( ':checked' ) ? 1 : 0;

        // Basic client-side check before hitting the server
        if ( ! email || ! password ) {
            showAlert( 'Por favor introduce el correo y la contraseña.', 'error' );
            return;
        }

        showLoader();

        $.post(
            honeymoon_ajax.ajax_url,
            buildPostData( 'honeymoon_login_user', {
                email:    email,
                password: password,
                remember: remember
            } ),
            function ( response ) {
                hideLoader();

                if ( response.success ) {
                    showAlert( response.data.message, 'ok' );
                    // Short delay so the user sees the success message
                    setTimeout( function () {
                        window.location.href = response.data.redirect || honeymoon_ajax.dashboard_url;
                    }, 800 );
                } else {
                    showAlert( response.data, 'error' );
                }
            }
        ).fail( function () {
            hideLoader();
            showAlert( 'Error de conexión. Inténtalo de nuevo.', 'error' );
        } );
    } );


    /* ============================================================
       REGISTRATION FORM
    ============================================================ */
    $( '#hm-registration-form' ).on( 'submit', function ( e ) {
        e.preventDefault();

        var $form    = $( this );
        var password = $form.find( '[name="password"]' ).val();
        var confirm  = $form.find( '[name="confirm_password"]' ).val();
        var $hint    = $( '#hm-pass-error' );

        // Client-side password match check before AJAX
        $hint.text( '' );
        if ( password !== confirm ) {
            $hint.text( 'Las contraseñas no coinciden.' );
            $form.find( '[name="confirm_password"]' ).focus();
            return;
        }

        if ( password.length < 6 ) {
            $hint.text( 'La contraseña debe tener al menos 6 caracteres.' );
            $form.find( '[name="password"]' ).focus();
            return;
        }

        showLoader();

        $.post(
            honeymoon_ajax.ajax_url,
            buildPostData( 'honeymoon_register_user', $form.serialize() ),
            function ( response ) {
                hideLoader();

                if ( response.success ) {
                    // Show success popup with the unique code
                    $( '#hm-user-code' ).val( response.data.code );
                    $( '#hm-success-overlay' ).addClass( 'hm-visible' );
                } else {
                    showAlert( response.data, 'error' );
                }
            }
        ).fail( function () {
            hideLoader();
            showAlert( 'Error de conexión. Inténtalo de nuevo.', 'error' );
        } );
    } );


    /* ============================================================
       PROFILE UPDATE FORM
    ============================================================ */
    $( '#hm-profile-form' ).on( 'submit', function ( e ) {
        e.preventDefault();

        var $form = $( this );
        var $btn  = $form.find( 'button[type="submit"]' );

        $btn.prop( 'disabled', true ).text( 'Guardando...' );

        $.post(
            honeymoon_ajax.ajax_url,
            buildPostData( 'honeymoon_update_profile', $form.serialize() ),
            function ( response ) {
                $btn.prop( 'disabled', false ).text( 'Guardar cambios' );

                if ( response.success ) {
                    showAlert( 'Perfil actualizado correctamente.', 'ok' );
                } else {
                    showAlert( response.data, 'error' );
                }
            }
        ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Guardar cambios' );
            showAlert( 'Error de conexión. Inténtalo de nuevo.', 'error' );
        } );
    } );


    /* ============================================================
       WITHDRAW REQUEST
    ============================================================ */
    $( '#hm-withdraw-btn' ).on( 'click', function () {
        var $btn = $( this );

        // Already in a non-actionable state
        if ( $btn.prop( 'disabled' ) ) { return; }

        $btn.prop( 'disabled', true ).text( 'Enviando...' );

        $.post(
            honeymoon_ajax.ajax_url,
            buildPostData( 'honeymoon_withdraw_request', {} ),
            function ( response ) {
                if ( response.success ) {
                    // Update button to pending state permanently
                    $btn
                        .text( response.data.label )
                        .addClass( 'pending' )
                        .prop( 'disabled', true );

                    showAlert( response.data.message, 'ok' );
                } else {
                    // Re-enable if the request was blocked (e.g. already pending)
                    $btn.prop( 'disabled', false ).text( 'Solicitar Retiro' );
                    showAlert( response.data, 'error' );
                }
            }
        ).fail( function () {
            $btn.prop( 'disabled', false ).text( 'Solicitar Retiro' );
            showAlert( 'Error de conexión. Inténtalo de nuevo.', 'error' );
        } );
    } );


    /* ============================================================
       SUCCESS OVERLAY — close on backdrop click
    ============================================================ */
    $( '#hm-success-overlay' ).on( 'click', function ( e ) {
        // Only close when clicking the dark backdrop, not the box itself
        if ( $( e.target ).is( '#hm-success-overlay' ) ) {
            $( this ).removeClass( 'hm-visible' );
        }
    } );

} );
