{* The column widths of the view type, as in the reference: two_columns_33_66 4/8, two_columns_66_33 8/4, otherwise
   (two_columns_50_50, default) 6/6. Without {def}: a container may hold another one, and the two would share it. *}
<div class="ngl-block ngl-two-columns whitespace-top-medium whitespace-bottom-medium" data-block-id="{$block.id|wash}">
    <div class="container">
        <div class="row">
            <div class="{cond( $block.view_type|eq( 'two_columns_33_66' ), 'col-md-4', $block.view_type|eq( 'two_columns_66_33' ), 'col-md-8', 'col-md-6' )}">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('left'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
            <div class="{cond( $block.view_type|eq( 'two_columns_33_66' ), 'col-md-8', $block.view_type|eq( 'two_columns_66_33' ), 'col-md-4', 'col-md-6' )}">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('right'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
</div>
