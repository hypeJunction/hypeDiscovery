<?php

namespace hypeJunction\Discovery;

class Analytics {

	/**
	 * Store temp user hash
	 *
	 * @param string $event		Equals 'login'
	 * @param string $type		Equals 'user'
	 * @param ElggUser $user
	 */
	public static function saveTempUserHash($event, $type = null, $user = null) {
		if ($event instanceof \Elgg\Event) {
			$type = $event->getType();
			$user = $event->getObject();
		}

		if (isset($_SESSION['discovery_hash'])) {
			$user->discovery_temp_hash = $_SESSION['discovery_hash'];
			unset($_SESSION['discovery_hash']);
		}

		return true;
	}

}
