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
use pocketmine\Server;

class MemberFactory {
	/** @var Loader */
	protected $plugin;
	/** @var Member[] */
	protected $onlineMembers = [];
	/** @var OfflineMember[] */
	protected $offlineMembers = [];

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	public function createSession(Player $player): void{
		$this->getMember($player); // just call this function, does the same thing
	}

	public function destroySession(Player $player): void{
		unset($this->onlineMembers[$player->getId()]);
	}

	/**
	 * @param Player|string $player
	 * @param bool $loadData
	 * @param callable|null $onLoad
	 *
	 * @return OfflineMember|Member
	 */
	public function getMember($player, bool $loadData = true, ?callable $onLoad = null){
		$newMember = false;
		if(!($player instanceof Player)){
			if(($p = Server::getInstance()->getPlayerExact($player)) instanceof Player){
				$player = $p;
			}
		}
		if($player instanceof Player){
			if(!isset($this->onlineMembers[($n = $player->getId())])){
				$this->onlineMembers[$n] = new Member($player);
				$newMember = true;
			}
			$m = $this->onlineMembers[$n];
		} else {
			if(!isset($this->offlineMembers[$player])) {
				$this->offlineMembers[$player] = new OfflineMember($player);
				$newMember = true;
			}
			$m = $this->offlineMembers[$player];
		}
		if($loadData && $newMember){
			$this->plugin->getDataSource()->loadMemberData($m, function()use($m, $onLoad){
				if($onLoad !== null){
					($onLoad)($m);
				}
			});
		}else{
			if($onLoad !== null){
				($onLoad)($m);
			}
		}
		return $m;
	}

	public function shutdown():void{
		foreach($this->onlineMembers as $member){
			$this->destroySession($member->getPlayer());
		}
	}
}