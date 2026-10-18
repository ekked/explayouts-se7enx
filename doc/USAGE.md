# Using explayouts

## Resolving a layout in PHP

```php
<?php
// Resolve the layout for the current request URI (falls back to
// ResolverSettings/DefaultLayout from explayouts.ini):
$layout = expLayoutsResolver::resolve();

// Or resolve for an explicit path:
$layout = expLayoutsResolver::resolve( 'about-us/team' );

if ( $layout instanceof expLayoutsLayout )
{
    // Prepare the full zone/block tree for rendering (status 2 = published)
    $prepared = expLayoutsRenderer::prepareLayout( $layout, 2 );
    // $prepared is an array: id, identifier, name, layout_type,
    // zones (each with blocks), block_count
}
```

### The resolver cache

Each answer is kept in `var/<site>/cache/explayouts/resolver/<md5 of path|siteaccess>.php` for
`[ResolverSettings] CacheTTL` seconds (default 3600; "no rule matched" and the default layout at most 300).
`expLayoutsResolver::clearCache()` removes every entry and bumps the admin generation; publishing a layout and
changing a rule run it. Deleting the directory by hand clears it as well.

A process also remembers the entries it read or wrote, for the rest of its life when the server keeps it for many
requests (Exponential Velocity with `ForkPerRequest=disabled`). That copy is checked against the file once per request
(`$_SERVER['REQUEST_TIME_FLOAT']` tells requests apart), with one `stat()` and no database query: an entry whose file
was deleted, rewritten by another process or has passed its TTL is not used, so a clear made by Apache, another worker
or the command line reaches every server on the next request, without a restart.

## Resolving a layout in templates

The extension registers fetch functions on the `explayouts` module:

```smarty
{* By node *}
{def $layout = fetch( 'explayouts', 'resolve_layout_for_node',
                      hash( 'node_id', $module_result.content_info.node_id ) )}

{* By path (current request when path is omitted) *}
{def $layout2 = fetch( 'explayouts', 'resolve_layout', hash() )}

{* By identifier *}
{def $layout3 = fetch( 'explayouts', 'layout', hash( 'identifier', 'homepage' ) )}

{* Rules that map to a node *}
{def $rules = fetch( 'explayouts', 'rules_for_node',
                     hash( 'node_id', $module_result.content_info.node_id ) )}
```

Layout rendering templates live under `design/standard/templates/explayouts/` (`layout.tpl`, `zone.tpl`, and per-type templates in `layouts/`, e.g. `2_column.tpl`). `design/standard/templates/pagelayout.tpl` shows a complete integration: resolve the layout for the current node/path, then include `explayouts/layout.tpl` with the prepared layout.

## Working with value objects

```php
<?php
$layout = expLayoutsLayout::fetch( $layoutId );
$layout = expLayoutsLayout::fetchByIdentifier( 'homepage', 2 );

$zones  = expLayoutsZone::fetchByLayout( $layout->attribute( 'id' ), 2 );
$blocks = expLayoutsBlock::fetchByZone( $zones[0]->attribute( 'id' ), 2 );
// Pass null as status to fetch drafts and published rows together:
$allZones = expLayoutsZone::fetchByLayout( $layout->attribute( 'id' ), null );
```

## Block handlers

Block definitions come from `explayouts.ini`:

```php
<?php
$handler = expLayoutsBlockHandlerFactory::get( 'text' );   // expLayoutsTextBlockHandler
$params  = $handler->getParameters();          // parameter definitions
$values  = $handler->getValues( $blockArray ); // values for the view template

$available = expLayoutsBlockHandlerFactory::getAvailableBlocks();
$info      = expLayoutsBlockHandlerFactory::getBlockInfo( 'list' );
// $info: identifier, name, view_types, has_collection
```

## Dynamic collection queries

Blocks with `HasCollection=1` attach a collection. Dynamic collections run a query handler:

```php
<?php
$queryHandler = expLayoutsQueryHandlerFactory::get( 'children' ); // expLayoutsChildrenQueryHandler
$queries = expLayoutsQueryHandlerFactory::getAvailableQueries();
// children, parent, subtree, siblings, latest, random, manual,
// exp_content_relation_list, exp_content_reverse_relation_list, exp_content_tags
```

## Layout types

```php
<?php
$types = expLayoutsLayoutType::getAvailableTypes();
$info  = expLayoutsLayoutType::getTypeInfo( '2_column' );
$zones = expLayoutsLayoutType::getZones( '2_column' );   // e.g. array( 'left', 'right' )
```

