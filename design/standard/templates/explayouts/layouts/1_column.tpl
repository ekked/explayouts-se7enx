<div class="slb slb-1-column" data-layout-id="{$layout.id|wash}">
    {foreach $layout.zones as $zone}
        {if eq($zone.identifier,'main')}
            <div class="sevenx-zone sevenx-zone-main" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </div>
        {/if}
    {/foreach}
</div>
