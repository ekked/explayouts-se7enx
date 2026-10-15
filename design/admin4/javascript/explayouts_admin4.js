/* The older admin views of the module explayouts in admin4: confirmations of forms (data-explayouts-confirm). */
(function () {
    'use strict';
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var text = form && form.getAttribute && form.getAttribute('data-explayouts-confirm');
        if (text && !window.confirm(text)) e.preventDefault();
    }, true);
})();
