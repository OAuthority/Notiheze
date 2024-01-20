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

use Config;
use MediaWiki\User\UserFactory;
use Message;
use WikiMap;
use WikiReference;

class Notification {

	/**
	 * Main constructor for this class, set up everything we need.
	 * @param UserFactory $userFactory
	 * @param Config $config
	 * @param string $type
	 * @param string $message
	 * @param string $canonicalUrl
	 * @param int $id
	 * @param int $creation
	 * @param int $read
	 * @param string|null $originId
	 * @param string|null $agentId
	 */
	public function __construct(
		private UserFactory $userFactory,
		private Config $config,
		private string $type,
		private string $message,
		private string $canonicalUrl,
		private int $id = 0,
		private int $creation = 0,
		private int $read = 0,
		private ?string $originId = null,
	 	private ?string $agentId= null,
	) {

	}

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

	/**
	 * Do any some cleanup on message parameters and return them
	 *
	 * @return array
	 */
	protected function getMessageParameters(): array {
		$json = json_decode( $this->message, true);

		$parameters = [];

		foreach ( $json as $parameter ) {
			$parameters[$parameter[0]] = $parameter[1];
		}

		ksort( $parameters, SORT_NATURAL );

		return $parameters;
	}

	/**
	 * Return the origin of the notification; an object of the wiki with information
	 * This should have information about the wiki such as its url, sitname, etc.
	 * Can we possibly just look up its json config file in /cache and decode it?
	 */
	public function getOrigin() {
		// check if the origin doesn't exist, and bail
		if ( !$this->originId || $this->originId = null ) {
			return null;
		}

		// Check the wiki map for the origin id we pass in, which should be a database name
		if ( WikiMap::getWiki( $this->originId ) !== null ) {
			// return the wiki object
			$wiki = WikiMap::getWiki( $this->originId );
		}

		// return the wiki object, which should be from $wgConf
		return $wiki;
	}

	/**
	 * Get the url of the origin
	 *
	 * @return string
	 */
	public function getOriginUrl() {
		$origin = $this->getOrigin();

		// get the canonical server url for this wiki from the wiki map
		$originUrl = $origin->getCanonicalServer();

		// return it as a string for further use
		return $originUrl;
	}

	/**
	 * Get the category of this notification
	 * is it a notification, alert, something else?
	 *
	 */
	public function getCategory(): string {
		// we don't yet have a notification service, but we will, leave this here for now?!
		return $this->notificationService->getCategoryFromType( $this->type );
	}

	/**
	 * Get the image for the notification icon and return its url
	 *
	 * @return string|null
	 */
	public function getNotificationIcon(): ?string {
		return $this->getIconConfig( 'notification' )[$this->type] ?? null;
	}

	/**
	 * Get icon configuration.
	 *
	 * @param string $type Icon Type, one of: 'notification', 'category', 'subcategory'
	 *
	 * @return array Array containing key of type name to the URL location for it.
	 */
	private function getIconConfig( string $type ): array {
		return $this->config->get( 'NotihezeIcons' )[$type];
	}

	/**
	 * Assign the importance of this notification, so that more important notifs can be shown firwst
	 *
	 * @return int
	 */
	public function getImportance(): int {
		return $this->config->get( 'Notiheze' )[ $this->type ][ 'importance' ] ?? 0;
	}

	/**
	 * Return an array object of this notification that we can use externally and in APIs.
	 *
	 * @return array
	 */
	public function notificationToArray(): array {
		return [
			'icons' => [
				'notification' => $this->getNotificationIcon()
			],
			'category' => $this->getCategory(),
			'id' => $this->id,
			'type' => $this->type,
			'header_short' => $this->getHeader(),
			'header_long' => $this->getHeader( true ),
			'created_at' => $this->creation,
			'read_at' => $this->read,
			'origin_url' => $this->getOriginUrl(),
			'agent_url' => $this->getAgentUrl(),
			'canonical_url' => $this->canonicalUrl,
			'importance' => $this->getImportance(),
		];
	}
}
