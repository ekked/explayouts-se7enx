<!DOCTYPE html>
{* Standalone page (the view sets no pagelayout). Every value the script reads is JSON encoded by the view. The look
   is stylesheets/explayouts_template_editor.css on the --a4-* tokens of components.css, light or dark
   (parts/template_editor_theme.tpl); the editor itself is parts/template_editor_body.tpl. *}
<html lang="{$language_code|wash}" data-a4-color-mode="{ezpreference( 'admin_color_mode' )|wash}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash}</title>
    {include uri='design:explayouts/parts/template_editor_theme.tpl'}
    <link rel="stylesheet" href={'stylesheets/components.css'|ezdesign}>
    <link rel="stylesheet" href={'stylesheets/explayouts_template_editor.css'|ezdesign}>
</head>
<body>
{include uri='design:explayouts/parts/template_editor_body.tpl' i18n_json=$i18n_json files_json=$files_json base_url_json=$base_url_json exit_url_json=$exit_url_json csrf_token_json=$csrf_token_json allow_new_templates=$allow_new_templates}
</body>
</html>
