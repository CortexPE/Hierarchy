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

namespace CortexPE\Hierarchy\role;


use CortexPE\Hierarchy\data\role\RoleDataSource;
use CortexPE\Hierarchy\exception\HierarchyException;
use CortexPE\Hierarchy\exception\RoleCollissionError;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\OfflineMember;
use RuntimeException;
use function uasort;

class RoleManager {
	/** @var Hierarchy */
	protected $plugin;
	/** @var Role[] */
	protected $roles = [];
	/** @var Role */
	protected $defaultRole = null;
	/** @var RoleDataSource */
	protected $dataSource;
	/** @var int */
	protected $lastID = PHP_INT_MIN;
	/** @var array */
	protected $lookupTable = []; // NAME => ID

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
		$this->dataSource = $plugin->getRoleDataSource();
	}

	/**
	 * @internal Used to load role data from a data source
	 *
	 * @param array $roles
	 *
	 * @throws HierarchyException
	 */
	public function loadRoles(array $roles): void {
		foreach($roles as $i => $roleData) {
			$role = new Role($this->plugin, $roleData["ID"], $roleData["Name"], [
				"position" => $i,
				"isDefault" => $roleData["isDefault"]
			]);
			foreach($roleData["Inherits"] ?? [] as $inherit){
				if(!isset($this->lookupTable[$inherit])){
					$this->plugin->getLogger()->warning("Cannot inherit role '$inherit' from '{$roleData['Name']}' ({$roleData['ID']}). Roles can only inherit permissions from other roles with lower positions.");
					continue;
				}
				$role->inheritRole($this->getRoleByName($inherit));
			}
			$role->loadPermissions($roleData["Permissions"] ?? []);

			if($roleData["ID"] < 0) {
				throw new HierarchyException("Role '{$role->getName()}'({$role->getId()}) has a negative ID");
			}
			if(!isset($this->roles[$roleData["ID"]])) {
				$this->roles[$roleData["ID"]] = $role;
			} else {
				throw new RoleCollissionError("Role '{$role->getName()}'({$role->getId()}) has a colliding ID");
			}
			if($roleData["ID"] > $this->lastID) {
				$this->lastID = $roleData["ID"];
			}
			if($roleData["isDefault"]) {
				if($i !== 0){
					throw new HierarchyException("Default role must always be the first role (lowest position).");
				}
				if($this->defaultRole === null) {
					$this->defaultRole = $role;
				} else {
					throw new RoleCollissionError("There can only be one default role");
				}
			}
			$this->addToLookupTable($role);
		}
		if(!($this->defaultRole instanceof Role)) {
			throw new RuntimeException("No default role is set");
		}
		$this->sortRoles();
		$this->plugin->getLogger()->info("Loaded " . count($this->roles) . " roles");
	}

	/**
	 * Gets a role by its ID
	 *
	 * @param int $id
	 * @return Role|null
	 */
	public function getRole(int $id): ?Role {
		return $this->roles[$id] ?? null;
	}

	/**
	 * Adds a role to the lookup dictionary where Role Name => ID
	 *
	 * @param Role $role
	 */
	private function addToLookupTable(Role $role): void {
		$name = $role->getName();
		$id = $role->getId();
		if(isset($this->lookupTable[$name])) {
			$_id = $this->lookupTable[$name];
			unset($this->lookupTable[$name]);
			$this->lookupTable["{$name}.{$_id}"] = $_id;
			$this->lookupTable["{$name}.{$id}"] = $role->getId();
		} else {
			$this->lookupTable[$name] = $id;
		}
		$this->lookupTable[$id] = $id;
	}

	/**
	 * Removes a role from the lookup dictionary where Role Name => ID
	 *
	 * @param Role $role
	 */
	private function removeFromLookupTable(Role $role): void {
		$name = $role->getName();
		$id = $role->getId();
		if(isset($this->lookupTable["{$name}.{$id}"]) && !isset($this->lookupTable[$name])){
			unset($this->lookupTable["{$name}.{$id}"]);
			$this->lookupTable[$name] = $id;
		} else {
			unset($this->lookupTable[$name]);
		}
		unset($this->lookupTable[$id]);
	}

	/**
	 * Tries to resolve a role by its name.
	 *
	 * WARNING: This uses the `roleName.ID` (MyRole.4) format for colliding role names
	 *
	 * @param string $roleName
	 *
	 * @return Role|null
	 */
	public function getRoleByName(string $roleName): ?Role {
		return $this->getRole($this->lookupTable[$roleName] ?? -1);
	}

	/**
	 * @return Role
	 */
	public function getDefaultRole(): Role {
		return $this->defaultRole;
	}

	/**
	 * Gets all roles in the server.
	 *
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	/**
	 * Creates a new role
	 *
	 * @param string $name
	 *
	 * @return Role
	 */
	public function createRole(string $name = "new role"): Role {
		$newRolePos = ($defRolePos = $this->defaultRole->getPosition()) + 1;
		foreach($this->roles as $role) {
			if($role->getPosition() > $defRolePos) {
				$role->bumpPosition();
			}
		}
		$this->dataSource->createRoleOnStorage($name, $this->lastID += 1, $newRolePos);
		$role = $this->roles[$this->lastID] = new Role($this->plugin, $this->lastID, $name, [
			"position" => $newRolePos
		]);
		$this->sortRoles();
		$this->addToLookupTable($role);

		return $role;
	}

	/**
	 * Sorts roles by position (ascending)
	 */
	private function sortRoles(): void {
		uasort($this->roles, function (Role $a, Role $b): int {
			return $a->getPosition() <=> $b->getPosition();
		});
	}

	/**
	 * Deletes a role, also deletes from the database.
	 *
	 * @param Role $role
	 * @throws HierarchyException
	 */
	public function deleteRole(Role $role): void {
		if($role->isDefault()) {
			throw new RuntimeException("Default role cannot be deleted while at runtime");
		}
		foreach($role->getChildren() as $child){
			$child->unInheritRole($role);
		}
		$members = $role->getOnlineMembers();
		foreach($members as $member) {
			$member->removeRole($role);
		}
		$role->getOfflineMembers(function(array $members) use ($role): void{
			/** @var OfflineMember $member */
			foreach($members as $member){
				$member->removeRole($role, false);
			}
			unset($this->roles[$role->getId()]);
			$this->sortRoles();
			$this->removeFromLookupTable($role);
			$this->dataSource->deleteRoleFromStorage($role);
		});
	}
}