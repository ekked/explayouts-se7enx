{* The "Layouts" tab of the content full view (admininterface.ini [AdditionalTab_layouts]): drawn only for users with
   the policy explayouts/read, like the tab content itself (tabs/explayouts.tpl). Title and Description as the
   kernel's tab header draws them, translated in TranslationContext. *}
{if fetch( 'user', 'has_access_to', hash( 'module', 'explayouts', 'function', 'read' ) )}
{def $layouts_tab_context = ezini( 'AdditionalTab_layouts', 'TranslationContext', 'admininterface.ini' )
     $layouts_tab_title = ezini( 'AdditionalTab_layouts', 'Title', 'admininterface.ini' )|i18n( $layouts_tab_context )
     $layouts_tab_description = ezini( 'AdditionalTab_layouts', 'Description', 'admininterface.ini' )|i18n( $layouts_tab_context )}
<li id="node-tab-{$tab}" class="{if $last}last{else}middle{/if}{if $node_tab_index|eq( $tab )} selected{/if}">
    {if $tabs_disabled}
        <span class="disabled" title="{'Tab is disabled, enable with toggler to the left of these tabs.'|i18n( 'design/admin/node/view/full' )}">{$layouts_tab_title|wash}</span>
    {else}
        <a href={concat( $node_url_alias, '/(tab)/', $tab )|ezurl} title="{$layouts_tab_description|wash}">{$layouts_tab_title|wash}</a>
    {/if}
</li>
{undef $layouts_tab_context $layouts_tab_title $layouts_tab_description}
{/if}
