<?php

namespace hypeJunction\Discovery;

$entity = \elgg_extract('entity', $vars);
if (!$entity) {
	return;
}

$title = get_discovery_title($entity);

$layout = \elgg_view_layout('default', [
	'title' => $title,
	'content' => \elgg_view('framework/discovery/public', $vars),
	'sidebar' => \elgg_view('core/account/login_box'),
]);

echo \elgg_view_page($title, $layout);