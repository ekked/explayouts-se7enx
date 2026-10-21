{* An embedded player or map (the video and map blocks) that loads after a click: until then the page sends nothing
   to the other host. A placeholder with a button carries the checked address (src, from
   expLayoutsSafeValue::embedUrl() or a handler that built it) and the frame's attributes as data-*;
   javascript/explayouts_embed.js puts the <iframe> in its place on the click, sandboxed (scripts and its own origin
   for the player, popups for its links, presentation for full screen; no navigation of the page) and with the
   origin as the only referrer. No inline handler, no inline style: the size is the class of the kind (video 16:9,
   map) and the iframe's width and height attributes.
   Only addresses of another host come here (expLayoutsSafeValue::externalEmbedUrl(): http(s), //host as https, no
   page of this site); host is the host the handler read from the address, for the note.
   Parameters: src, host, kind (video, map), title, width, height, allow, load_label. *}
<div class="explayouts-embed explayouts-embed-{$kind|wash}" data-explayouts-embed="{$src|wash}" data-embed-title="{$title|wash}" data-embed-allow="{first_set( $allow, '' )|wash}" data-embed-width="{$width|wash}" data-embed-height="{$height|wash}">
    <button type="button" class="explayouts-embed-load">{$load_label|wash}</button>
    <p class="explayouts-embed-note">{'Loading it sends data to %host.'|i18n( 'design/standard/explayouts/block/embed', '', hash( '%host', first_set( $host, '' ) ) )|wash}</p>
</div>
<script src={'javascript/explayouts_embed.js'|ezdesign} defer></script>
