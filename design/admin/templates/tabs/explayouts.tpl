{ezcss_load(array('netgen/layouts-admin.css','netgen/layouts-exponential.css','explayouts-ui.css'))}
{* Roboto and Material Icons come with layouts-admin.css (@font-face on the files of the design), so the tab loads no
   stylesheet or font from another host and the administration's Content Security Policy needs none. *}
{def $resolved = fetch('explayouts','layout_summary_for_node',hash('node_id',$node.node_id))}
{def $rules = fetch('explayouts','rules_for_node',hash('node_id',$node.node_id))}
<div class="ng-layouts-app row exp-node-layout-tab">
    <div class="layouts-content">
        <div class="layouts-header">
            <div>
                <p class="expl-node-line">{'Node: %name (ID: %node_id) · URL alias: %url_alias'|i18n( 'design/admin/tabs/explayouts',, hash( '%name', $node.name|wash, '%node_id', $node.node_id, '%url_alias', $node.url_alias|wash ) )}</p>
            </div>
        </div>

        {if $resolved}
            <div class="nl-node-resolved">
                <div class="nl-node-resolved-header">
                    <div>
                        <h3 class="nl-node-resolved-title">{'Resolved layout'|i18n( 'design/admin/tabs/explayouts' )}</h3>
                        <p class="nl-node-resolved-meta">{'%name (%identifier) · Type: %layout_type'|i18n( 'design/admin/tabs/explayouts',, hash( '%name', $resolved.name|wash, '%identifier', $resolved.identifier|wash, '%layout_type', $resolved.layout_type|wash ) )}</p>
                    </div>
                    <div class="nl-node-rule-actions">
                        <a href={concat('explayouts_ui/layout_preview/',$resolved.id,'/2')|ezurl} target="_blank" class="nl-btn"><i class="material-icons">visibility</i> {'Preview'|i18n( 'design/admin/tabs/explayouts' )}</a>
                        <a href={concat('explayouts_ui_api/app#layout/',$resolved.id)|ezurl} class="nl-btn nl-btn-primary" data-explayouts-return-to="{concat('content/view/full/', $node.node_id)|ezurl('no')|wash}"><i class="material-icons">edit</i> {'Edit layout'|i18n( 'design/admin/tabs/explayouts' )}</a>
                    </div>
                </div>
                {def $zone_count = count($resolved.zones)}
                {def $block_count = $resolved.block_count}
                <div class="nl-node-resolved-stats">
                    <div class="nl-node-resolved-stat"><i class="material-icons">view_quilt</i> {if $zone_count|ne(1)}{'%count zones'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $zone_count ) )}{else}{'%count zone'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $zone_count ) )}{/if}</div>
                    <div class="nl-node-resolved-stat"><i class="material-icons">widgets</i> {if $block_count|ne(1)}{'%count blocks'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $block_count ) )}{else}{'%count block'|i18n( 'design/admin/tabs/explayouts',, hash( '%count', $block_count ) )}{/if}</div>
                    <div class="nl-node-resolved-stat"><i class="material-icons">assignment</i> {'Layout ID: %id'|i18n( 'design/admin/tabs/explayouts',, hash( '%id', $resolved.id ) )}</div>
                </div>
                {undef $zone_count $block_count}
            </div>
        {else}
            <div class="nl-empty-state expl-empty-spaced">
                <i class="material-icons">layers</i>
                <p>{'No layout resolves for this node.'|i18n( 'design/admin/tabs/explayouts' )}</p>
            </div>
        {/if}

        <h3 class="nl-node-rules-title">{'List of layout mappings directly applied to this location'|i18n( 'design/admin/tabs/explayouts' )}</h3>
        {if count($rules)}
            {foreach $rules as $rule}
                <div class="nl-node-rule">
                    <div class="nl-node-rule-info">
                        <p class="nl-node-rule-name"><a href={concat('explayouts_ui_api/app#layout/',$rule.layout_id)|ezurl}>{$rule.layout_name|wash}</a> <small>({$rule.layout_identifier|wash})</small></p>
                        <p class="nl-node-rule-meta">{'Priority %priority · Target: node / %node_id'|i18n( 'design/admin/tabs/explayouts',, hash( '%priority', $rule.priority|wash, '%node_id', $node.node_id ) )}</p>
                    </div>
                    <div class="nl-node-rule-actions">
                        {if $rule.enabled}<span class="nl-status enabled">{'Enabled'|i18n( 'design/admin/tabs/explayouts' )}</span>{else}<span class="nl-status disabled">{'Disabled'|i18n( 'design/admin/tabs/explayouts' )}</span>{/if}
                        <a href={concat('explayouts_ui/rule_list?RuleID=',$rule.id)|ezurl} class="nl-btn" title="{'Edit mapping'|i18n( 'design/admin/tabs/explayouts' )}"><i class="material-icons">edit</i></a>
                        <a href={concat('explayouts_ui/layout_preview/',$rule.layout_id,'/2')|ezurl} target="_blank" class="nl-btn" title="{'Preview layout'|i18n( 'design/admin/tabs/explayouts' )}"><i class="material-icons">visibility</i></a>
                    </div>
                </div>
            {/foreach}
        {else}
            <p class="nl-empty-text">{'No mappings'|i18n( 'design/admin/tabs/explayouts' )}</p>
        {/if}

        <h3 class="nl-node-rules-title">{'List of layouts using this location/content as block item'|i18n( 'design/admin/tabs/explayouts' )}</h3>
        <p class="nl-empty-text">{'No related layouts'|i18n( 'design/admin/tabs/explayouts' )}</p>

        <h3 class="nl-node-rules-title">{'List of layouts using this content as a component'|i18n( 'design/admin/tabs/explayouts' )}</h3>
        <p class="nl-empty-text">{'No layouts'|i18n( 'design/admin/tabs/explayouts' )}</p>
        <div class="layouts-controls">
            <a href={concat('explayouts_ui/rule_list?TargetType=node&TargetValue=',$node.node_id)|ezurl} class="nl-btn nl-btn-primary">
                <i class="material-icons">add</i> {'Map layout'|i18n( 'design/admin/tabs/explayouts' )}
            </a>
            <a href={concat('explayouts_ui_api/app#layout')|ezurl} class="nl-btn">
                <i class="material-icons">add_box</i> {'New layout'|i18n( 'design/admin/tabs/explayouts' )}
            </a>
        </div>
    </div>
</div>
{ezscript( array( 'explayouts_admin.js' ) )}
