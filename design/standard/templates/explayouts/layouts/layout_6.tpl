{* Layout type layout_6 of the reference: header and post header; then in 8 of 12 columns top above left (8) and
   center (4), beside it right in 4 columns; then pre footer and footer. *}
<div class="slb zone-layout-layout6" data-layout-id="{$layout.id|wash}" data-layout-identifier="{$layout.identifier|wash}">
    <div class="top-content-block">
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='header' module_result=first_set( $module_result, hash() )}
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='post_header' module_result=first_set( $module_result, hash() )}
    </div>
    <div class="main-content-block">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-lg-12">
                            {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='top' module_result=first_set( $module_result, hash() )}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8">
                            {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='left' module_result=first_set( $module_result, hash() )}
                        </div>
                        <div class="col-lg-4">
                            {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='center' module_result=first_set( $module_result, hash() )}
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='right' module_result=first_set( $module_result, hash() )}
                </div>
            </div>
        </div>
    </div>
    <div class="bottom-content-block">
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='pre_footer' module_result=first_set( $module_result, hash() )}
        {include uri='design:explayouts/layouts/zone_section.tpl' layout=$layout identifier='footer' module_result=first_set( $module_result, hash() )}
    </div>
</div>
