<?php
/**
 *  WikiTide Foundation
 *  Notiheze
 *  Main Notification Class
 *
 * @package   Notiheze
 * @author    Original Authority
 * @copyright (c) 2024 WikiTide Foundation.
 * @license   GPL-3.0-or-later
 * @link     https://github.com/miraheze/notiheze
 */

namespace Miraheze\Notiheze\Notification;

use MediaWiki\User\UserFactory;
use Message;

class Notification {

	// Let's come back to this when we've figured out what we're doing
	public function __construct( private UserFactory $userFactory, private ?string $agentId=  null ) {}

	/**
	 * Get the ID of the notification
	 * @return int
	 */
	public function getID(): int {
		return $this->id;
	}

	/**
	 * Get the type of notification
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Get the timestamp of when the notification was created
	 * When did the event occur, in other words?
	 * @return int
	 */
	public function getCreation(): int {
		return $this->creation;
	}

	/**
	 * Get the timestamp of when the notification was read
	 * or dismissed as Echo likes to call it
	 * @return int
	 */
	public function getRead(): int {
		return $this->read;
	}

	/**
	 * Get the canonical URL of the notification
	 * @return string
	 */
	public function getCanonicalUrl(): string {
		return $this->canonicalUrl;
	}

	/**
	 * Where did the notification originate from??
	 * @return int
	 */
	public function getOriginID(): int {
		return $this->originId;
	}

	/**
	 * The agent of the notification
	 * @return int
	 */
	public function getAgentID(): int {
		return $this->agentId;
	}

	/**
	 * Get the header for this specific notification
	 * Also known as the title
	 *
	 * @param bool $long use the long or short version of the header
	 * @return Message
	 */
	public function getHeader(bool $long = false): Message {
		$parameters = $this->getMessageParameters();

		// Ensure array keys start at 1
		$parameters = $this->normalizeArrayKeys($parameters);

		return wfMessage(($long ? 'long' : 'short') . '-header-' . $this->getType())->params($parameters);
	}

	/**
	 * Echo has an issue where some legacy keys missed parameters at the beginning of the string
	 * Let us smash that bug on the head.
	 *
	 * @param array $array
	 * @return array
	 */
	private function normalizeArrayKeys(array $array): array {
		if (!empty($array) && min(array_keys($array)) !== 1) {
			$max = max(array_keys($array));
			for ($i = 1; $i <= $max; $i++) {
				$array[$i] = $array[$i] ?? null;
			}
			ksort($array);
		}

		return $array;
	}

    /**
     * Get the agent of this notification, whoever sent the notification
     * @return string|null
     */
    public function getAgentUrl(): ?string {
        if ( $this->agentId === null ) {
            return null;
        }
        return $this->userFactory->newFromId( (int)$this->agentId )->getUserPage()->getFullURL();
    }





}
