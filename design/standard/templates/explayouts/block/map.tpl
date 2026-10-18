{* Map: a point with zoom, map type and marker, as in the reference (the handler builds the address: Google's Maps
   Embed API with explayouts.ini [MapSettings] GoogleMapsApiKey, else OpenStreetMap), or the address of a map to embed
   (embed_url). The map loads after a click (parts/embed.tpl). *}
<div class="slb slb-map" data-block-id="{$block.id|wash}">
    {if $block.values.embed_url|explayouts_safe_embed_url}
        {include uri='design:explayouts/block/parts/embed.tpl'
                 src=$block.values.embed_url|explayouts_safe_embed_url
                 kind='map'
                 title='Map'|i18n( 'design/standard/explayouts/block/map' )
                 width=$block.values.width
                 height=$block.values.height
                 allow=''
                 load_label='Load map'|i18n( 'design/standard/explayouts/block/map' )}
    {else}
        <p class="empty">{'No map configured.'|i18n( 'design/standard/explayouts/block/map' )}</p>
    {/if}
</div>
