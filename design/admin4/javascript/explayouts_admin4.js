/* explayouts in admin4, without inline script:
   - the older admin views of the module explayouts: confirmations of forms (data-explayouts-confirm);
   - the Layouts tab of the node view: a link with data-explayouts-return-to keeps that page in sessionStorage
     (explayouts_return_to), where the layouts editor reads the page to go back to. The link opens as usual. */
(function () {
    'use strict';
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var text = form && form.getAttribute && form.getAttribute('data-explayouts-confirm');
        if (text && !window.confirm(text)) e.preventDefault();
    }, true);
    document.addEventListener('click', function (e) {
        var link = e.target && e.target.closest ? e.target.closest('[data-explayouts-return-to]') : null;
        if (!link) return;
        try {
            window.sessionStorage.setItem('explayouts_return_to', link.getAttribute('data-explayouts-return-to'));
        } catch (err) {
            /* no sessionStorage (private mode, blocked storage): the editor falls back to its own default */
        }
    }, true);
})();
