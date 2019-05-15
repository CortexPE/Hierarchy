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

use CortexPE\Hierarchy\exception\UnresolvedRoleException;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use function array_map;
use function array_values;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function mkdir;

abstract class IndexedDataSource extends DataSource {
	/** @var string */
	protected const FILE_EXTENSION = null;

	/** @var array */
	protected $roles = [];

	/** @var string */
	protected $rolesFile;
	/** @var string */
	protected $membersDir;

	public function initialize(): void {
		// just to make it work like the SQL ones... and, we need this to be able to get the default perms correctly
		if(file_exists(($this->rolesFile = $this->plugin->getDataFolder() . "roles." . static::FILE_EXTENSION))) {
			$this->roles = $this->decode(file_get_contents($this->rolesFile));
		} else {
			// create default role & add default permissions
			$pMgr = PermissionManager::getInstance();
			file_put_contents($this->rolesFile, $this->encode(($this->roles = [
				[
					"ID" => 1,
					"Position" => 0,
					"Name" => "Member",
					"isDefault" => true,
					"Permissions" => array_map(function (Permission $perm) {
						return $perm->getName();
					}, array_values($pMgr->getDefaultPermissions(false)))
				],
				[
					"ID" => 2,
					"Position" => 1,
					"Name" => "Operator",
					"isDefault" => false,
					"Permissions" => array_map(function (Permission $perm) {
						return $perm->getName();
					}, array_values($pMgr->getDefaultPermissions(true)))
				],
			])));
		}
		@mkdir(($this->membersDir = $this->plugin->getDataFolder() . "members/"));

		$this->postInitialize($this->roles);
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
			case self::ACTION_MEMBER_ROLE_ADD:
				$existingData["roles"][] = (int)$data;
				break;
			case self::ACTION_MEMBER_ROLE_REMOVE:
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

	public function addRolePermission(Role $role, Permission $permission, bool $inverted = false): void {
		$this->removeRolePermission($role, $permission);
		$permission = ($inverted ? "-" : "") . $permission->getName();
		$k = $this->resolveRoleIndex($role->getId());
		if(!in_array($permission, $this->roles[$k]["Permissions"])) {
			$this->roles[$k]["Permissions"][] = $permission;
		}
		$this->reIndex($this->roles[$k]["Permissions"]);
	}

	public function removeRolePermission(Role $role, $permission): void {
		if($permission instanceof Permission) {
			$permission = $permission->getName();
		}
		// todo: find a better way to do this
		$k = $this->resolveRoleIndex($role->getId());
		if(
			in_array($permission, ($a = $this->roles[$k]["Permissions"])) ||
			in_array("-" . $permission, $a)
		) {
			unset(
				$this->roles[$k]["Permissions"][array_search($permission, $a)],
				$this->roles[$k]["Permissions"][array_search("-" . $permission, $a)]
			);
		}
		$this->reIndex($this->roles[$k]["Permissions"]);
	}

	private function reIndex(array &$array): void {
		$array = array_values($array);
	}

	public function createRoleOnStorage(string $name, int $id, int $position): void {
		$this->roles[] = [ // we dont need to find index, it's a new role.
			"ID" => $id,
			"Position" => $position,
			"Name" => $name,
			"isDefault" => false,
			"Permissions" => []
		];
	}

	public function deleteRoleFromStorage(Role $role): void {
		unset($this->roles[$this->resolveRoleIndex($role->getId())]);
	}

	/**
	 * @param int $roleID
	 *
	 * @return int
	 * @throws UnresolvedRoleException
	 */
	private function resolveRoleIndex(int $roleID): int {
		foreach($this->roles as $i => $role) {
			if($role["ID"] == $roleID) {
				return $i;
			}
		}
		throw new UnresolvedRoleException("Unable to resolve unknown role with ID {$roleID}");
	}

	public function bumpPosition(Role $role): void {
		$this->roles[$this->resolveRoleIndex($role->getId())]["Position"] += 1;
	}

	public function shutdown(): void {
		$this->flush();
	}

	public function flush(): void {
		file_put_contents($this->rolesFile, $this->encode($this->roles));
	}
}