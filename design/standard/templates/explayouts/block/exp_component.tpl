{* The content component blocks (exp_component_hero, _features, _about, _logos, _quote, _lead) without a design of
   their own. Their content parameter names content of the reference installation, which a theme resolves (the
   component_content operator of sevenx_themes_media); the standard design has no such resolution and draws an empty
   block, so a layout with a component block renders instead of failing on a missing template. A design overrides
   explayouts/block/exp_component_<type>.tpl. *}
<div class="slb slb-component slb-{$block.definition_identifier|wash}" data-block-id="{$block.id|wash}" data-view-type="{$block.view_type|wash}"></div>
