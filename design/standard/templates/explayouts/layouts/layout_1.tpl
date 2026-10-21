{* Layout type layout_1 of the reference: one zone. *}
<div class="slb zone-layout-layout1" data-layout-id="{$layout.id|wash}" data-layout-identifier="{$layout.identifier|wash}">
    {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='main' module_result=first_set( $module_result, hash() )}
</div>
