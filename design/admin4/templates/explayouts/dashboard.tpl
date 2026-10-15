{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): dashboard. *}
{ezcss_require( 'explayouts_admin4.css' )}
<div class="context-block a4-page explayouts-a4">
<div class="box-header"><div class="box-ml">
<h1 class="context-title">{'Exponential Layouts Dashboard'|i18n( 'design/admin/explayouts/dashboard' )}</h1>
</div></div>
<div class="box-bc"><div class="box-ml"><div class="box-content">
<div class="a4-toolbar">
    <div class="a4-actions">
        <a class="a4-btn" href={'explayouts/layout_list'|ezurl}>{'Layouts'|i18n( 'design/admin/explayouts/dashboard' )}</a>
        <a class="a4-btn" href={'explayouts/rule_list'|ezurl}>{'Rules'|i18n( 'design/admin/explayouts/dashboard' )}</a>
        <a class="a4-btn" href={'explayouts/template_editor/'|ezurl}>{'Template editor'|i18n( 'design/admin/explayouts/dashboard' )}</a>
        <a class="a4-btn" href={'explayouts/setup'|ezurl}>{'Setup DB'|i18n( 'design/admin/explayouts/dashboard' )}</a>
    </div>
</div>
<ul class="a4-figures">
    <li class="a4-figure"><strong>{$counts.layouts|wash}</strong><span>{'Layouts'|i18n( 'design/admin/explayouts/dashboard' )}</span></li>
    <li class="a4-figure"><strong>{$counts.zones|wash}</strong><span>{'Zones'|i18n( 'design/admin/explayouts/dashboard' )}</span></li>
    <li class="a4-figure"><strong>{$counts.blocks|wash}</strong><span>{'Blocks'|i18n( 'design/admin/explayouts/dashboard' )}</span></li>
    <li class="a4-figure"><strong>{$counts.rules|wash}</strong><span>{'Rules'|i18n( 'design/admin/explayouts/dashboard' )}</span></li>
    <li class="a4-figure"><strong>{$counts.collections|wash}</strong><span>{'Collections'|i18n( 'design/admin/explayouts/dashboard' )}</span></li>
</ul>
<section class="a4-section">
<div class="a4-section-head"><h2 class="a4-h2">{'Recent layouts'|i18n( 'design/admin/explayouts/dashboard' )}</h2></div>
{if count( $recent_layouts )}
<div class="a4-table-wrap"><table class="a4-table">
    <thead><tr><th scope="col" class="a4-num">ID</th><th scope="col">{'Name'|i18n( 'design/admin/explayouts/dashboard' )}</th><th scope="col">{'Identifier'|i18n( 'design/admin/explayouts/dashboard' )}</th><th scope="col">{'Modified'|i18n( 'design/admin/explayouts/dashboard' )}</th></tr></thead>
    <tbody>
    {foreach $recent_layouts as $layout}
    <tr><td class="a4-num">{$layout.id|wash}</td><td><a href={concat( 'explayouts/layout_edit/', $layout.id )|ezurl}>{$layout.name|wash}</a></td><td><code class="a4-code">{$layout.identifier|wash}</code></td><td class="a4-nowrap">{$layout.modified|datetime( 'custom', '%Y-%m-%d %H:%i' )}</td></tr>
    {/foreach}
    </tbody>
</table></div>
{else}
<p class="a4-empty">{'No layouts yet.'|i18n( 'design/admin/explayouts/dashboard' )}</p>
{/if}
</section>
</div></div></div>
</div>
