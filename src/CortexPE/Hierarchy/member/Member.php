<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 5:54 AM
 */

namespace CortexPE\Hierarchy\member;


use CortexPE\Hierarchy\Loader;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;

class Member extends BaseMember {
	/** @var Player */
	protected $player;
	/** @var PermissionAttachment */
	protected $attachment;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->attachment = $player->addAttachment(Loader::getInstance());
	}

	/**
	 * @return PermissionAttachment|null
	 */
	public function getAttachment(): ?PermissionAttachment {
		return $this->attachment;
	}

	public function recalculatePermissions(): void {
		$this->attachment->clearPermissions();
		parent::recalculatePermissions();
		$this->attachment->setPermissions($this->permissions);
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player {
		return $this->player;
	}

	public function getName(): string {
		return $this->player->getName();
	}
}