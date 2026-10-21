/*
 * Lightbox of the gallery blocks (gallery, slider, thumb_gallery, grid_gallery, sushi_bar) with
 * "Enable lightbox" on. No dependencies, no inline code: the templates load this file once per
 * block (it sets itself up once per page) and mark each image link with the class
 * explayouts-lightbox, the group data-gallery and the node's address data-node-url; the texts
 * are data-label-* attributes of the block.
 *
 * A native <dialog> shown with showModal(): the browser keeps the focus inside it and closes it
 * on Esc; the arrow keys and the buttons go to the previous and next image of the same block,
 * and on closing the focus returns to the link that opened it. A click with a modifier key, or
 * without <dialog> support, follows the link to the original image as before.
 */
( function () {
    'use strict';

    if ( window.explayoutsLightbox ) {
        return;
    }

    var dialog = null;
    var image = null;
    var captionLink = null;
    var captionText = null;
    var position = null;
    var previous = null;
    var next = null;
    var links = [];
    var index = 0;
    var opener = null;
    var labels = {};

    function label( name, fallback ) {
        return labels[ name ] || fallback;
    }

    function button( className, text, glyph ) {
        var element = document.createElement( 'button' );
        element.type = 'button';
        element.className = className;
        element.setAttribute( 'aria-label', text );
        element.title = text;
        element.textContent = glyph;
        return element;
    }

    function build() {
        dialog = document.createElement( 'dialog' );
        dialog.className = 'explayouts-lightbox-dialog';

        var figure = document.createElement( 'figure' );
        image = document.createElement( 'img' );
        image.alt = '';
        var caption = document.createElement( 'figcaption' );
        captionText = document.createElement( 'span' );
        captionLink = document.createElement( 'a' );
        position = document.createElement( 'span' );
        position.className = 'explayouts-lightbox-position';
        position.setAttribute( 'aria-live', 'polite' );
        caption.appendChild( captionText );
        caption.appendChild( captionLink );
        caption.appendChild( position );
        figure.appendChild( image );
        figure.appendChild( caption );

        var close = button( 'explayouts-lightbox-close', label( 'close', 'Close' ), '×' );
        previous = button( 'explayouts-lightbox-previous', label( 'previous', 'Previous image' ), '‹' );
        next = button( 'explayouts-lightbox-next', label( 'next', 'Next image' ), '›' );

        dialog.appendChild( close );
        dialog.appendChild( previous );
        dialog.appendChild( figure );
        dialog.appendChild( next );
        document.body.appendChild( dialog );

        close.addEventListener( 'click', function () { dialog.close(); } );
        previous.addEventListener( 'click', function () { show( index - 1 ); } );
        next.addEventListener( 'click', function () { show( index + 1 ); } );
        dialog.addEventListener( 'keydown', function ( event ) {
            if ( event.key === 'ArrowLeft' ) {
                event.preventDefault();
                show( index - 1 );
            } else if ( event.key === 'ArrowRight' ) {
                event.preventDefault();
                show( index + 1 );
            }
        } );
        // a click on the backdrop (the dialog itself, outside its content) closes it
        dialog.addEventListener( 'click', function ( event ) {
            if ( event.target === dialog ) {
                dialog.close();
            }
        } );
        dialog.addEventListener( 'close', function () {
            image.removeAttribute( 'src' );
            if ( opener && typeof opener.focus === 'function' ) {
                opener.focus();
            }
            opener = null;
        } );
    }

    function show( newIndex ) {
        if ( !links.length ) {
            return;
        }
        index = ( newIndex + links.length ) % links.length;
        var link = links[ index ];
        var thumbnail = link.querySelector( 'img' );
        var title = link.getAttribute( 'title' ) || ( thumbnail ? thumbnail.getAttribute( 'alt' ) : '' ) || '';

        image.src = link.getAttribute( 'href' );
        image.alt = title;

        var nodeUrl = link.getAttribute( 'data-node-url' );
        if ( nodeUrl ) {
            captionLink.href = nodeUrl;
            captionLink.textContent = title || label( 'open', 'Open page' );
            captionLink.hidden = false;
            captionText.hidden = true;
        } else {
            captionLink.hidden = true;
            captionText.textContent = title;
            captionText.hidden = false;
        }

        var many = links.length > 1;
        previous.hidden = !many;
        next.hidden = !many;
        position.textContent = many
            ? label( 'position', '%current of %total' ).replace( '%current', String( index + 1 ) ).replace( '%total', String( links.length ) )
            : '';
    }

    function open( link ) {
        var block = link.closest( '[data-lightbox]' );
        labels = {};
        if ( block ) {
            [ 'dialog', 'close', 'previous', 'next', 'open', 'position' ].forEach( function ( name ) {
                var value = block.getAttribute( 'data-label-' + name );
                if ( value ) {
                    labels[ name ] = value;
                }
            } );
        }
        if ( !dialog ) {
            build();
        }
        dialog.setAttribute( 'aria-label', label( 'dialog', 'Image viewer' ) );

        var group = link.getAttribute( 'data-gallery' );
        links = Array.prototype.filter.call( document.querySelectorAll( 'a.explayouts-lightbox' ), function ( candidate ) {
            return candidate.getAttribute( 'data-gallery' ) === group;
        } );
        opener = link;
        show( Math.max( 0, links.indexOf( link ) ) );
        dialog.showModal();
    }

    document.addEventListener( 'click', function ( event ) {
        if ( event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
            return;
        }
        var link = event.target.closest ? event.target.closest( 'a.explayouts-lightbox' ) : null;
        if ( !link || typeof HTMLDialogElement === 'undefined' ) {
            return;
        }
        event.preventDefault();
        open( link );
    } );

    window.explayoutsLightbox = { open: open };
}() );
