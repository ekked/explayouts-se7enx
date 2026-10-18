<div class="slb slb-badge" data-block-id="{$block.id|wash}">
    {def $badge_link = $block.values.link|explayouts_safe_url}
    {if $badge_link}<a href="{$badge_link|wash}" class="badge badge-{$block.values.style|wash}">{else}<span class="badge badge-{$block.values.style|wash}">{/if}
        {$block.values.label|wash}
    {if $badge_link}</a>{else}</span>{/if}
    {undef $badge_link}
</div>
