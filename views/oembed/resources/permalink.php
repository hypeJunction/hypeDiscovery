<?php

namespace hypeJunction\Discovery;

// MANUAL-MIGRATION (3.x -> 4.x): the registry-based asset model was removed in 4.x.
// elgg_unregister_css()/elgg_unregister_js() no longer exist and have no global equivalent —
// default CSS (elgg, font-awesome, lightbox) and JS (jquery-ui) are now loaded via the page
// shell / AMD and cannot be selectively unregistered from a resource view. To render a stripped
// embed page in 4.x, override the page shell (e.g. a dedicated 'oembed' viewtype page view) or
// use elgg_unextend_view() against the head/page views. Decision required from maintainer.
//
// Likewise the X-Frame-Options handler is now \Elgg\Page\SetXFrameOptionsHeaderHandler
// (a Class::method handler), not the removed _elgg_views_send_header_x_frame_options function,
// so the old unregister-by-function-name call cannot match it in 4.x.

$entity = \elgg_extract('entity', $vars);
if (!$entity) {
	return;
}

$title = get_discovery_title($entity);

$content = \elgg_view('framework/discovery/public', $vars);

echo \elgg_view_page($title, $content);