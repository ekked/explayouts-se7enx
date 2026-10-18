{* One zone of a layout type template of the reference (layout_1 ... layout_6) as <section class="zone zone-<id>">,
   found by its identifier. Parameters: layout, identifier, module_result. No {def}: the layout templates include it
   several times. *}
{foreach $layout.zones as $zone}
    {if eq( $zone.identifier, $identifier )}
        <section class="zone zone-{$zone.identifier|wash}" data-zone-id="{$zone.id|wash}" data-zone-identifier="{$zone.identifier|wash}">
            {include uri='design:explayouts/zone_blocks.tpl' zone=$zone module_result=first_set( $module_result, hash() )}
        </section>
    {/if}
{/foreach}
