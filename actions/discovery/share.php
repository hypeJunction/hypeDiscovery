<?php

namespace hypeJunction\Discovery;

$provider = get_input('provider');
$guid = get_input('guid');
$referrer = get_input('referrer');
$share_url = get_input('share_url');

$result = elgg_call(ELGG_IGNORE_ACCESS, function () use ($provider, $guid, $referrer, $share_url) {
	$error = false;
	$forward_url = REFERRER;

	if ($guid) {
		$entity = get_entity($guid);
		if (!is_discoverable($entity)) {
			$error = true;
		} else {
			$forward_url = get_provider_url($provider, $entity, $referrer);
			$forward_url = elgg_trigger_event_results('entity:share', $entity->getType(), [
				'provider' => $provider,
				'entity' => $entity,
				'referrer' => $referrer,
			], $forward_url);
		}
	} else {
		$forward_url = get_provider_url($provider, null, $referrer, $share_url);
		$forward_url = elgg_trigger_event_results('share', 'url', [
			'provider' => $provider,
			'referrer' => $referrer,
			'share_url' => $share_url,
		], $forward_url);
	}

	return ['forward_url' => $forward_url, 'error' => $error];
});

$forward_url = $result['forward_url'];
$error = $result['error'];

if (!$forward_url || $error) {
	return elgg_error_response(elgg_echo('discovery:share:error:no_url'), REFERRER, ELGG_HTTP_NOT_FOUND);
}

return elgg_redirect_response($forward_url);

