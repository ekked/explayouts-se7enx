# Changelog

## Unreleased

### Changed (check before upgrading)

- **A rule without a target matches no page.** It used to match every page. The catch-all is the target
  `path_info_prefix` `/`.
- **A condition of an unknown type is never met.** It used to be ignored, so the rule applied everywhere.
  `ibexa_content_type` is read as the class condition.
- Answers that depend on a `query_parameter` or `time` condition are no longer cached; the resolver cache is pruned
  to `[ResolverSettings] CacheMaxEntries` (5000).
- **Imports create a draft.** Rules of an export come along disabled and with known target and condition types only;
  layout type, block definitions and view types must be ones of the settings; a zone links to a shared layout by its
  identifier only. Both export forms import (this extension's and the layout editor's).
- The classic views save nothing when a form is refused (unknown types, values of the wrong kind) and clear the
  resolver cache after every write; the rule list shows disabled rules too.
- The Layouts tab of the node view is drawn and filled only for users with `explayouts/read`.
- Block values that reach markup are checked: links (http, https, mailto, tel or relative), embeds, colours, CSS
  classes, the element of a title, template names of the template block and of included block templates.
- The layout preview and Load more from the administration render in the administration's public siteaccess
  (`expLayoutsPreviewSiteAccess`, `[PreviewSettings] SiteAccess`), never in one with another database.

### Added (parity with netgen/layouts-standard)

- **Markdown block renders Markdown** (the reference: `markdown_to_html`). With `league/commonmark` installed it is
  CommonMark with HTML in the text escaped, unsafe links dropped and the nesting limited; links with another scheme
  than http, https, mailto and tel become text, images from other hosts become links. Without the library a small
  parser of its own renders headings, paragraphs, lists, quotes, code, emphasis, links and images of the site
  (`expLayoutsMarkdown`). Suggested in `composer.json`.
- **Two columns** has the view types `two_columns_50_50` (first, as in the reference), `_33_66` and `_66_33`;
  **three columns** `three_columns_33_33_33`, `_50_25_25`, `_25_50_25` and `_25_25_50` (a block stored as
  `three_columns` renders 33:33:33); the view types are named (`ViewTypeNames[]`).
- **Layout types `layout_3`, `layout_5` and `layout_6`** of the reference, and templates for `layout_1` to `layout_6`
  with the reference's grid (`layout_4` showed its zones one under the other).
- **External video**: service (YouTube, Vimeo, Dailymotion) and video id as in the reference, and a caption; the
  address field stays for existing blocks. YouTube plays from youtube-nocookie.com, Vimeo with `dnt=1`.
- **Map**: latitude, longitude, zoom (0-20), map type and marker as in the reference. With
  `[MapSettings] GoogleMapsApiKey` the map is Google's Maps Embed API, without it OpenStreetMap; the embed address
  stays for existing blocks.
- Video and map load after a click in the standard templates (`javascript/explayouts_embed.js`): no request to the
  other host before.
- **Gallery blocks**: the reference's design parameters next/previous, pagination, infinite loop, transition,
  autoplay with its time, thumbnails to move, details and details on hover, each for the reference's view types.
- Selects for the title's HTML tag (h1-h6, p, div), the button's style (default, highlighted, link) and target.
- Heading level for the component blocks (hero, about, features, lead); features take a list of features
  (`Title|Text` per line, `expLayoutsFeaturesBlockHandler`).
- Number fields carry min and max where the reference has them.

### Fixed

- The export of the layout list failed: the class is `expLayoutsExporter` again (the old name stays as an alias).
- The scripts under `bin/php` read their arguments when run through `bin/php/ezexec.php`.
- The content component blocks `exp_component_*` have a standard template.

### Upgrade

- New classes (`expLayoutsSafeValue`, `expLayoutsPreviewSiteAccess`, `expLayoutsMarkdown`,
  `expLayoutsFeaturesBlockHandler`) and template operators: run
  `php bin/php/ezpgenerateautoloads.php -e` and clear the INI and template caches.
- Run `php bin/php/ezexec.php -s <siteaccess> extension/explayouts/bin/php/check_rules.php` to list enabled rules
  without a target and targets or conditions of an unknown type.
