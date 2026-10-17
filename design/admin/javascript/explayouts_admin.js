/* The explayouts admin views (setup, layout_list, layout_edit, rule_list and the node tab): delegated listeners in
   place of the inline handlers they had, so the pages run under a policy without 'unsafe-inline'. Plain JS, no
   admin design or kernel version assumed; the file sets itself up once even when it is printed twice.
   - data-explayouts-confirm="<text>" on a button or link: a click asks confirm(<text>) and is cancelled when it is
     declined (the same attribute on a form, as admin4's templates have it, is left to explayouts_admin4.js).
   - data-explayouts-href="<url>": a click goes to <url>.
   - data-explayouts-return-to="<url>": a click stores <url> as sessionStorage "explayouts_return_to" (the layout
     editor goes back there) and then follows the link as usual. */
(function () {
    if (window.expLayoutsAdminReady) {
        return;
    }
    window.expLayoutsAdminReady = true;

    function closest(el, attr) {
        while (el && el.nodeType === 1) {
            if (el.hasAttribute(attr)) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }

    document.addEventListener('click', function (e) {
        var el = closest(e.target, 'data-explayouts-confirm');
        // a form's confirmation is asked on submit, by admin4's explayouts_admin4.js, not here
        if (el && el.tagName !== 'FORM' && !window.confirm(el.getAttribute('data-explayouts-confirm'))) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        el = closest(e.target, 'data-explayouts-return-to');
        if (el) {
            try {
                window.sessionStorage.setItem('explayouts_return_to', el.getAttribute('data-explayouts-return-to'));
            } catch (err) {}
        }
        el = closest(e.target, 'data-explayouts-href');
        if (el) {
            e.preventDefault();
            window.location.href = el.getAttribute('data-explayouts-href');
        }
    }, true);
})();
