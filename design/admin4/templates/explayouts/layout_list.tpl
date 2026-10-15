{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): layouts with export,
   copy, delete and the JSON import. Same forms and button names as design/admin's template. *}
{ezcss_require( 'explayouts_admin4.css' )}
{ezscript_require( 'explayouts_admin4.js' )}
<div class="context-block a4-page explayouts-a4">
<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Layouts'|i18n( 'design/admin/explayouts/dashboard' )}</h1>
</div></div>
<div class="box-bc"><div class="box-ml"><div class="box-content">
{if $message}<div class="a4-notice is-ok" role="status">{$message|wash}</div>{/if}
{if $error}<div class="a4-notice is-bad" role="alert">{$error|wash}</div>{/if}
<div class="a4-toolbar">
    <div class="a4-actions">
        <a class="a4-btn a4-btn-primary" href={'explayouts/layout_edit/'|ezurl}>{'New layout'|i18n( 'design/admin/explayouts/layout_list' )}</a>
        <a class="a4-btn" href={'explayouts/rule_list'|ezurl}>{'Rules'|i18n( 'design/admin/explayouts/layout_list' )}</a>
        <a class="a4-btn" href={'explayouts/template_editor/'|ezurl}>{'Template editor'|i18n( 'design/admin/explayouts/layout_list' )}</a>
        <a class="a4-btn" href={'explayouts/setup'|ezurl}>{'Setup DB'|i18n( 'design/admin/explayouts/layout_list' )}</a>
    </div>
</div>
{if count( $layouts )}
<div class="a4-table-wrap"><table class="a4-table">
    <thead><tr><th scope="col" class="a4-num">ID</th><th scope="col">{'Identifier'|i18n( 'design/admin/explayouts/layout_list' )}</th><th scope="col">{'Name'|i18n( 'design/admin/explayouts/layout_list' )}</th><th scope="col">{'Type'|i18n( 'design/admin/explayouts/layout_list' )}</th><th scope="col">{'Status'|i18n( 'design/admin/explayouts/layout_list' )}</th><th scope="col"><span class="a4-sr-only">{'Edit'|i18n( 'design/admin/explayouts/layout_list' )}</span></th></tr></thead>
    <tbody>
    {foreach $layouts as $layout}
    <tr>
        <td class="a4-num">{$layout.id|wash}</td>
        <td><code class="a4-code">{$layout.identifier|wash}</code></td>
        <td><a href={concat( 'explayouts/layout_edit/', $layout.id )|ezurl}>{$layout.name|wash}</a></td>
        <td><span class="a4-tag">{$layout.layout_type|wash}</span></td>
        <td>{if eq( $layout.status, 2 )}<span class="a4-badge is-ok">{'Published'|i18n( 'design/admin/explayouts/layout_list' )}</span>{else}<span class="a4-badge is-warn">{'Draft'|i18n( 'design/admin/explayouts/layout_list' )}</span>{/if}</td>
        <td class="a4-end"><div class="a4-actions">
            <a class="a4-btn a4-btn-small" href={concat( 'explayouts/layout_edit/', $layout.id )|ezurl}>{'Edit'|i18n( 'design/admin/explayouts/layout_list' )}</a>
            <a class="a4-btn a4-btn-small a4-btn-ghost" href={concat( 'explayouts/layout_preview/', $layout.id, '/', $layout.status )|ezurl} target="_blank">{'Preview'|i18n( 'design/admin/explayouts/layout_list' )}</a>
            {if eq( $layout.status, 2 )}<a class="a4-btn a4-btn-small a4-btn-ghost" href={concat( 'explayouts_ui_api/app#layout/', $layout.id, '/create_new_draft' )|ezurl}>{'New draft'|i18n( 'design/admin/explayouts/layout_list' )}</a>{else}<a class="a4-btn a4-btn-small a4-btn-ghost" href={concat( 'explayouts_ui_api/app#layout/', $layout.id, '/edit' )|ezurl}>{'Edit in UI'|i18n( 'design/admin/explayouts/layout_list' )}</a>{/if}
            <form method="post" action={'explayouts/layout_list'|ezurl} class="explayouts-a4-inline-form"><input type="hidden" name="ExportLayoutID" value="{$layout.id|wash}" /><button type="submit" name="ExportLayout" class="a4-btn a4-btn-small">{'Export'|i18n( 'design/admin/explayouts/layout_list' )}</button></form>
            <form method="post" action={'explayouts/layout_list'|ezurl} class="explayouts-a4-inline-form"><input type="hidden" name="CopyLayoutID" value="{$layout.id|wash}" /><button type="submit" name="CopyLayout" class="a4-btn a4-btn-small">{'Copy'|i18n( 'design/admin/explayouts/layout_list' )}</button></form>
            <form method="post" action={'explayouts/layout_list'|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Delete this layout and all its zones/blocks?'|i18n( 'design/admin/explayouts/layout_list' )|wash}"><input type="hidden" name="DeleteLayoutID" value="{$layout.id|wash}" /><button type="submit" name="DeleteLayout" class="a4-btn a4-btn-small a4-btn-outline-danger">{'Delete'|i18n( 'design/admin/explayouts/layout_list' )}</button></form>
        </div></td>
    </tr>
    {/foreach}
    </tbody>
</table></div>
{else}
<p class="a4-empty">{'No layouts yet.'|i18n( 'design/admin/explayouts/layout_list' )}</p>
{/if}
<form class="a4-form is-wide a4-section" method="post" action={'explayouts/layout_list'|ezurl}>
    <div class="a4-field a4-field-wide">
        <label class="a4-label" for="import-json">{'Import layout from JSON:'|i18n( 'design/admin/explayouts/layout_list' )}</label>
        <textarea class="a4-input" id="import-json" name="ImportJson" rows="6" placeholder="{'Paste exported layout JSON here'|i18n( 'design/admin/explayouts/layout_list' )}"></textarea>
    </div>
    <div class="a4-actions"><button type="submit" name="ImportLayout" class="a4-btn">{'Import layout'|i18n( 'design/admin/explayouts/layout_list' )}</button></div>
</form>
</div></div></div>
</div>
