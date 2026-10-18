{* One image of a gallery block (gallery.tpl, slider.tpl, thumb_gallery.tpl, grid_gallery.tpl, sushi_bar.tpl).
   With the lightbox on, the image opens its original size (full_url) in the lightbox of
   javascript/explayouts_lightbox.js, whose caption links the node (data-node-url); without
   JavaScript the link shows the original image. Otherwise the image links its node. *}
{if $lightbox}<a href="{$image.full_url|wash}" class="explayouts-lightbox" data-gallery="explayouts-gallery-{$block.id|wash}"{if $image.link} data-node-url="{$image.link|ezurl( 'no' )|wash}"{/if} title="{$image.alt|wash}">{elseif $image.link}<a href="{$image.link|ezurl( 'no' )|wash}">{/if}
<img src="{$image.url|wash}" alt="{$image.alt|wash}" loading="lazy" />
{if or( $lightbox, $image.link )}</a>{/if}
