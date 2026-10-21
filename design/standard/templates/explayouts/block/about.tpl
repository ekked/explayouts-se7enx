<section class="slb slb-about{if $block.values.css_class|explayouts_safe_css_class} {$block.values.css_class|explayouts_safe_css_class|wash}{/if}" {if $block.values.css_id}id="{$block.values.css_id|wash}"{/if} data-block-id="{$block.id|wash}">
    {if $block.values.title}<h2>{$block.values.title|wash}</h2>{/if}
    <div class="row">
        {if $block.values.image_url}
        <div class="col-md-6">
            <img src="{$block.values.image_url|wash}" alt="{$block.values.image_alt|wash}" class="img-fluid" />
        </div>
        {/if}
        <div class="col{if $block.values.image_url}-md-6{/if}">
            {if $block.values.content}<div class="block-content">{$block.values.content|wash|nl2br}</div>{/if}
            {if and( $block.values.link_text, $block.values.link_url|explayouts_safe_url )}<a href={$block.values.link_url|explayouts_safe_url|ezurl} class="btn btn-secondary">{$block.values.link_text|wash}</a>{/if}
        </div>
    </div>
</section>
