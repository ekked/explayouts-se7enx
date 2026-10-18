{* External video: a service and a video id, as in the reference, or the address of a player (video_url, checked by
   the handler). The player loads after a click (parts/embed.tpl). *}
<div class="slb slb-video" data-block-id="{$block.id|wash}">
    {if $block.values.video_url|explayouts_safe_embed_url}
        <figure class="video{if $block.values.service} video-{$block.values.service|wash}{/if}">
            {include uri='design:explayouts/block/parts/embed.tpl'
                     src=$block.values.video_url|explayouts_safe_embed_url
                     host=first_set( $block.values.embed_host, '' )
                     kind='video'
                     title=cond( $block.values.caption|ne( '' ), $block.values.caption, 'Video'|i18n( 'design/standard/explayouts/block/video' ) )
                     width=$block.values.width
                     height=$block.values.height
                     allow=cond( $block.values.autoplay, 'autoplay; fullscreen', 'fullscreen' )
                     load_label='Load video'|i18n( 'design/standard/explayouts/block/video' )}
            {if $block.values.caption}<figcaption>{$block.values.caption|wash}</figcaption>{/if}
        </figure>
    {else}
        <p class="empty">{'No video configured.'|i18n( 'design/standard/explayouts/block/video' )}</p>
    {/if}
</div>
