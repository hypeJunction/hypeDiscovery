<?php

namespace hypeJunction\Discovery;

$entity = elgg_extract('entity', $vars);
/* @var $entity \ElggSite */

echo elgg_view_input('file', [
	'name' => 'og_image',
	'value' => ($entity->og_icontime),
	'label' => elgg_echo('discovery:og:site_image'),
	'help' => elgg_echo('discovery:og:site_image:help'),
]);

echo elgg_view('framework/discovery/icon', [
	'entity' => $entity,
	'size' => 'medium',
	'img_class' => 'elgg-photo elgg-field',
]);

echo elgg_view_input('text', [
	'name' => 'og_site_name',
	'value' => ($entity->og_site_name) ? $entity->og_site_name : $entity->name,
	'label' => elgg_echo('discovery:og:site_name'),
]);

echo elgg_view_input('text', [
	'name' => 'og_description',
	'value' => ($entity->og_description) ? $entity->og_description : $entity->description,
	'label' => elgg_echo('discovery:og:description'),
]);

echo elgg_view_input('text', [
	'name' => 'fb_app_id',
	'value' => $entity->fb_app_id,
	'label' => elgg_echo('discovery:fb:app_id'),
]);

echo elgg_view_input('text', [
	'name' => 'twitter_site',
	'value' => $entity->twitter_site,
	'label' => elgg_echo('discovery:twitter:site'),
]);

echo elgg_view_input('submit', [
	'value' => elgg_echo('save'),
	'field_class' => 'elgg-foot',
]);
