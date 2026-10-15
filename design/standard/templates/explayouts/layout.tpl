{* A layout type with its own template (explayouts.ini [LayoutType_<type>] Template=) draws its zones in their
   places, a grid of columns; every other type gets its zones one under the other. *}
{if and( is_set( $layout.layout_type ), ezini_hasvariable( concat( 'LayoutType_', $layout.layout_type ), 'Template', 'explayouts.ini' ) )}
    {include uri=concat( 'design:', ezini( concat( 'LayoutType_', $layout.layout_type ), 'Template', 'explayouts.ini' ) ) layout=$layout module_result=first_set( $module_result, hash() )}
{else}
<div class="slb" data-layout-id="{$layout.id|wash}" data-layout-identifier="{$layout.identifier|wash}">
{foreach $layout.zones as $zone}
    {include uri='design:explayouts/zone.tpl' zone=$zone module_result=first_set( $module_result, hash() )}
{/foreach}
</div>
{/if}
