<div class="slb slb-title" data-block-id="{$block.id|wash}">
    {if $block.values.title}
        {* only h1-h6, p or div; the handler checks the same (expLayoutsSafeValue::headingTag()) *}
        {def $tag = $block.values.tag|explayouts_safe_tag( $block.values.level )
             $title_link = $block.values.link|explayouts_safe_url}
        <{$tag} class="title">
            {if and($block.values.use_link|eq(1), $title_link|ne(''))}
                <a href={$title_link|ezurl}>{$block.values.title|wash}</a>
            {else}
                {$block.values.title|wash}
            {/if}
        </{$tag}>
        {undef $tag $title_link}
    {/if}
</div>