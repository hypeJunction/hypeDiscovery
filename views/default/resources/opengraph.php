<?php
/**
 * Dispatcher for the `opengraph` route (/opengraph/{segments}).
 *
 * The route was ported from a 2.x page handler but named a resource view,
 * resources/opengraph.php, that was never created — so /opengraph/edit/<guid> and
 * /opengraph/share/<guid> raised ResourceNotFoundException and 404'd
 * (bd elgg-migrate-ckn0c). The page-handler logic itself survived the port as
 * \hypeJunction\Discovery\Router::opengraphHandler(); it echoes the page (or the bare
 * content for an XHR request) and returns false when the segments do not resolve.
 */

use Elgg\Exceptions\Http\PageNotFoundException;
use hypeJunction\Discovery\Router;

$segments = (string) elgg_extract('segments', $vars, '');
$parts = array_values(array_filter(explode('/', trim($segments, '/')), 'strlen'));

if (empty($parts) || !Router::opengraphHandler($parts, 'opengraph')) {
	throw new PageNotFoundException();
}
