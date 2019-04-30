<?php


namespace CortexPE\Hierarchy\member;


use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\Server;

class OfflineMember extends BaseMember {
	/** @var string */
	protected $username;

	public function __construct(string $username) {
		$this->username = $username;
	}

	public function getPlayer(): ?Player {
		return Server::getInstance()->getPlayerExact($this->username);
	}

	public function getName(): string {
		return $this->username;
	}

	public function getAttachment():?PermissionAttachment {
		return null;
	}
}