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


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\exception\RoleCollissionError;
use CortexPE\Hierarchy\Hierarchy;
use RuntimeException;
use function usort;

class RoleManager {
	/** @var Hierarchy */
	protected $plugin;
	/** @var Role[] */
	protected $roles = [];
	/** @var Role */
	protected $defaultRole = null;
	/** @var DataSource */
	protected $dataSource;
	/** @var int */
	protected $lastID = PHP_INT_MIN;

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
		$this->dataSource = $plugin->getDataSource();
	}

	/**
	 * @param array $roles
	 *
	 * @throws RoleCollissionError
	 * @internal Used to load role data from a data source
	 *
	 */
	public function loadRoles(array $roles) {
		foreach($roles as $roleData) {
			$role = new Role($this->plugin, $roleData["ID"], $roleData["Name"], [
				"permissions" => $roleData["Permissions"] ?? [], // permissions can be empty
				"position" => $roleData["Position"],
				"isDefault" => $roleData["isDefault"]
			]);
			foreach($this->roles as $i_role) {
				if($i_role->getPosition() == $roleData["Position"]) {
					throw new RoleCollissionError("Role '{$i_role->getName()}'({$i_role->getId()}) has a colliding Position");
				}
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
				if($this->defaultRole === null) {
					$this->defaultRole = $role;
				} else {
					throw new RoleCollissionError("There can only be one default role");
				}
			}
		}
		if(!($this->defaultRole instanceof Role)) {
			throw new RuntimeException("No default role is set");
		}
		$this->sortRoles();
		$this->plugin->getLogger()->info("Loaded " . count($this->roles) . " roles");
	}

	public function getRole(int $id): ?Role {
		return $this->roles[$id] ?? null;
	}

	/**
	 * @return Role
	 */
	public function getDefaultRole(): Role {
		return $this->defaultRole;
	}

	/**
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
		$this->dataSource->createRoleOnStorage($name, $this->lastID += 1, $newRolePos);
		foreach($this->roles as $role) {
			if($role->getPosition() > $defRolePos) {
				$role->bumpPosition();
				$this->dataSource->bumpPosition($role);
			}
		}
		$role = $this->roles[$this->lastID] = new Role($this->plugin, $this->lastID, $name, [
			"position" => $newRolePos
		]);
		$this->sortRoles();

		return $role;
	}

	private function sortRoles(): void {
		usort($this->roles, function (Role $a, Role $b) {
			return $a->getPosition() > $b->getPosition();
		});
	}

	public function deleteRole(Role $role): void {
		if($role->isDefault()) {
			throw new RuntimeException("Default role cannot be deleted while at runtime");
		}
		$members = $role->getMembers();
		foreach($members as $member) {
			$member->removeRole($role);
		}
		unset($this->roles[$role->getId()]);
		$this->dataSource->deleteRoleFromStorage($role);
	}
}