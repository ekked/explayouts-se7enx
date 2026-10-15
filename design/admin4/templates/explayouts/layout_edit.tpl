{* admin4 (module explayouts, the older admin views; explayouts_ui has the current ones): Edit layout (explayouts_ui/layout_edit), the form version of the layout editor: its settings, then one
   card per zone with its blocks. Same forms and button names as design/admin's template; confirmations through
   data-explayouts-confirm (javascript/explayouts_admin4.js). *}
{ezcss_require( 'explayouts_admin4.css' )}
{ezscript_require( 'explayouts_admin4.js' )}
<div class="context-block a4-page explayouts-a4">

<div class="box-header"><div class="box-ml">
<h1 class="context-title">{$layout.name|wash}</h1>
</div></div>

<div class="box-bc"><div class="box-ml"><div class="box-content">

{if $message}<div class="a4-notice is-ok" role="status">{$message|wash}</div>{/if}
{if $error}<div class="a4-notice is-bad" role="alert">{$error|wash}</div>{/if}

<form class="a4-form is-wide" method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl}>
    <div class="a4-field">
        <label class="a4-label" for="layout-identifier">{'Identifier'|i18n( 'design/admin/explayouts/layout_edit' )}</label>
        <input class="a4-input" id="layout-identifier" type="text" name="Identifier" value="{$layout.identifier|wash}" />
    </div>
    <div class="a4-field">
        <label class="a4-label" for="layout-name">{'Name'|i18n( 'design/admin/explayouts/layout_edit' )}</label>
        <input class="a4-input" id="layout-name" type="text" name="Name" value="{$layout.name|wash}" />
    </div>
    <div class="a4-field">
        <label class="a4-label" for="layout-type">{'Layout type'|i18n( 'design/admin/explayouts/layout_edit' )}</label>
        <select class="a4-input" id="layout-type" name="LayoutType" aria-describedby="layout-type-help">
            <option value="">{'-- Select --'|i18n( 'design/admin/explayouts/layout_edit' )}</option>
            {foreach $available_types as $typeInfo}
            <option value="{$typeInfo.identifier|wash}"{if eq( $layout.layout_type, $typeInfo.identifier )} selected="selected"{/if}>{$typeInfo.name|wash}</option>
            {/foreach}
        </select>
        <span class="a4-help" id="layout-type-help">{'Selecting a type auto-creates zones on save.'|i18n( 'design/admin/explayouts/layout_edit' )}</span>
    </div>
    <div class="a4-actions">
        <button type="submit" name="Publish" class="a4-btn a4-btn-primary">{'Publish'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
        <button type="submit" name="SaveDraft" class="a4-btn">{'Save draft'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
        <a class="a4-btn a4-btn-ghost" href={'explayouts/layout_list'|ezurl}>{'Cancel'|i18n( 'design/admin/explayouts/layout_edit' )}</a>
    </div>
</form>

<section class="a4-section" aria-labelledby="layout-zones-title">
<div class="a4-section-head"><h2 class="a4-h2" id="layout-zones-title">{'Zones'|i18n( 'design/admin/explayouts/layout_edit' )}</h2></div>
{if count( $zones )}
<ul class="a4-cards">
{foreach $zones as $zoneEntry}
    {def $zone = $zoneEntry.zone
         $blocks = $zoneEntry.blocks}
    <li class="a4-card">
        <div class="a4-card-head">
            <div class="a4-card-title"><h3 class="a4-h3"><code class="a4-code">{$zone.identifier|wash}</code></h3></div>
            <form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Delete this zone and all its blocks?'|i18n( 'design/admin/explayouts/layout_edit' )|wash}">
                <input type="hidden" name="DeleteZoneID" value="{$zone.id|wash}" />
                <button type="submit" name="DeleteZone" class="a4-btn a4-btn-small a4-btn-outline-danger">{'Delete zone'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
            </form>
        </div>
        {if count( $blocks )}
        <div class="a4-table-wrap"><table class="a4-table is-compact">
            <tbody>
            {foreach $blocks as $block}
            <tr>
                <td>{$block.name|wash}</td>
                <td><span class="a4-tag">{$block.definition_identifier|wash}</span></td>
                <td class="a4-end">
                    <div class="a4-actions">
                        <a class="a4-btn a4-btn-small" href={concat( 'explayouts/block_edit/', $block.id )|ezurl}>{'Edit'|i18n( 'design/admin/explayouts/layout_edit' )}</a>
                        <form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="explayouts-a4-inline-form">
                            <input type="hidden" name="MoveBlockID" value="{$block.id|wash}" />
                            <button type="submit" name="MoveBlockUp" class="a4-btn a4-btn-small a4-btn-icon" title="{'Move up'|i18n( 'design/admin/explayouts/layout_edit' )}"><span aria-hidden="true">&uarr;</span><span class="a4-sr-only">{'Move up'|i18n( 'design/admin/explayouts/layout_edit' )}</span></button>
                            <button type="submit" name="MoveBlockDown" class="a4-btn a4-btn-small a4-btn-icon" title="{'Move down'|i18n( 'design/admin/explayouts/layout_edit' )}"><span aria-hidden="true">&darr;</span><span class="a4-sr-only">{'Move down'|i18n( 'design/admin/explayouts/layout_edit' )}</span></button>
                        </form>
                        {if gt( count( $zones ), 1 )}
                        <form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="explayouts-a4-inline-form">
                            <input type="hidden" name="MoveBlockID" value="{$block.id|wash}" />
                            <select name="TargetZoneID" class="a4-input is-auto" aria-label="{'Move to zone'|i18n( 'design/admin/explayouts/layout_edit' )}">
                            {foreach $zones as $z}{if ne( $z.zone.id, $zone.id )}<option value="{$z.zone.id|wash}">{$z.zone.identifier|wash}</option>{/if}{/foreach}
                            </select>
                            <button type="submit" name="MoveBlockToZone" class="a4-btn a4-btn-small">{'Move'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
                        </form>
                        {/if}
                        <form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="explayouts-a4-inline-form" data-explayouts-confirm="{'Delete this block?'|i18n( 'design/admin/explayouts/layout_edit' )|wash}">
                            <input type="hidden" name="DeleteBlockID" value="{$block.id|wash}" />
                            <button type="submit" name="DeleteBlock" class="a4-btn a4-btn-small a4-btn-outline-danger">{'Delete'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
                        </form>
                    </div>
                </td>
            </tr>
            {/foreach}
            </tbody>
        </table></div>
        {else}
        <p class="a4-muted">{'No blocks yet.'|i18n( 'design/admin/explayouts/layout_edit' )}</p>
        {/if}
        {if count( $available_blocks )}
        <form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="a4-row explayouts-a4-add">
            <input type="hidden" name="ZoneID" value="{$zone.id|wash}" />
            <select name="DefinitionIdentifier" class="a4-input is-auto" aria-label="{'Block type'|i18n( 'design/admin/explayouts/layout_edit' )}">
            {foreach $available_blocks as $blockInfo}<option value="{$blockInfo.identifier|wash}">{$blockInfo.name|wash}</option>{/foreach}
            </select>
            <button type="submit" name="AddBlock" class="a4-btn a4-btn-small">{'Add block'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
        </form>
        {/if}
    </li>
    {undef $zone $blocks}
{/foreach}
</ul>
{else}
<p class="a4-empty">{'No zones yet. Choose a layout type and save to auto-create zones.'|i18n( 'design/admin/explayouts/layout_edit' )}</p>
{/if}
<form method="post" action={concat( 'explayouts/layout_edit/', $layout.id )|ezurl} class="a4-row explayouts-a4-add">
    <input class="a4-input is-auto" type="text" name="ZoneIdentifier" value="" placeholder="{'zone identifier'|i18n( 'design/admin/explayouts/layout_edit' )}" aria-label="{'zone identifier'|i18n( 'design/admin/explayouts/layout_edit' )}" />
    <button type="submit" name="AddZone" class="a4-btn">{'Add custom zone'|i18n( 'design/admin/explayouts/layout_edit' )}</button>
</form>
</section>

</div></div></div>
</div>
