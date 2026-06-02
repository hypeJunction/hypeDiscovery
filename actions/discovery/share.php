<?php

namespace hypeJunction\Discovery;

$provider = get_input('provider');
$guid = get_input('guid');
$referrer = get_input('referrer');
$share_url = get_input('share_url');

$error = false;

list($forward_url, $error) = \elgg_call(ELGG_IGNORE_ACCESS, function () use ($guid, $provider, $referrer, $share_url) {
	$error = false;
	$forward_url = REFERRER;

	if ($guid) {
		$entity = get_entity($guid);
		if (!is_discoverable($entity)) {
			$error = true;
		} else {
			$forward_url = get_provider_url($provider, $entity, $referrer);
			$forward_url = \elgg_trigger_plugin_hook('entity:share', $entity->getType(), array(
				'provider' => $provider,
				'entity' => $entity,
				'referrer' => $referrer,
					), $forward_url);
		}
	} else {
		$forward_url = get_provider_url($provider, null, $referrer, $share_url);
		$forward_url = \elgg_trigger_plugin_hook('share', 'url', array(
			'provider' => $provider,
			'referrer' => $referrer,
			'share_url' => $share_url,
				), $forward_url);
	}

	return [$forward_url, $error];
});

if (!$forward_url || $error) {
	\elgg_register_error_message(\elgg_echo('discovery:share:error:no_url'));
	forward(REFERER);
}

forward($forward_url);

