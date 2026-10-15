{* The top-level blocks of a zone, for the layout type templates (explayouts/layouts/<type>.tpl). Child blocks of a
   container are drawn by their container. $module_result is passed on, so a "Full view" block shows the page's
   own content. *}
{foreach $zone.blocks as $block}
    {if and( is_set( $block.parent_id ), $block.parent_id|eq( 0 ), is_set( $block.definition_identifier ), $block.definition_identifier|ne( '' ) )}
        {include uri=concat( 'design:explayouts/block/', $block.definition_identifier, '.tpl' ) block=$block zone=$zone module_result=first_set( $module_result, hash() )}
    {/if}
{/foreach}
