<div class="slb slb-image" data-block-id="{$block.id|wash}">
    {if $block.values.image_url}
        {def $image_link = $block.values.link|explayouts_safe_url}
        {if $image_link}<a href="{$image_link|wash}" target="_blank" rel="noopener">{/if}
        <img src="{$block.values.image_url|wash}" alt="{$block.values.alt|wash}" />
        {if $image_link}</a>{/if}
        {undef $image_link}
    {/if}
</div>
