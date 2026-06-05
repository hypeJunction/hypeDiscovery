<?php

$params = get_input('params', [], false); // don't filter the results so that html inputs remain unchanged
$plugin = elgg_get_plugin_from_id('hypediscovery');

if (!($plugin instanceof ElggPlugin)) {
	return elgg_error_response(elgg_echo('plugins:settings:save:fail', ['hypediscovery']), REFERRER, ELGG_HTTP_BAD_REQUEST);
}

$plugin_name = $plugin->getManifest()->getName();

$result = false;

foreach ($params as $k => $v) {
	if (is_array($v)) {
		$v = json_encode($v);
	}

	$result = $plugin->setSetting($k, $v);
	if (!$result) {
		return elgg_error_response(elgg_echo('plugins:settings:save:fail', [$plugin_name]), REFERRER, ELGG_HTTP_BAD_REQUEST);
	}
}

return elgg_ok_response('', elgg_echo('plugins:settings:save:ok', [$plugin_name]), REFERRER);
