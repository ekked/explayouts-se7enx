<div class="slb slb-button" data-block-id="{$block.id|wash}">
    {if and( $block.values.text|ne(''), $block.values.link|explayouts_safe_url|ne('') )}
        <a href={$block.values.link|explayouts_safe_url|ezurl} target="{$block.values.target|wash}" class="{$block.values.class|wash}">{$block.values.text|wash}</a>
    {elseif $block.values.text|ne('')}
        <span class="{$block.values.class|wash}">{$block.values.text|wash}</span>
    {/if}
</div>