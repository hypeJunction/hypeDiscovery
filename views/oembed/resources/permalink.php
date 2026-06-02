<?php

namespace hypeJunction\Discovery;

\elgg_unrequire_css('font-awesome');
\elgg_unrequire_css('elgg');
\elgg_unrequire_css('lightbox');
//elgg_unrequire_js('jquery');
\elgg_unrequire_js('jquery-ui');
\elgg_unregister_event_handler('output:before', 'page', \Elgg\Page\SetXFrameOptionsHeaderHandler::class);

$entity = \elgg_extract('entity', $vars);
if (!$entity) {
	return;
}

$title = get_discovery_title($entity);

$content = \elgg_view('framework/discovery/public', $vars);

echo \elgg_view_page($title, $content);
