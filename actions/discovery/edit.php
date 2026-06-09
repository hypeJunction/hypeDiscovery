<?php

namespace hypeJunction\Discovery;

elgg_make_sticky_form('discovery/edit');

$guid = get_input('guid');
$entity = $guid ? get_entity((int) $guid) : null;

if (!$entity instanceof \ElggEntity || !$entity->canEdit()) {
	return elgg_error_response(elgg_echo('actionnotauthorized'), REFERRER, ELGG_HTTP_FORBIDDEN);
}

$entity->og_title = htmlentities(get_input('og_title', ''), ENT_QUOTES, 'UTF-8');
$entity->og_description = get_input('og_description');
$entity->og_keywords = elgg_string_to_array(get_input('og_keywords', ''));
$entity->discoverable = (bool) get_input('discoverable', false);
$entity->embeddable = (bool) get_input('embeddable', false);

if ($entity->save()) {
	$entity->saveIconFromUploadedFile('og_image', 'open_graph_image');
	elgg_clear_sticky_form('discovery/edit');
	elgg_register_success_message(elgg_echo('discovery:site:success'));
} else {
	elgg_register_error_message(elgg_echo('discovery:site:error'));
}
