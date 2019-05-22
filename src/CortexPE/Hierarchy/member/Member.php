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


use CortexPE\Hierarchy\event\MemberRoleUpdateEvent;
use CortexPE\Hierarchy\Hierarchy;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;

class Member extends BaseMember {
	/** @var Player */
	protected $player;
	/** @var PermissionAttachment */
	protected $attachment;

	public function __construct(Hierarchy $plugin, Player $player) {
		parent::__construct($plugin);
		$this->player = $player;
		$this->attachment = $player->addAttachment($plugin);
	}

	/**
	 * @return PermissionAttachment|null
	 */
	public function getAttachment(): ?PermissionAttachment {
		return $this->attachment;
	}

	public function recalculatePermissions(): void {
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

	public function loadData(array $memberData): void {
		parent::loadData($memberData);

		// broadcast that our data has loaded, and our roles has updated from being empty
		(new MemberRoleUpdateEvent($this))->call();
	}
}