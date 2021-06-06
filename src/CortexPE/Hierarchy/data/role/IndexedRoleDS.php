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

namespace CortexPE\Hierarchy\data\role;


use CortexPE\Hierarchy\data\traits\IndexedDataUtilities;
use CortexPE\Hierarchy\exception\UnresolvedRoleException;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use function file_exists;
use function file_get_contents;
use function file_put_contents;

abstract class IndexedRoleDS extends RoleDataSource {
	use IndexedDataUtilities;

	/** @var string */
	protected const FILE_EXTENSION = null;

	/** @var array */
	protected $roles = [];

	/** @var string */
	protected $rolesFile;

	public function __construct(Hierarchy $plugin) {
		parent::__construct($plugin);
		$this->rolesFile = $this->plugin->getDataFolder() . "roles." . static::FILE_EXTENSION;
	}

	public function initialize(): void {
		if(file_exists($this->rolesFile)) {
			$this->roles = $this->decode(file_get_contents($this->rolesFile));
		} else {
			// create default role & add default permissions
			$pMgr = PermissionManager::getInstance();
			file_put_contents($this->rolesFile, $this->encode(($this->roles = [
				[
					"ID" => 1,
					"Name" => "Member",
					"isDefault" => true,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_USER)->getChildren())
				],
				[
					"ID" => 2,
					"Name" => "Operator",
					"isDefault" => false,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_OPERATOR)->getChildren())
				],
			])));
		}

		$this->postInitialize($this->roles);
	}

	abstract function decode(string $string): array;

	abstract function encode(array $data): string;

	public function addRolePermission(Role $role, Permission $permission, bool $inverted = false): void {
		$this->removeRolePermission($role, $permission);
		$permission = ($inverted ? "-" : "") . $permission->getName();
		$k = $this->resolveRoleIndex($role->getId());
		if(!self::permissionInArray($permission, $this->roles[$k]["Permissions"])) {
			$this->roles[$k]["Permissions"][] = $permission;
		}
		$this->reIndex($this->roles[$k]["Permissions"]);
		$this->flush();
	}

	public function removeRolePermission(Role $role, $permission): void {
		if($permission instanceof Permission) {
			$permission = $permission->getName();
		}
		$k = $this->resolveRoleIndex($role->getId());
		self::removePermissionFromArray($permission, $this->roles[$k]["Permissions"]);
		$this->reIndex($this->roles[$k]["Permissions"]);
		$this->flush();
	}

	public function createRoleOnStorage(string $name, int $id, int $position): void {
		array_splice($this->roles, $position, 0, [[
			"ID" => $id,
			"Name" => $name,
			"isDefault" => false,
			"Permissions" => []
		]]);
		$this->flush();
	}

	public function deleteRoleFromStorage(Role $role): void {
		unset($this->roles[$this->resolveRoleIndex($role->getId())]);
		$this->flush();
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

	public function shutdown(): void {
		// should be saved already
	}

	/**
	 * @param array $roles
	 * @internal Only used for writing data directly
	 */
	public function setRoles(array $roles): void {
		$this->roles = $roles;
	}

	public function flush(): void {
		ksort($this->roles);
		file_put_contents($this->rolesFile, $this->encode($this->roles));
	}
}