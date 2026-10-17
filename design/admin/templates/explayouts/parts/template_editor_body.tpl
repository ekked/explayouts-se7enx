{* The template editor itself (module explayouts, view template_editor): file list, toolbar, editing area and
   script. One file for every admin design; design/admin's and design/admin4's template_editor.tpl draw the page
   around it. *}
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>{'7x Template Editor'|i18n( 'design/admin/explayouts/template_editor' )|wash} <small id="tpl-count"></small></h1>
            <input type="text" id="filter" placeholder="{'Filter templates...'|i18n( 'design/admin/explayouts/template_editor' )|wash}" autocomplete="off">
        </div>
        <nav class="flat-list" id="tree"></nav>
        <div class="resizer" id="resizer"></div>
    </aside>
    <main class="main">
        <div class="toolbar">
            {* New only with [TemplateEditorSettings] AllowNewTemplates=enabled; the server refuses to create a file otherwise. *}
            {if $allow_new_templates}<button class="btn secondary" id="new-file">{'New'|i18n( 'design/admin/explayouts/template_editor' )|wash}</button>{/if}
            <button class="btn" id="save-file">{'Save'|i18n( 'design/admin/explayouts/template_editor' )|wash}</button>
            <button class="btn secondary exit-btn" id="exit-editor" title="{'Return to admin'|i18n( 'design/admin/explayouts/template_editor' )|wash}">×</button>
            <span class="path" id="path">{'No file selected'|i18n( 'design/admin/explayouts/template_editor' )|wash}</span>
            <span class="status" id="status"></span>
        </div>
        <div class="editor-wrap" id="editor-wrap">
            <div class="empty">{'Select a template from the list to start editing.'|i18n( 'design/admin/explayouts/template_editor' )|wash}</div>
        </div>
    </main>
</div>

{* The values of the script, JSON from the view (json_encode with the HEX flags: no <, >, & or quote survives raw),
   read by javascript/explayouts_template_editor.js. *}
<script type="application/json" id="explayouts-te-data">{ldelim}"i18n": {$i18n_json}, "files": {$files_json}, "base_url": {$base_url_json}, "exit_url": {$exit_url_json}, "csrf_token": {$csrf_token_json}{rdelim}</script>
<script src={'javascript/explayouts_template_editor.js'|ezdesign}></script>
