<?php

namespace hypeJunction\Discovery;

elgg_unregister_external_file('css', 'font-awesome');
elgg_unregister_external_file('css', 'lightbox');
//elgg_unregister_external_file('js', 'jquery');
elgg_unregister_external_file('js', 'jquery-ui');

$entity = elgg_extract('entity', $vars);
if (!$entity) {
	return;
}

$title = get_discovery_title($entity);

$content = elgg_view('framework/discovery/public', $vars);

echo elgg_view_page($title, $content);
