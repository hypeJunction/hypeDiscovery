<?php

namespace hypeJunction\Discovery;

$entity = elgg_extract('entity', $vars);

echo elgg_format_element('div', [
	'class' => 'elgg-text-help',
], elgg_echo('discovery:og:help'));

if (is_discoverable_type($entity)) {
	echo elgg_view_input('select', array(
		'name' => 'discoverable',
		'value' => (isset($entity->discoverable)) ? (bool) $entity->discoverable : is_discoverable($entity),
		'options_values' => array(
			0 => elgg_echo('option:no'),
			1 => elgg_echo('option:yes'),
		),
		'label' => elgg_echo('discovery:og:discoverable'),
	));
}

if (is_embeddable_type($entity)) {
	echo elgg_view_input('select', array(
		'name' => 'embeddable',
		'value' => (isset($entity->discoverable)) ? (bool) $entity->embeddable : is_embeddable($entity),
		'options_values' => array(
			0 => elgg_echo('option:no'),
			1 => elgg_echo('option:yes'),
		),
		'label' => elgg_echo('discovery:og:embeddable'),
	));
}

echo elgg_view_input('file', array(
	'name' => 'og_image',
	'value' => $entity->hasIcon('large', 'open_graph_image'),
	'label' => elgg_echo('discovery:og:image'),
));

echo elgg_view('framework/discovery/icon', array(
	'entity' => $entity,
	'size' => 'medium',
	'img_class' => 'elgg-photo elgg-field',
));

echo elgg_view_input('text', array(
	'name' => 'og_title',
	'value' => get_discovery_title($entity),
	'label' => elgg_echo('discovery:og:title'),
));

echo elgg_view_input('text', array(
	'name' => 'og_description',
	'value' => get_discovery_description($entity),
	'label' => elgg_echo('discovery:og:description'),
));

echo elgg_view_input('tags', array(
	'name' => 'og_keywords',
	'value' => get_discovery_keywords($entity),
	'label' => elgg_echo('discovery:og:keywords'),
));

echo elgg_view_input('hidden', array(
	'name' => 'guid',
	'value' => $entity->guid
));

echo elgg_view_input('submit', array(
	'value' => elgg_echo('save'),
	'field_class' => 'elgg-foot',
));
?>
<script>
	require(['forms/discovery/edit']);
</script>