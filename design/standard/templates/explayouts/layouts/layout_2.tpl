{* Layout type layout_2 of the reference: header, post header, main, pre footer and footer one under the other. *}
<div class="slb zone-layout-layout2" data-layout-id="{$layout.id|wash}" data-layout-identifier="{$layout.identifier|wash}">
    <div class="top-content-block">
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='header' module_result=first_set( $module_result, hash() )}
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='post_header' module_result=first_set( $module_result, hash() )}
    </div>
    <div class="main-content-block">
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='main' module_result=first_set( $module_result, hash() )}
    </div>
    <div class="bottom-content-block">
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='pre_footer' module_result=first_set( $module_result, hash() )}
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='footer' module_result=first_set( $module_result, hash() )}
    </div>
</div>
