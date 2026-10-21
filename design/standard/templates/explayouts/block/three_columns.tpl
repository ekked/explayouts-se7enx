{* The column widths of the view type, as in the reference: three_columns_50_25_25 6/3/3, three_columns_25_50_25 3/6/3,
   three_columns_25_25_50 3/3/6, otherwise (three_columns_33_33_33, and the former three_columns) 4/4/4. Without {def}: a container may hold another one, and the two would share it. *}
<div class="ngl-block ngl-three-columns whitespace-top-medium whitespace-bottom-medium" data-block-id="{$block.id|wash}">
    <div class="container">
        <div class="row">
            <div class="{cond( $block.view_type|eq( 'three_columns_50_25_25' ), 'col-md-6', array( 'three_columns_25_50_25', 'three_columns_25_25_50' )|contains( $block.view_type ), 'col-md-3', 'col-md-4' )}">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_1'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
            <div class="{cond( $block.view_type|eq( 'three_columns_25_50_25' ), 'col-md-6', array( 'three_columns_50_25_25', 'three_columns_25_25_50' )|contains( $block.view_type ), 'col-md-3', 'col-md-4' )}">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_2'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
            <div class="{cond( $block.view_type|eq( 'three_columns_25_25_50' ), 'col-md-6', array( 'three_columns_50_25_25', 'three_columns_25_50_25' )|contains( $block.view_type ), 'col-md-3', 'col-md-4' )}">
                {foreach $zone.blocks as $child}
                    {if and( is_set( $child.parent_id ), $child.parent_id|eq($block.id), is_set( $child.placeholder ), $child.placeholder|eq('col_3'), is_set( $child.definition_identifier ), $child.definition_identifier|ne('') )}
                        {if $child.definition_identifier|explayouts_safe_template_name}{include uri=concat( 'design:explayouts/block/', $child.definition_identifier|explayouts_safe_template_name, '.tpl' ) block=$child zone=$zone module_result=$module_result}{/if}
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
</div>
