<?php

require_once __DIR__ . '/lib/functions.php';

use hypeJunction\Discovery\Analytics;
use hypeJunction\Discovery\Discovery;
use hypeJunction\Discovery\Icons;
use hypeJunction\Discovery\Menus;
use hypeJunction\Discovery\Router;

return [
    'plugin' => [
        'name' => 'hypeDiscovery',
        'activate_on_install' => false,
    ],
    'bootstrap' => \hypeJunction\Discovery\Bootstrap::class,

    'view_extensions' => [
        'elgg.css' => [
            'discovery.css' => [],
        ],
        'elements/icons.css' => [
            'webicons.css' => [],
        ],
    ],

    'actions' => [
        'hypediscovery/settings/save' => [],
        'discovery/site' => ['access' => 'admin'],
        'discovery/share' => ['access' => 'public'],
        'discovery/edit' => [],
    ],

    'routes' => [
        'permalink' => [
            'path' => '/permalink/{segments}',
            'resource' => 'permalink',
            'requirements' => ['segments' => '.+'],
            'defaults' => ['segments' => ''],
        ],
        'opengraph' => [
            'path' => '/opengraph/{segments}',
            'resource' => 'opengraph',
            'requirements' => ['segments' => '.+'],
            'defaults' => ['segments' => ''],
        ],
    ],

    'hooks' => [
        'public_pages' => [
            'walled_garden' => [
                Router::class . '::publicPages' => [],
            ],
        ],
        'forward' => [
            'login' => [Router::class . '::redirectErrorToPermalink' => []],
            '403' => [Router::class . '::redirectErrorToPermalink' => []],
            '404' => [Router::class . '::redirectErrorToPermalink' => []],
        ],
        'register' => [
            'menu:entity' => [Menus::class . '::entityMenuSetup' => []],
            'menu:extras' => [Menus::class . '::extrasMenuSetup' => []],
            'menu:discovery_share' => [Menus::class . '::shareMenuSetup' => []],
            'menu:scraper:card' => [Menus::class . '::setupCardMenu' => []],
        ],
        'entity:icon:url' => [
            'all' => [Icons::class . '::entityIconURL' => []],
        ],
        'entity:open_graph_image:file' => [
            'all' => [Icons::class . '::entityOpenGraphImageFile' => []],
        ],
        'entity:open_graph_image:url' => [
            'all' => [Icons::class . '::entityOpenGraphImageURL' => []],
        ],
        'entity:open_graph_image:sizes' => [
            'all' => [Icons::class . '::entityOpenGraphImageSizes' => []],
        ],
        'head' => [
            'page' => [
                Discovery::class . '::prepareMetas' => [],
                Discovery::class . '::prepareAlternateLinks' => [],
            ],
        ],
        'metatags' => [
            'discovery' => [Discovery::class . '::graphExport' => []],
        ],
        'export:entity' => [
            'oembed' => [Discovery::class . '::oEmbedExport' => []],
        ],
        'route' => [
            'services' => [Router::class . '::servicesRoute' => []],
        ],
    ],

    'events' => [
        'login:after' => [
            'user' => [Analytics::class . '::saveTempUserHash' => []],
        ],
    ],
];
