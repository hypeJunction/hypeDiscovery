<?php

namespace hypeJunction\Discovery;

use Elgg\DefaultPluginBootstrap;

/**
 * hypeDiscovery plugin bootstrap.
 *
 * Most registrations live in elgg-plugin.php's declarative config
 * (view_extensions, actions, routes, hooks, events). This bootstrap
 * only handles the imperative bits that don't fit declarative form:
 *
 * - Admin menu items (registered via elgg_register_menu_item; the
 *   declarative 'menus' key in elgg-plugin.php is for menu hooks,
 *   not menu items)
 *
 * Note: the legacy `elgg_register_css('oembed.css', ...)` call is gone —
 * `elgg_register_css` was removed in 4.x and the oembed.css view at
 * views/default/oembed.css is auto-discovered by simplecache.
 */
class Bootstrap extends DefaultPluginBootstrap
{
    /**
     * @return void
     */
    public function load(): void
    {
    }

    /**
     * @return void
     */
    public function boot(): void
    {
    }

    /**
     * @return void
     */
    public function init(): void
    {
        \elgg_register_menu_item('page', [
            'name' => 'discovery:settings',
            'href' => 'admin/plugin_settings/hypediscovery',
            'text' => \elgg_echo('admin:discovery:settings'),
            'context' => 'admin',
            'section' => 'discovery',
        ]);
        \elgg_register_menu_item('page', [
            'name' => 'discovery:site',
            'href' => 'admin/discovery/site',
            'text' => \elgg_echo('admin:discovery:site'),
            'context' => 'admin',
            'section' => 'discovery',
        ]);
    }

    /**
     * @return void
     */
    public function ready(): void
    {
    }

    /**
     * @return void
     */
    public function shutdown(): void
    {
    }

    /**
     * @return void
     */
    public function activate(): void
    {
    }

    /**
     * @return void
     */
    public function deactivate(): void
    {
    }

    /**
     * @return void
     */
    public function upgrade(): void
    {
    }
}
