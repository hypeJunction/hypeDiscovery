# hypeDiscovery — Architecture (Elgg 5.x)

## Purpose

Enhanced social presence and discovery of Elgg content: OpenGraph/Twitter Card
meta tags, oEmbed export, permalink redirects, social share UI, and entity
analytics tracking.

## Directory layout

```
hypediscovery/
├── classes/hypeJunction/Discovery/
│   ├── Bootstrap.php       # Bootstrap — load() requires lib/functions.php; init() registers admin menu items
│   ├── Analytics.php       # Event handler: save temp user hash on login
│   ├── Discovery.php       # Event handlers: head:page meta tags, metatags, oEmbed export
│   ├── Icons.php           # Event handlers: entity:icon:url and open_graph_image variants
│   ├── Menus.php           # Event handlers: entity/extras/discovery_share/scraper menus
│   └── Router.php          # Event handlers: publicPages, servicesRoute, redirectErrorToPermalink; inline head:page closures
│   └── Upgrades/
│       └── EncodeSettingsAsJson.php
├── lib/
│   └── functions.php       # Namespace hypeJunction\Discovery procedural helpers (is_discoverable, get_entity_permalink, etc.)
├── actions/
│   ├── discovery/share.php # Social share action
│   ├── discovery/edit.php
│   ├── discovery/site.php
│   └── hypediscovery/settings/save.php
├── views/default/
│   ├── plugins/hypediscovery/settings.php
│   └── resources/...
├── languages/en.php        # Returns translation array (return $array; format)
├── elgg-plugin.php         # Plugin manifest — events, actions, routes, upgrades, view_extensions
└── composer.json           # php >=8.2, elgg/elgg ^5.0
```

## Plugin manifest (`elgg-plugin.php`)

| Key               | Description |
|-------------------|-------------|
| `bootstrap`       | `Bootstrap` — `load()` requires `lib/functions.php`; `init()` adds admin sidebar items |
| `events`          | 16 event handlers (see below) |
| `actions`         | 4 actions (settings/save, discovery/site, discovery/share, discovery/edit) |
| `routes`          | `permalink` and `opengraph` URL patterns |
| `upgrades`        | `EncodeSettingsAsJson` — migrates stored plugin settings from serialize to JSON |
| `view_extensions` | elgg.css ← discovery.css; elements/icons.css ← webicons.css |

## Registered event handlers

| Event name                     | Type          | Handler class / method                  |
|--------------------------------|---------------|-----------------------------------------|
| `public_pages`                 | `walled_garden` | `Router::publicPages`                |
| `forward`                      | `login`/`403`/`404` | `Router::redirectErrorToPermalink` |
| `register`                     | `menu:entity`  | `Menus::entityMenuSetup`              |
| `register`                     | `menu:extras`  | `Menus::extrasMenuSetup`              |
| `register`                     | `menu:discovery_share` | `Menus::shareMenuSetup`        |
| `register`                     | `menu:scraper:card` | `Menus::setupCardMenu`            |
| `entity:icon:url`              | `all`         | `Icons::entityIconURL`                  |
| `entity:open_graph_image:file` | `all`         | `Icons::entityOpenGraphImageFile`       |
| `entity:open_graph_image:url`  | `all`         | `Icons::entityOpenGraphImageURL`        |
| `entity:open_graph_image:sizes`| `all`         | `Icons::entityOpenGraphImageSizes`      |
| `head`                         | `page`        | `Discovery::prepareMetas`               |
| `head`                         | `page`        | `Discovery::prepareAlternateLinks`      |
| `metatags`                     | `discovery`   | `Discovery::graphExport`                |
| `export:entity`                | `oembed`      | `Discovery::oEmbedExport`               |
| `route`                        | `services`    | `Router::servicesRoute`                 |
| `login:after`                  | `user`        | `Analytics::saveTempUserHash`           |

All handlers take `\Elgg\Event $event` (Elgg 5.x style).

`Router::permalinkHandler` and `opengraphHandler` also register inline closures
on `head:page` at request time for canonical link and robots noindex.

## Dependencies

| Package                   | Version   | Why |
|---------------------------|-----------|-----|
| `elgg/elgg`               | `^5.0`    | Core platform |
| `composer/installers`     | `^2.0`    | Composer 2.2+ requirement |
| `mrclay/elgg-url-sniffer` | `~3.0`    | URL-to-GUID resolution in lib/functions.php |

## Migration notes (4.x → 5.x)

### Plugin manifest
- `'hooks'` key renamed to `'events'` — merged with existing `'events'` key
- `require_once 'lib/functions.php'` removed from top of `elgg-plugin.php`; moved to `Bootstrap::load()`

### Hook handlers → Event handlers
- All 16 static methods converted from the polymorphic 4-arg `($hook, $type = null, $return = null, $params = null)` / `\Elgg\Hook` compatibility shim to `(\Elgg\Event $event)` with `$event->getValue()` / `$event->getParams()`
- Inline closures in `Router::permalinkHandler()` converted to `\Elgg\Event $event` signature
- `elgg_register_plugin_hook_handler()` → `elgg_register_event_handler()`
- `elgg_trigger_plugin_hook()` → `elgg_trigger_event_results()` (3 call sites in lib/functions.php and actions)

### Removed API
- `current_page_url()` removed in Elgg 5.x → replaced with `elgg_get_current_url()` (8 call sites)
- `add_translation('en', $array)` in languages/en.php → `return $array;`

### Infrastructure
- PHP 7.4 → 8.2; MySQL 5.7 → 8.0; Elgg 4.x Docker stack → 5.x

### No data migration required
- `EncodeSettingsAsJson` upgrade already shipped; runs once at upgrade time

## Tests

PHPUnit integration suite: 52 tests, 364 assertions.

- `makeEvent()` helper creates mock `\Elgg\Event` with stubbed `getValue()`, `getParams()`, `getParam()`
- Session login uses `_elgg_services()->session_manager->setLoggedInUser()` (Elgg 5.x API; was `elgg_get_session()->setLoggedInUser()`)
- Entity menu test calls `Menus::entityMenuSetup()` directly (avoids Elgg 5.x `MenuItems` collection incompatibility)
