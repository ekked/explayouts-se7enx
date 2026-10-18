<div class="slb-zone" data-zone-id="{$zone.id|wash}" data-zone-identifier="{$zone.identifier|wash}">
{foreach $zone.blocks as $block}
    {if and( is_set( $block.parent_id ), $block.parent_id|eq(0), is_set( $block.definition_identifier ), $block.definition_identifier|ne('') )}
        {if $block.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $block.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$block zone=$zone module_result=$module_result}{/if}
    {/if}
{/foreach}
</div>
