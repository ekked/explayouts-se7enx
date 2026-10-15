<div class="slb slb-sidebar-left" data-layout-id="{$layout.id|wash}">
    {foreach $layout.zones as $zone}
        {if eq($zone.identifier,'sidebar')}
            <aside class="sevenx-zone sevenx-zone-sidebar" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </aside>
        {/if}
        {if eq($zone.identifier,'main')}
            <main class="sevenx-zone sevenx-zone-main" data-zone-id="{$zone.id|wash}">
                {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=$module_result}
            </main>
        {/if}
    {/foreach}
</div>
