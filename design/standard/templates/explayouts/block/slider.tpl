{* A gallery block renders with the template of its view type (block.values.view_type,
   expLayoutsGalleryBlockHandler::galleryViewType(), always one of the five gallery view
   types): a block switched to another gallery view type in the editor renders that view
   type's template. The number of thumbnails per row/slide is the class explayouts-cols-<n>. *}
{if and( is_set( $block.values.view_type ), $block.values.view_type|ne( 'slider' ) )}
    {include uri=concat( 'design:explayouts/block/', $block.values.view_type, '.tpl' ) block=$block zone=first_set( $zone, false() ) module_result=first_set( $module_result, hash() )}
{else}
{def $columns = first_set( $block.values.number_of_thumbnails, 6 )
     $lightbox = first_set( $block.values.enable_lightbox, false() )}
<div class="slb explayouts-cols-{$columns|wash} slb-slider" data-block-id="{$block.id|wash}" data-columns="{$columns|wash}"{if $lightbox} data-lightbox="1"{include uri='design:explayouts/block/parts/lightbox_labels.tpl'}{/if}>
    {if count($block.values.images)}
        <div class="slider">
        {foreach $block.values.images as $image}
            <div class="slide">{include uri='design:explayouts/block/parts/gallery_item.tpl' image=$image block=$block lightbox=$lightbox}</div>
        {/foreach}
        </div>
    {else}
        <p class="empty">{'No images found.'|i18n( 'design/standard/explayouts/block/slider' )}</p>
    {/if}
</div>
{if and( $lightbox, count( $block.values.images ) )}<script src={'javascript/explayouts_lightbox.js'|ezdesign} defer></script>{/if}
{undef $columns $lightbox}
{/if}
