## [Unreleased] — Elgg 4.x migration

### Migrated to Elgg 4.x

- composer.json: elgg/elgg ^4.0, php >=7.4, composer/installers ^2.0, PSR-4 autoload (was PSR-0), config.allow-plugins, extra.elgg-plugin.id=hypediscovery
- **Deleted manifest.xml** (composer.json is sole metadata source from 4.x)
- **Deleted start.php** (Iron Law: 4.x rejects start.php)
- **Created elgg-plugin.php** with declarative actions/routes/hooks/events/view_extensions translated from start.php (16 hooks, 1 event, 4 actions, 2 routes, 2 view extensions)
- **Created Bootstrap class** — init() registers the 2 admin menu items
- **Lowercased plugin id** across lib, classes, actions, tests (Iron Law 6)
- **Renamed views/default/plugins/hypeDiscovery → plugins/hypediscovery**
- **16 hook/event handlers** converted to polymorphic guard pattern
- **4 critical render-path elgg_set_ignore_access set/restore pairs refactored to elgg_call(ELGG_IGNORE_ACCESS, fn) closures** per Iron Law 11 — never polyfill removed APIs
- Action `hypediscovery/settings/save` file moved to `actions/hypediscovery/settings/save.php` to match Elgg 4.x auto-derive convention
- `get_user_hash` always returns a string so `uh` query param doesn't drop on anonymous

### Verified

- Activates in elgg4
- Homepage 200/9137, login 200/9272
- Pre-migration test suite: **52/52 passing, 364 assertions** (per-plugin isolation via bin/discover-plugins.sh)

### Deferred to 4→5 or later

- 5 remaining elgg_set_ignore_access pairs in Router route handlers + share.php action (not in render path)
- 10 forward() calls in Router + actions
- md5() ETag (cosmetic)
- `elgg_is_active_plugin('hypeSeo')` guard uses camelCase (silently false in 4.x)

<a name="2.3.2"></a>
## [2.3.2](https://github.com/hypeJunction/hypeDiscovery/compare/2.3.1...v2.3.2) (2018-09-03)


### Bug Fixes

* **seo:** add option to disable crawling on preview pages ([fd1c1ff](https://github.com/hypeJunction/hypeDiscovery/commit/fd1c1ff))



<a name="2.3.1"></a>
## [2.3.1](https://github.com/hypeJunction/hypeDiscovery/compare/2.3.0...v2.3.1) (2017-04-21)


### Bug Fixes

* **discovery:** improve appearance of public entities ([a772052](https://github.com/hypeJunction/hypeDiscovery/commit/a772052))
* **discovery:** improve public profile display ([8b459c4](https://github.com/hypeJunction/hypeDiscovery/commit/8b459c4))
* **share:** use permalinks when in walled garden mode ([1ca5355](https://github.com/hypeJunction/hypeDiscovery/commit/1ca5355))



<a name="2.3.0"></a>
# [2.3.0](https://github.com/hypeJunction/hypeDiscovery/compare/2.2.0...v2.3.0) (2017-04-04)


### Features

* **cards:** allow scraper cards to be shared externally ([97e7a75](https://github.com/hypeJunction/hypeDiscovery/commit/97e7a75))
* **share:** allow any URL to be shared externally ([0edd9b6](https://github.com/hypeJunction/hypeDiscovery/commit/0edd9b6))



<a name="2.2.0"></a>
# [2.2.0](https://github.com/hypeJunction/hypeDiscovery/compare/2.1.2...v2.2.0) (2017-02-03)


### Features

* **deps:** update to latest version of elgg-url-sniffer ([782916a](https://github.com/hypeJunction/hypeDiscovery/commit/782916a))



<a name="2.1.2"></a>
## [2.1.2](https://github.com/hypeJunction/hypeDiscovery/compare/2.1.1...v2.1.2) (2016-10-25)


### Bug Fixes

* **og:** do a better job at resolving open graph images ([1e99b00](https://github.com/hypeJunction/hypeDiscovery/commit/1e99b00))
* **og:** do a better job at resolving open graph images ([8ac878f](https://github.com/hypeJunction/hypeDiscovery/commit/8ac878f))
* **og:** do a better job at resolving open graph images ([295835e](https://github.com/hypeJunction/hypeDiscovery/commit/295835e))
* **sharer:** allow sharing any URL from extras menu ([69e0c28](https://github.com/hypeJunction/hypeDiscovery/commit/69e0c28))
* **sniff:** sniff current route rather than current url ([6a44390](https://github.com/hypeJunction/hypeDiscovery/commit/6a44390))



<a name="2.1.1"></a>
## [2.1.1](https://github.com/hypeJunction/hypeDiscovery/compare/2.1.0...v2.1.1) (2016-10-14)


### Bug Fixes

* **ui:** simplify extras menu ([5fb4b4f](https://github.com/hypeJunction/hypeDiscovery/commit/5fb4b4f))



<a name="2.1.0"></a>
# [2.1.0](https://github.com/hypeJunction/hypeDiscovery/compare/2.0.2...v2.1.0) (2016-10-13)


### Bug Fixes

* **hashes:** hashes now remain static across the session ([30e911c](https://github.com/hypeJunction/hypeDiscovery/commit/30e911c))
* **menus:** update deprecated uses of class parameter ([bcbe3df](https://github.com/hypeJunction/hypeDiscovery/commit/bcbe3df))
* **metatags:** correctly set meta properties for OGP tags ([a1c3183](https://github.com/hypeJunction/hypeDiscovery/commit/a1c3183))

### Features

* **discovery:** improve UX/UI of the discovery process and pages ([df34293](https://github.com/hypeJunction/hypeDiscovery/commit/df34293))
* **errors:** catch some errors and redirect to entity permalinks ([eaa502b](https://github.com/hypeJunction/hypeDiscovery/commit/eaa502b))
* **links:** set canonical URLs on permalink pages ([59fb329](https://github.com/hypeJunction/hypeDiscovery/commit/59fb329))



<a name="2.0.2"></a>
## [2.0.2](https://github.com/hypeJunction/hypeDiscovery/compare/2.0.1...v2.0.2) (2016-08-24)


### Bug Fixes

* **icons:** default to site image if no icon found ([65e64ba](https://github.com/hypeJunction/hypeDiscovery/commit/65e64ba))



<a name="2.0.1"></a>
## [2.0.1](https://github.com/hypeJunction/hypeDiscovery/compare/2.0.0...v2.0.1) (2016-08-24)




<a name="2.0.0"></a>
# [2.0.0](https://github.com/hypeJunction/hypeDiscovery/compare/v1.0.0...v2.0.0) (2016-08-24)


### Features

* **releases:** upgrade to 2.2 ([815c9f9](https://github.com/hypeJunction/hypeDiscovery/commit/815c9f9))


### BREAKING CHANGES

* releases: Now requires Elgg 2.2
Most hook and event handlers were refactored into class methods
Many of the views have been updated
Global variables and config values have been removed



