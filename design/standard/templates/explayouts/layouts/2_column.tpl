<div class="slb slb-2-column" data-layout-id="{$layout.id|wash}">
    {foreach $layout.zones as $zone}
        {if eq($zone.identifier,'left')}
            <div class="sevenx-zone sevenx-zone-left" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </div>
        {/if}
        {if eq($zone.identifier,'right')}
            <div class="sevenx-zone sevenx-zone-right" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </div>
        {/if}
    {/foreach}
</div>
