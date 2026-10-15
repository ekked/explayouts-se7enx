<div class="slb slb-3-column" data-layout-id="{$layout.id|wash}">
    {foreach $layout.zones as $zone}
        {if or(eq($zone.identifier,'left'),eq($zone.identifier,'main'),eq($zone.identifier,'right'))}
            <div class="sevenx-zone sevenx-zone-{$zone.identifier|wash}" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </div>
        {/if}
    {/foreach}
</div>
