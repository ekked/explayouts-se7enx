{* The template editor's color mode, set before anything is drawn (a page drawn light and then switched would
   flash): html[data-a4-theme] light or dark, what components.css and explayouts_template_editor.css read. The
   choice is the signed-in user's eZPreference admin_color_mode (html[data-a4-color-mode], written by the page),
   else this browser's localStorage "exp-admin4-theme" (admin4's switch), light by default; "system" follows
   prefers-color-scheme. The one script of the page that cannot wait; no <style>. *}
{literal}<script>
(function () {
    var root = document.documentElement, choice = root.getAttribute('data-a4-color-mode') || '';
    if (!choice) { try { choice = window.localStorage.getItem('exp-admin4-theme') || ''; } catch (e) {} }
    var dark = choice === 'dark' || ( choice === 'system' && !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches );
    root.setAttribute('data-a4-theme', dark ? 'dark' : 'light');
})();
</script>{/literal}
