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


use CortexPE\Hierarchy\data\member\SQLMemberDS;
use CortexPE\Hierarchy\Hierarchy;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use function is_string;

class MemberFactory {
	/** @var Hierarchy */
	protected $plugin;
	/** @var Member[] */
	protected $onlineMembers = [];

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
	}

	public function createSession(Player $player): void {
		$this->getMember($player); // just call this function, does the same thing
	}

	/**
	 * @param Player|OfflinePlayer|string $player
	 * @param bool          $loadData
	 * @param callable|null $onLoad
	 *
	 * @return BaseMember
	 */
	public function getMember($player, bool $loadData = true, ?callable $onLoad = null): BaseMember {
		if(is_string($player)) {
			$player = $this->plugin->getServer()->getOfflinePlayer((string)$player);
		}
		$newMember = false;
		if($player instanceof Player) {
			if(!isset($this->onlineMembers[($n = $player->getId())])) {
				$this->onlineMembers[$n] = new Member($this->plugin, $player);
				$newMember = true;
			}
			$m = $this->onlineMembers[$n];
		} else {
			$m = new OfflineMember($this->plugin, $player->getName());
			$newMember = true;
		}
		if($loadData && $newMember) {
			($ds = $this->plugin->getMemberDataSource())->loadMemberData($m, function () use ($m, $onLoad) {
				if($onLoad !== null) {
					($onLoad)($m);
				}
			});
			if($m instanceof OfflinePlayer && $ds instanceof SQLMemberDS){
				/**
				 * TODO:
				 *  Make this better...
				 *  the sole reason this hack exists is because of the typical usage of OfflineMember,
				 *  data has to be manipulated right away therefore it has to be available right away.
				 */
				$ds->getDB()->waitAll();
			}
		} elseif($onLoad !== null) {
			($onLoad)($m);
		}

		return $m;
	}

	public function shutdown(): void {
		foreach($this->onlineMembers as $member) {
			$this->destroySession($member->getPlayer());
		}
	}

	public function destroySession(Player $player): void {
		unset($this->onlineMembers[$player->getId()]);
	}
}