Shipped layout types: `1_column`, `2_column`, `3_column`, `4_column`, `hero`, `sidebar_left`, `sidebar_right`, `featured`, `mosaic`.

## Import / export

```php
<?php
$exporter = new expLayoutsExporter();
$importer = new expLayoutsImporter();
```

Used by the transfer endpoints of `explayouts_ui_api` and the Import screen in `explayouts_ui`.

## Admin screens

- Admin module views: `/explayouts/dashboard`, `/explayouts/layout_list`, `/explayouts/layout_edit`, `/explayouts/block_edit`, `/explayouts/rule_list`, `/explayouts/rule_edit`, `/explayouts/preview`, `/explayouts/setup`, `/explayouts/template_editor`.
- The "Layouts" tab on the admin content node view shows layouts and rules for the node (template `design/admin/templates/tabs/explayouts.tpl`).
- The template editor (`/explayouts/template_editor`, and `/explayouts_ui/template_editor` of `explayouts_ui`) lists, reads and writes only the `.tpl` files inside `explayouts.ini` `[TemplateEditorSettings] AllowedTemplateRoots[]`. Shipped are the folders of the layout type and block templates (`extension/explayouts/design/standard/templates/explayouts/{layouts,block,tpl_block}`); an empty list allows nothing. Do not add `design` or `extension` as a whole: whoever may open the editor could then change the administration's page layout. A site design that overrides layout templates adds its own folder in `settings/override/explayouts.ini.append.php`.
- The editor creates a template that does not exist yet only with `AllowNewTemplates=enabled` (its New button is shown only then). Before a template is overwritten, its previous version is copied to `<var directory>/explayouts/template_backup/<path>`. That is one generation: the next save replaces it. No `.tpl.bak` is written next to the template. The var directory must not be served by the web server; the `.htaccess_root` shipped with Exponential serves only listed storage and cache paths below `var/` and sends everything else to `index.php`. A symbolic link inside a root is never listed as editable, read or written. Existing `*.tpl.bak` files from earlier versions can be deleted.

## The Exp Layouts panel of the debug bar

With Exponential 6.0.15 or later the Exp Debug bar has an **Exp Layouts** tab on public pages, for users who may edit
layouts (`explayouts_ui/edit`); the bar also shows to anonymous visitors when `DebugByIP` matches, and they do not
get it. Its chip in the summary names the layout of the page (`Layout: Landing Page / Home`). The tab shows:

- the layout (name, identifier, id, type) and what chose it: a rule, the resolver cache, the default layout
  (`[ResolverSettings] DefaultLayout`) or nothing, with the path and the siteaccess;
- links to edit the layout and the rule in the administration, to the rule list, and to the page with
  `?expl_debug=1`, the overlay that lists the blocks found in the page;
- every zone with its blocks in order, nested blocks under their parent, linked zones with their source, each block
  with a link to edit it;
- the blocks available for site layouts (`[BlockSettings] AvailableBlocks[]`) with name, category and views.

The panel reads what `expLayoutsResolver::resolve()` answered for the page (`pageResolution()`: the request's path,
else the URL alias of the page's node; the layouts a page resolves for other nodes are not the page's) and how
`expLayoutsRenderer::prepareLayout()` prepared that layout (`prepared()`). Its own queries are the page's node, when
the request's path is not what the page resolved, and the layout's name when the layout was resolved but not
rendered. It is registered in
`settings/debugbar.ini.append.php`; older kernels ignore that file. The links go to
`explayouts.ini [DebugBarSettings] AdminURL` when it is set (an administration on its own host), else to the site's
directory followed by the first plain name of `[AdminLayoutSettings] SiteAccessMatch[]` (`/admin`).

## CLI

Helper scripts under `bin/php/`, e.g.:

```bash
php extension/explayouts/bin/php/layout_list.php
php extension/explayouts/bin/php/layout_publish.php
php extension/explayouts/bin/php/diagnose.php
php extension/explayouts/bin/php/updatecomponentblockidentifiers.php --dry-run
php extension/explayouts/bin/php/updatelinklocationreferences.php --dry-run
```

## Upgrading: the component block identifiers

The content component blocks (hero, features, about, logos, quote, lead) are
`exp_component_<type>`. Up to 1.4.9 they were `ibexa_component_<type>`. After
upgrading, rename the blocks already stored, once, from the installation's root:

```bash
php extension/explayouts/bin/php/updatecomponentblockidentifiers.php --dry-run
php extension/explayouts/bin/php/updatelinklocationreferences.php --dry-run
php extension/explayouts/bin/php/updatecomponentblockidentifiers.php
php bin/php/ezcache.php --clear-id=content,template-block --allow-root-user
```

