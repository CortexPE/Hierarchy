<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 6:44 AM
 */

namespace CortexPE\Hierarchy\member;


use CortexPE\Hierarchy\Loader;
use pocketmine\Player;

class MemberFactory {
	/** @var Loader */
	protected $plugin;
	/** @var Member[] */
	protected $members = [];

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	public function createSession(Player $player): void{
		$m = $this->members[($n = $player->getId())] = new Member($player);
		$this->plugin->getDataSource()->loadMemberData($m);
	}

	public function destroySession(Player $player): void{
		unset($this->members[$player->getId()]);
	}

	public function getMember(Player $player): ?Member{
		return $this->members[$player->getId()] ?? null;
	}

	// TODO: be able to get offline Member data

	public function shutdown():void{
		foreach($this->members as $member){
			$this->destroySession($member->getPlayer());
		}
	}
}