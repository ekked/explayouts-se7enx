<div class="slb slb-4-column" data-layout-id="{$layout.id|wash}">
    {foreach $layout.zones as $zone}
        {if or(eq($zone.identifier,'col1'),eq($zone.identifier,'col2'),eq($zone.identifier,'col3'),eq($zone.identifier,'col4'))}
            <div class="sevenx-zone sevenx-zone-{$zone.identifier|wash}" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </div>
        {/if}
    {/foreach}
</div>
