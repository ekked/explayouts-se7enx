<div class="slb slb-tpl-block" data-block-id="{$block.id|wash}" data-tpl-block-name="{if is_set($block.values.block_name)}{$block.values.block_name|wash}{/if}">
    {* a template name only, no path: letters, digits and _ (the handler checks the same) *}
    {if and( is_set($block.values.block_name), $block.values.block_name|explayouts_safe_template_name|ne( '' ) )}
        {include uri=concat('design:explayouts/tpl_block/', $block.values.block_name|explayouts_safe_template_name, '.tpl')}
    {/if}
</div>
