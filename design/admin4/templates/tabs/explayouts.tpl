{* admin4: the Layouts tab of the node view (admininterface.ini [AdditionalTab_layouts]) with admin4's components
   only: no stylesheet of its own, no web fonts from outside. Same fetches and links as design/admin's tab. *}
{def $resolved = fetch( 'explayouts', 'layout_summary_for_node', hash( 'node_id', $node.node_id ) )
     $rules = fetch( 'explayouts', 'rules_for_node', hash( 'node_id', $node.node_id ) )}
<div class="a4-page a4-stack">

<p class="a4-meta">{'Node: %name (ID: %node_id) · URL alias: %url_alias'|i18n( 'design/admin/tabs/explayouts',, hash( '%name', $node.name|wash, '%node_id', $node.node_id, '%url_alias', $node.url_alias|wash ) )}</p>

{if $resolved}
<div class="a4-card">
    <div class="a4-card-head">
        <div class="a4-card-title">
            <h3 class="a4-h3">{'Resolved layout'|i18n( 'design/admin/tabs/explayouts' )}</h3>
            <span class="a4-tag">{$resolved.layout_type|wash}</span>
        </div>
        <div class="a4-actions">
            <a class="a4-btn a4-btn-small" href={concat( 'explayouts_ui/layout_preview/', $resolved.id, '/2' )|ezurl} target="_blank">{'Preview'|i18n( 'design/admin/tabs/explayouts' )}</a>
            <a class="a4-btn a4-btn-small a4-btn-primary" href={concat( 'explayouts_ui_api/app#layout/', $resolved.id )|ezurl} onclick="sessionStorage.setItem('explayouts_return_to','{concat( 'content/view/full/', $node.node_id )|ezurl( 'no' )|wash( 'javascript' )}'); return true;">{'Edit layout'|i18n( 'design/admin/tabs/explayouts' )}</a>
        </div>
    </div>
    <p class="a4-meta">{'%name (%identifier) · Type: %layout_type'|i18n( 'design/admin/tabs/explayouts',, hash( '%name', $resolved.name|wash, '%identifier', $resolved.identifier|wash, '%layout_type', $resolved.layout_type|wash ) )}</p>
    {def $zone_count = count( $resolved.zones )
         $block_count = $resolved.block_count}
    <ul class="a4-badges">
        <li class="a4-badge">{if $zone_count|ne( 1 )}{'%count zones'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $zone_count ) )}{else}{'%count zone'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $zone_count ) )}{/if}</li>
        <li class="a4-badge">{if $block_count|ne( 1 )}{'%count blocks'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $block_count ) )}{else}{'%count block'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $block_count ) )}{/if}</li>
        <li class="a4-badge is-mono">{'Layout ID: %id'|i18n( 'design/admin/tabs/explayouts',, hash( '%id', $resolved.id ) )}</li>
    </ul>
    {undef $zone_count $block_count}
</div>
{else}
<p class="a4-empty">{'No layout resolves for this node.'|i18n( 'design/admin/tabs/explayouts' )}</p>
{/if}

<section class="a4-section">
<h3 class="a4-h3">{'List of layout mappings directly applied to this location'|i18n( 'design/admin/tabs/explayouts' )}</h3>
{if count( $rules )}
<div class="a4-table-wrap">
<table class="a4-table is-compact">
    <tbody>
    {foreach $rules as $rule}
        <tr>
            <td><a href={concat( 'explayouts_ui_api/app#layout/', $rule.layout_id )|ezurl}>{$rule.layout_name|wash}</a> <code class="a4-code">{$rule.layout_identifier|wash}</code></td>
            <td class="a4-meta">{'Priority %priority · Target: node / %node_id'|i18n( 'design/admin/tabs/explayouts',, hash( '%priority', $rule.priority|wash, '%node_id', $node.node_id ) )}</td>
            <td>{if $rule.enabled}<span class="a4-badge is-ok">{'Enabled'|i18n( 'design/admin/tabs/explayouts' )}</span>{else}<span class="a4-badge is-warn">{'Disabled'|i18n( 'design/admin/tabs/explayouts' )}</span>{/if}</td>
            <td class="a4-end"><a class="a4-btn a4-btn-small" href={concat( 'explayouts_ui/rule_list?RuleID=', $rule.id )|ezurl}>{'Edit mapping'|i18n( 'design/admin/tabs/explayouts' )}</a></td>
        </tr>
    {/foreach}
    </tbody>
</table>
</div>
{else}
<p class="a4-muted">{'No mappings'|i18n( 'design/admin/tabs/explayouts' )}</p>
{/if}
</section>

<div class="a4-actions">
    <a class="a4-btn a4-btn-primary" href={concat( 'explayouts_ui/rule_list?TargetType=node&TargetValue=', $node.node_id )|ezurl}>{'Map layout'|i18n( 'design/admin/tabs/explayouts' )}</a>
    <a class="a4-btn" href={'explayouts_ui_api/app#layout'|ezurl}>{'New layout'|i18n( 'design/admin/tabs/explayouts' )}</a>
</div>

</div>
{undef $resolved $rules}
