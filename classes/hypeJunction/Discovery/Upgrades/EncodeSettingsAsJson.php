<?php

namespace hypeJunction\Discovery\Upgrades;

use Elgg\Upgrade\Batch;
use Elgg\Upgrade\Result;

/**
 * Re-encodes legacy serialized plugin settings as JSON.
 *
 * The 3.x security fix in hypeDiscovery switched actions/hypediscovery/settings/save.php
 * to json_encode() and added a json_decode-with-serialize-fallback in lib/functions.php
 * (_decode_setting_array). This upgrade batch walks the three array settings, decodes
 * whichever format is stored, and re-saves as JSON so the runtime fallback can be
 * removed in a future release.
 */
class EncodeSettingsAsJson extends Batch {

	/** @var string[] Settings that store serialized/JSON arrays */
	private const ARRAY_SETTINGS = [
		'providers',
		'discovery_type_subtype_pairs',
		'embed_type_subtype_pairs',
	];

	/** {@inheritdoc} */
	public function getVersion(): int {
		return 2026041600;
	}

	/** {@inheritdoc} */
	public function shouldBeSkipped(): bool {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		if (!$plugin instanceof \ElggPlugin) {
			return true;
		}

		foreach (self::ARRAY_SETTINGS as $key) {
			$raw = $plugin->getSetting($key);
			if ($raw && json_decode($raw, true) === null) {
				// At least one setting is not valid JSON — upgrade is needed.
				return false;
			}
		}

		return true;
	}

	/** {@inheritdoc} */
	public function needsIncrementOffset(): bool {
		return false;
	}

	/** {@inheritdoc} */
	public function countItems(): int {
		return count(self::ARRAY_SETTINGS);
	}

	/** {@inheritdoc} */
	public function run(Result $result, $offset): Result {
		$plugin = elgg_get_plugin_from_id('hypediscovery');
		if (!$plugin instanceof \ElggPlugin) {
			$result->addError('hypediscovery plugin entity not found');
			$result->addFailures(count(self::ARRAY_SETTINGS));
			$result->markComplete();
			return $result;
		}

		foreach (self::ARRAY_SETTINGS as $key) {
			$raw = $plugin->getSetting($key);

			if (!$raw) {
				// Nothing stored — nothing to re-encode.
				$result->addSuccesses();
				continue;
			}

			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				// Already valid JSON.
				$result->addSuccesses();
				continue;
			}

			// Try unserialize (legacy format).
			$decoded = @unserialize($raw, ['allowed_classes' => false]);
			if (!is_array($decoded)) {
				$result->addError("hypediscovery: could not decode setting '{$key}' — skipping");
				$result->addFailures();
				continue;
			}

			if ($plugin->setSetting($key, json_encode($decoded))) {
				$result->addSuccesses();
			} else {
				$result->addError("hypediscovery: failed to save re-encoded setting '{$key}'");
				$result->addFailures();
			}
		}

		$result->markComplete();

		return $result;
	}
}
