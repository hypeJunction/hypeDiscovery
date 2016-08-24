<?php

namespace hypeJunction\Discovery;

$viewtype = elgg_extract('viewtype', $vars);
$user_hash = elgg_extract('user_hash', $vars);
$guid = elgg_extract('guid', $vars);

switch ($viewtype) {
	case 'json+oembed' :
	case 'json oembed' :
		$viewtype = 'json';
		break;

	case 'xml+oembed' :
	case 'xml oembed' :
		$viewtype = 'xml';
		break;
}

if (!$user_hash || !$guid || !elgg_is_registered_viewtype($viewtype)) {
	forward('', '404');
}

elgg_set_viewtype($viewtype);

$ia = elgg_set_ignore_access(true);
$entity = get_entity($guid);

$forward_url = false;
if (has_access_to_entity($entity) && (!elgg_get_config('walled_garden') || elgg_is_logged_in())) {
	$forward_url = $entity->getURL();
}

if (is_discoverable($entity)) {
	$forward_url = elgg_trigger_plugin_hook('entity:referred', $entity->getType(), array(
		'entity' => $entity,
		'user_hash' => $user_hash,
		'referrer' => $_SERVER['HTTP_REFERER'],
	), $forward_url);
}

if ($forward_url && $viewtype == 'default') {
	elgg_set_ignore_access($ia);
	forward($forward_url);
}

if ($viewtype == 'oembed') {
	elgg_unregister_css('font-awesome');
	elgg_unregister_css('elgg');
	elgg_unregister_css('lightbox');
	//elgg_unregister_js('jquery');
	elgg_unregister_js('jquery-ui');
	elgg_unregister_plugin_hook_handler('output:before', 'page', '_elgg_views_send_header_x_frame_options');
}

$title = get_discovery_title($entity);
$content = elgg_view('framework/discovery/public', array(
	'entity' => $entity,
));

$layout = elgg_view_layout('default', [
	'title' => false,
	'content' => $content,
]);

echo elgg_view_page($title, $layout);

elgg_set_ignore_access($ia);
