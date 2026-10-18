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

### Fixed

- The export of the layout list failed: the class is `expLayoutsExporter` again (the old name stays as an alias).
- The scripts under `bin/php` read their arguments when run through `bin/php/ezexec.php`.
- The content component blocks `exp_component_*` have a standard template.

### Upgrade

- New classes (`expLayoutsSafeValue`, `expLayoutsPreviewSiteAccess`) and template operators: run
  `php bin/php/ezpgenerateautoloads.php -e` and clear the INI and template caches.
- Run `php bin/php/ezexec.php -s <siteaccess> extension/explayouts/bin/php/check_rules.php` to list enabled rules
  without a target and targets or conditions of an unknown type.
