/*
 * Embedded players and maps of the video and map blocks that load after a click
 * (templates/explayouts/block/parts/embed.tpl). Until the click the page sends nothing to the
 * other host. No dependencies, no inline code: the template loads this file once per block, and
 * it sets itself up once per page.
 *
 * The placeholder carries the address (data-explayouts-embed, checked on the server; here only
 * http(s) addresses of a host are taken) and the frame's title, allow, width and
 * height. The <iframe> is sandboxed (scripts and its own origin for the player, popups for its
 * links, presentation for full screen; no allow-top-navigation) and sends only the origin as the
 * referrer. The focus moves to the frame, so a keyboard user goes on from where they clicked.
 */
( function () {
    'use strict';

    if ( window.explayoutsEmbed ) {
        return;
    }
    window.explayoutsEmbed = true;

    var SANDBOX = 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-presentation';

    // the server sends addresses of another host only (http(s), //host made https); a page of this site in a frame
    // with scripts and its own origin would be outside every sandbox
    function allowed( src ) {
        return /^https?:\/\/[^\/\\]/i.test( src );
    }

    // "100%", "640" or "640px" (the width as the editor stores it); anything else: the fallback
    function size( value, fallback ) {
        value = String( value || '' ).trim().replace( /px$/i, '' );
        if ( /^\d{1,4}%$/.test( value ) || /^\d{1,5}$/.test( value ) ) {
            return value;
        }
        return fallback;
    }

    function load( placeholder ) {
        var src = placeholder.getAttribute( 'data-explayouts-embed' ) || '';
        if ( !allowed( src ) ) {
            return;
        }
        var frame = document.createElement( 'iframe' );
        frame.src = src;
        frame.title = placeholder.getAttribute( 'data-embed-title' ) || '';
        frame.setAttribute( 'sandbox', SANDBOX );
        frame.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
        frame.setAttribute( 'allowfullscreen', '' );
        frame.setAttribute( 'loading', 'lazy' );
        var allow = placeholder.getAttribute( 'data-embed-allow' ) || '';
        if ( allow !== '' ) {
            frame.setAttribute( 'allow', allow );
        }
        frame.setAttribute( 'width', size( placeholder.getAttribute( 'data-embed-width' ), '100%' ) );
        frame.setAttribute( 'height', size( placeholder.getAttribute( 'data-embed-height' ), '400' ) );
        frame.className = 'explayouts-embed-frame ' + placeholder.className.replace( /\bexplayouts-embed\b/, '' ).trim();
        placeholder.parentNode.replaceChild( frame, placeholder );
        frame.focus();
    }

    document.addEventListener( 'click', function ( event ) {
        var button = event.target.closest ? event.target.closest( '.explayouts-embed-load' ) : null;
        if ( !button ) {
            return;
        }
        var placeholder = button.closest( '[data-explayouts-embed]' );
        if ( placeholder ) {
            event.preventDefault();
            load( placeholder );
        }
    } );
}() );
