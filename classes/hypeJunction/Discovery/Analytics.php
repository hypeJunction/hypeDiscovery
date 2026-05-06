<?php

namespace hypeJunction\Discovery;

/**
 * Analytics class.
 */
class Analytics {

	/**
	 * Store temp user hash
	 *
	 * @param string|\Elgg\Event $event Event name 'login' or an Elgg\Event instance
	 * @param string|null        $type  Event type, e.g. 'user' (legacy positional callers)
	 * @param \ElggUser|null     $user  User entity (legacy positional callers)
	 * @return bool
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
