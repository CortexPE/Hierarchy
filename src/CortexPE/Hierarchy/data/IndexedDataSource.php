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

namespace CortexPE\Hierarchy\data;

use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\BaseMember;
use pocketmine\permission\PermissionManager;
use pocketmine\scheduler\ClosureTask;

abstract class IndexedDataSource extends DataSource {
	/** @var string */
	protected const FILE_EXTENSION = null;

	/** @var array */
	protected $roles = [];

	/** @var string */
	protected $rolesFile;
	/** @var string */
	protected $membersDir;

	public function __construct(Hierarchy $plugin) {
		parent::__construct($plugin);

		// just to make it work like the SQL ones... and, we need this to be able to get the default perms correctly
		$plugin->getScheduler()->scheduleTask(new ClosureTask(function (int $_) use ($plugin): void {
			if(file_exists(($this->rolesFile = $plugin->getDataFolder() . "roles." . static::FILE_EXTENSION))) {
				$this->roles = $this->decode(file_get_contents($this->rolesFile));
			} else {
				// create default role & add default permissions
				$defaults = PermissionManager::getInstance()->getDefaultPermissions(false);
				$def = [];
				foreach($defaults as $permission) {
					$def[] = $permission->getName();
				}
				file_put_contents($this->rolesFile, $this->encode(($this->roles = [
					[
						"Position" => 0,
						"Name" => "Member",
						"isDefault" => true,
						"Permissions" => $def
					]
				])));
			}
			foreach($this->roles as $id => $role) {
				$this->roles[$id]["ID"] = $id + 1; // derive ID from the index, this way, an ID field is not needed
			}
			$this->getPlugin()->getRoleManager()->loadRoles($this->roles);

			@mkdir(($this->membersDir = $plugin->getDataFolder() . "members/"));
		}));
	}

	abstract function decode(string $string): array;

	abstract function encode(array $data): string;

	public function loadMemberData(BaseMember $member, ?callable $onLoad = null): void {
		$member->loadData($this->readMemberData($member));
		if($onLoad !== null) {
			$onLoad();
		}
	}

	protected function readMemberData(BaseMember $member): array {
		$data = [
			"roles" => [
				$this->plugin->getRoleManager()->getDefaultRole()->getId()
			]
		];
		if(file_exists(($fp = $this->getFileName($member)))) {
			$dat = $this->decode(file_get_contents($fp));
			if(isset($dat["roles"])) {
				$data["roles"] = array_merge($data["roles"], $dat["roles"]);
			}
		}

		return $data;
	}

	protected function getFileName(BaseMember $member): string {
		return $this->membersDir . strtolower($member->getName()) . "." . static::FILE_EXTENSION;
	}

	public function updateMemberData(BaseMember $member, string $action, $data): void {
		$existingData = $this->readMemberData($member);

		switch($action) {
			case self::ACTION_ROLE_ADD:
				$existingData["roles"][] = (int)$data;
				break;
			case self::ACTION_ROLE_REMOVE:
				$i = array_search(($data = (int)$data), $existingData["roles"]);
				if($i !== false) {
					unset($existingData["roles"][$i]);
				}
				break;
		}
		if(isset($existingData["roles"])) {
			$existingData["roles"] = array_values(array_unique($existingData["roles"]));
		}

		file_put_contents($this->getFileName($member), $this->encode($existingData));
	}

	public function shutdown(): void {
		file_put_contents($this->rolesFile, $this->encode($this->roles));
	}
}