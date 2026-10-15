<!DOCTYPE html>
{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): the template editor. A page
   of its own (a full-height editor does not fit the admin columns), with admin4's tokens, light or dark mode and a
   header that leads back. The editor itself is design/admin's parts/template_editor_body.tpl. *}
<html lang="{$language_code|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash}</title>
    {literal}<script>
    (function () {
        var theme = null;
        try { theme = window.localStorage.getItem('exp-admin4-theme'); } catch (e) {}
        document.documentElement.setAttribute('data-a4-theme', theme === 'dark' ? 'dark' : 'light');
    })();
    </script>{/literal}
    <link rel="stylesheet" href={'stylesheets/components.css'|ezdesign}>
    <link rel="stylesheet" href={'stylesheets/explayouts_template_editor.css'|ezdesign}>
</head>
<body>
<header class="explayouts-te-header">
    <a href={'explayouts/dashboard'|ezurl}>&larr; {'Exponential Layouts Dashboard'|i18n( 'design/admin/explayouts/dashboard' )}</a>
    <span class="explayouts-te-title">{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash}</span>
</header>
{include uri='design:explayouts/parts/template_editor_body.tpl' i18n_json=$i18n_json files_json=$files_json base_url_json=$base_url_json exit_url_json=$exit_url_json csrf_token_json=$csrf_token_json}
</body>
</html>
