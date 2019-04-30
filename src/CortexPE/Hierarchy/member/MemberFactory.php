<?php

/***
 *        __  ___                           __
 *       / / / (_)__  _________ ___________/ /_  __  __
 *      / /_/ / / _ \/ ___/ __ `/ ___/ ___/ __ \/ / / /
 *     / __  / /  __/ /  / /_/ / /  / /__/ / / / /_/ /
 *    /_/ /_/_/\___/_/   \__,_/_/   \___/_/ /_/\__, /
 *                                            /____/
 *
 * Hierarchy - Role-based permission management system
 * Copyright (C) 2019-Present CortexPE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

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