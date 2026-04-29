/**
 * Mi Luna de Miel — Admin JavaScript
 * Handles: Code copy · Notice auto-dismiss · Search highlight · Confirm dialogs
 *
 * Loaded only on plugin admin pages (hm-* hooks) via honeymoon_admin_assets().
 */

jQuery( document ).ready( function ( $ ) {

    'use strict';


    /* ============================================================
       1. COPY CODE CHIP ON CLICK
       Clicking any .hm-code element in the admin tables copies
       the code text to the clipboard and briefly flashes the chip.
    ============================================================ */
    $( document ).on( 'click', '.hm-code', function () {

        var $chip = $( this );
        var text  = $chip.text().trim();

        if ( ! text ) { return; }

        // Modern clipboard API with legacy fallback
        if ( navigator.clipboard && window.isSecureContext ) {
            navigator.clipboard.writeText( text ).then( function () {
                flashChip( $chip );
            } );
        } else {
            var $tmp = $( '<input>' ).css( { position: 'fixed', opacity: 0 } );
            $( 'body' ).append( $tmp );
            $tmp.val( text ).select();
            document.execCommand( 'copy' );
            $tmp.remove();
            flashChip( $chip );
        }
    } );

    /**
     * Briefly highlight the chip to confirm the copy action.
     * @param {jQuery} $chip
     */
    function flashChip( $chip ) {
        var original = $chip.text();
        $chip
            .text( '✅ Copiado' )
            .css( { background: '#dcfce7', color: '#166534', cursor: 'default' } );

        setTimeout( function () {
            $chip
                .text( original )
                .css( { background: '', color: '', cursor: '' } );
        }, 1800 );
    }

    // Show cursor pointer so it looks clickable
    $( '.hm-code' ).css( 'cursor', 'pointer' ).attr( 'title', 'Clic para copiar' );


    /* ============================================================
       2. AUTO-DISMISS ADMIN NOTICES
       WP success/updated notices (from settings_errors) disappear
       after 4 seconds. Gives the admin a brief confirmation glance.
    ============================================================ */
    var $notices = $( '.notice-success, .updated' );

    if ( $notices.length ) {
        setTimeout( function () {
            $notices.fadeOut( 600 );
        }, 4000 );
    }


    /* ============================================================
       3. SEARCH INPUT — CLEAR BUTTON VISIBILITY
       Show a small "×" hint when the search box has text so the
       admin knows they can clear it (the PHP renders a "Limpiar"
       button, but the UX hint is immediate).
    ============================================================ */
    var $search = $( '.hm-search' );

    $search.on( 'input', function () {
        var hasValue = $( this ).val().trim().length > 0;
        $search.toggleClass( 'hm-search-has-value', hasValue );
    } );

    // Trigger on load in case the field has a pre-filled search value
    $search.trigger( 'input' );


    /* ============================================================
       4. HIGHLIGHT SEARCH TERM IN TABLE
       After a search, wrap matching text in table cells with a
       <mark> so the admin can spot the result quickly.
    ============================================================ */
    var urlParams = new URLSearchParams( window.location.search );
    var query     = urlParams.get( 's' );

    if ( query && query.trim().length > 1 ) {
        var escaped = query.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
        var regex   = new RegExp( '(' + escaped + ')', 'gi' );

        // Only highlight in data cells, not action buttons
        $( '.hm-table tbody td:not(:last-child)' ).each( function () {
            var $td = $( this );

            // Skip cells that contain child elements (avatars, badges, buttons)
            if ( $td.children().length ) { return; }

            var original = $td.text();
            var highlighted = original.replace( regex, '<mark style="background:#fef9c3;padding:0 2px;border-radius:2px;">$1</mark>' );

            if ( highlighted !== original ) {
                $td.html( highlighted );
            }
        } );
    }


    /* ============================================================
       5. CONFIRM DIALOGS FOR DESTRUCTIVE ACTIONS
       The reject button already has onclick="return confirm()"
       from PHP. This upgrades the approve buttons to require a
       single click confirm too — preventing accidental approvals.
    ============================================================ */
    $( document ).on( 'click', 'a.button-primary[href*="hm_action=approve"]', function ( e ) {

        var isReset   = this.href.indexOf( 'reset=1' ) !== -1;
        var actionMsg = isReset
            ? '¿Aprobar y reiniciar el saldo? Esta acción no se puede deshacer.'
            : '¿Confirmar aprobación de esta solicitud?';

        if ( ! window.confirm( actionMsg ) ) {
            e.preventDefault();
        }
    } );


    /* ============================================================
       6. ROW CLICK → VIEW DETAILS  (Couple Manager table)
       Clicking anywhere on a couple's row navigates to their
       detail page — same as clicking the "Ver" button.
       The button itself is excluded to avoid double-navigation.
    ============================================================ */
    $( '.hm-table tbody tr' ).on( 'click', function ( e ) {

        // Ignore clicks on buttons or links inside the row
        if ( $( e.target ).closest( 'a, button' ).length ) { return; }

        var $viewBtn = $( this ).find( 'a.button[href*="hm-view"]' );
        if ( $viewBtn.length ) {
            window.location.href = $viewBtn.attr( 'href' );
        }
    } ).css( 'cursor', 'pointer' );

    // Remove pointer cursor from rows that have no "Ver" button (withdraw table)
    $( '.hm-table tbody tr' ).each( function () {
        if ( ! $( this ).find( 'a[href*="hm-view"]' ).length ) {
            $( this ).css( 'cursor', 'default' );
        }
    } );

} );
