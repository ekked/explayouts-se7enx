<div class="ngl-block ngl-three-columns whitespace-top-medium whitespace-bottom-medium" data-block-id="{$block.id|wash}">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_1'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
            <div class="col-md-4">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_2'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
            <div class="col-md-4">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_3'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
</div>
