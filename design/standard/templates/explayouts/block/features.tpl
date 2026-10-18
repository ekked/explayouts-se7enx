<section class="slb slb-features{if $block.values.css_class|explayouts_safe_css_class} {$block.values.css_class|explayouts_safe_css_class|wash}{/if}" {if $block.values.css_id}id="{$block.values.css_id|wash}"{/if} data-block-id="{$block.id|wash}">
    {* the heading of the block's level (Heading level), else h2; the features one level below it *}
    {if $block.values.title}<h{first_set( $block.values.level, 0 )|int|gt( 0 )|choose( 2, $block.values.level )}>{$block.values.title|wash}</h{first_set( $block.values.level, 0 )|int|gt( 0 )|choose( 2, $block.values.level )}>{/if}
    {if $block.values.content}<div class="block-content">{$block.values.content|wash|nl2br}</div>{/if}
    {if count( first_set( $block.values.items, array() ) )}
    <ul class="features-list row">
    {foreach $block.values.items as $feature}
        <li class="col-sm-6 col-md-4">
            {if $feature.title}<strong class="feature-title">{$feature.title|wash}</strong>{/if}
            {if $feature.text}<p>{$feature.text|wash}</p>{/if}
        </li>
    {/foreach}
    </ul>
    {/if}
    {if and( $block.values.link_text, $block.values.link_url|explayouts_safe_url )}<a href={$block.values.link_url|explayouts_safe_url|ezurl} class="btn btn-secondary">{$block.values.link_text|wash}</a>{/if}
</section>
