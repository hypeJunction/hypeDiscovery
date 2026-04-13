<?php

$params = get_input('params', array(), false); // don't filter the results so that html inputs remain unchanged
$plugin = elgg_get_plugin_from_id('hypediscovery');

if (!($plugin instanceof ElggPlugin)) {
	elgg_register_error_message(elgg_echo('plugins:settings:save:fail', array('hypediscovery')));
	forward(REFERER);
}

$plugin_name = $plugin->getManifest()->getName();

$result = false;

foreach ($params as $k => $v) {
	if (is_array($v)) {
		$v = json_encode($v);
	}
	$result = $plugin->setSetting($k, $v);
	if (!$result) {
		elgg_register_error_message(elgg_echo('plugins:settings:save:fail', array($plugin_name)));
		forward(REFERER);
		exit;
	}
}

elgg_register_success_message(elgg_echo('plugins:settings:save:ok', array($plugin_name)));
forward(REFERER);