It renames the blocks in every status (published, draft, archived), works on
every database Exponential supports, and changes nothing when run again. Add
`-s <siteaccess>` where the database is set per siteaccess.

Until it has run, a block stored under the old name still finds its
definition (`expLayoutsBlockHandlerFactory::currentIdentifier()`), and a theme
that includes `explayouts/block/<identifier>.tpl` can map the name the same
way. The admin's Components page lists component usages by the new names, so
it shows them once the blocks are renamed. A theme with its own
`ibexa_component_<type>.tpl` templates renames them to
`exp_component_<type>.tpl`.

## Upgrading: the block link references

A block link that names a node is stored as `exp-remote-location://<remote id>`:
remote ids come with the content, while node ids are handed out at install
time, so the link resolves to the same node on every installation. Up to 1.4.9
such links were stored as `ibexa-location://<id>`, an id of the reference
installation. Convert them once after upgrading:

```bash
php extension/explayouts/bin/php/updatelinklocationreferences.php --dry-run
php extension/explayouts/bin/php/updatelinklocationreferences.php
```

`expLayoutsLinkParameter::referencedNode()` reads every form —
`exp-remote-location://`, `ezlocation://<node id>` and, for old data,
`ibexa-location://` through `[NexusNodeMap]` — and the renderer and the link
parameter both use it, so a link resolves the same way wherever it is read.

## Customization

### Settings layer (INI cascade)

Every setting shipped in `extension/explayouts/settings/*.ini.append.php` can be overridden from outside the extension. The effective value is resolved through the standard INI cascade, from lowest to highest priority:

1. `extension/explayouts/settings/` — the defaults shipped here
2. `settings/siteaccess/<siteaccess>/` — per-siteaccess overrides
3. `extension/<your_extension>/settings/siteaccess/<siteaccess>/` — siteaccess overrides shipped in an active extension
4. `settings/override/` — global overrides, always win

Examples (place in `settings/override/explayouts.ini.append.php` or a siteaccess variant):

```ini
[ResolverSettings]
# Fallback layout when no rule matches
DefaultLayout=homepage

[BlockSettings]
# Add your own block
AvailableBlocks[]=my_block

[BlockDefinition_my_block]
Name=My block
Handler=myBlockHandler
ViewTypes[]=default

[LayoutType_landing]
Name=Landing page
Zones[]=hero
Zones[]=main
```

To swap the handler of a shipped block, redefine only its section:

```ini
[BlockDefinition_text]
Handler=myTextBlockHandler
```

The admin "Layouts" node tab can be disabled by overriding `admininterface.ini` `[WindowControlsSettings] AdditionalTabs[]` without the `layouts` entry, and the admin menu entries by overriding the `menu.ini` sections (`Topmenu_explayouts_dashboard`, `Leftmenu_explayouts_dashboard`).

### Template layer (design override cascade)

Frontend templates resolve through the design cascade: current siteaccess design, then additional site designs, then `standard`. To restyle a layout without touching this extension, ship the same-relative-path template in your own design extension, e.g.:

```
extension/mytheme/design/mydesign/templates/explayouts/layout.tpl
extension/mytheme/design/mydesign/templates/explayouts/zone.tpl
extension/mytheme/design/mydesign/templates/explayouts/layouts/2_column.tpl
```

with `mydesign` selected as (additional) site design for the siteaccess. The same works for the admin design (`design/admin/templates/tabs/explayouts.tpl`) from an admin-design extension. Block view templates referenced by `ViewTypes[]` follow the same rule.

### PHP layer (safe extension points)

- **Block handlers** — implement `expLayoutsBlockHandlerInterface` (`getParameters()`, `getValues( $block )`) in your own extension and wire it via `BlockDefinition_<id>` `Handler=`. This is the primary extension point; no core code changes needed.
- **Query handlers** — implement `expLayoutsQueryHandlerInterface` and register it via `[QuerySettings] AvailableQueries[]` plus a `QueryType_<id>` section with `Handler=`. Sibling extensions `explayouts_relation_list_query` and `explayouts_tags_query` are working examples (`exp_content_relation_list`, `exp_content_tags`).
- **Fetch functions** — build page logic on the `explayouts` module fetch functions (`layout`, `resolve_layout`, `resolve_layout_for_node`, `rules_for_node`) rather than querying the `explayouts_*` tables directly.
- **Services** — for create/update/delete flows, use the `explayouts_core` service classes instead of storing `eZPersistentObject` rows yourself.

Avoid subclassing the persistent value objects (`expLayoutsLayout` etc.); their definition maps to the `explayouts_*` schema and is treated as internal.
