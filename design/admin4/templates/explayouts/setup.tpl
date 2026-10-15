{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): setup. The schema step
   creates the explayouts_* tables only (expLayoutsInstall). Same forms and button names as design/admin's
   template; confirmations through data-explayouts-confirm. *}
{ezcss_require( 'explayouts_admin4.css' )}
{ezscript_require( 'explayouts_admin4.js' )}
<div class="context-block a4-page explayouts-a4">
<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Exponential Layouts Setup'|i18n( 'design/admin/explayouts/setup' )}</h1>
</div></div>
<div class="box-bc"><div class="box-ml"><div class="box-content">
{if $message}<div class="a4-notice is-ok" role="status">{$message|wash}</div>{/if}
{if $error}<div class="a4-notice is-bad" role="alert">{$error|wash}</div>{/if}
<dl class="a4-facts is-rows">
    <dt>{'Detected database type:'|i18n( 'design/admin/explayouts/setup' )}</dt><dd><code class="a4-code">{$db_type|wash}</code></dd>
    {if $schema_file}<dt>{'Schema file:'|i18n( 'design/admin/explayouts/setup' )}</dt><dd><code class="a4-code">{$schema_file|wash}</code></dd>{/if}
</dl>
<div class="a4-actions">
    {if $schema_file}
    <form method="post" action={'explayouts/setup'|ezurl} class="explayouts-a4-inline-form"><button class="a4-btn a4-btn-primary" type="submit" name="InstallSchema" value="1">{'Install / update schema'|i18n( 'design/admin/explayouts/setup' )}</button></form>
    {/if}
    <form method="post" action={'explayouts/setup'|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Install base data? This adds a default homepage layout and catch-all rule.'|i18n( 'design/admin/explayouts/setup' )|wash}"><button class="a4-btn" type="submit" name="InstallBaseData" value="1">{'Install base data'|i18n( 'design/admin/explayouts/setup' )}</button></form>
    <form method="post" action={'explayouts/setup'|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Install test data? This adds demo layouts and resolver rules.'|i18n( 'design/admin/explayouts/setup' )|wash}"><button class="a4-btn" type="submit" name="InstallTestData" value="1">{'Install test data'|i18n( 'design/admin/explayouts/setup' )}</button></form>
    <a class="a4-btn a4-btn-ghost" href={'explayouts/layout_list'|ezurl}>{'Back to layouts'|i18n( 'design/admin/explayouts/setup' )}</a>
</div>
{if not( $schema_file )}<div class="a4-notice is-warn">{'Could not determine a supported schema file for this database.'|i18n( 'design/admin/explayouts/setup' )}</div>{/if}
</div></div></div>
</div>
