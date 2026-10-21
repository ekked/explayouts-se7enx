<section class="slb slb-lead{if $block.values.css_class|explayouts_safe_css_class} {$block.values.css_class|explayouts_safe_css_class|wash}{/if}" {if $block.values.css_id}id="{$block.values.css_id|wash}"{/if} data-block-id="{$block.id|wash}">
    {* the heading of the block's level (Heading level), else h2 *}
    {if $block.values.title}<h{first_set( $block.values.level, 0 )|int|gt( 0 )|choose( 2, $block.values.level )}>{$block.values.title|wash}</h{first_set( $block.values.level, 0 )|int|gt( 0 )|choose( 2, $block.values.level )}>{/if}
    {if $block.values.content}<p class="lead">{$block.values.content|wash|nl2br}</p>{/if}
    {if and( $block.values.link_text, $block.values.link_url|explayouts_safe_url )}<a href={$block.values.link_url|explayouts_safe_url|ezurl} class="btn btn-primary">{$block.values.link_text|wash}</a>{/if}
</section>
