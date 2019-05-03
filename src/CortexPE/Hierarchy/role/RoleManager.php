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


use CortexPE\Hierarchy\exception\RoleCollissionError;
use CortexPE\Hierarchy\exception\UnknownPermissionNode;
use CortexPE\Hierarchy\Hierarchy;
use RuntimeException;

class RoleManager {
	/** @var Hierarchy */
	protected $plugin;
	/** @var Role[] */
	protected $roles = [];
	/** @var Role */
	protected $defaultRole = null;

	public function __construct(Hierarchy $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @internal Used to load role data from a data source
	 *
	 * @param array $roles
	 * @throws RoleCollissionError
	 * @throws UnknownPermissionNode
	 */
	public function loadRoles(array $roles) {
		foreach($roles as $roleData){
			$role = new Role($roleData["ID"], $roleData["Name"], [
				"permissions" => $roleData["Permissions"] ?? [], // permissions can be empty
				"position" => $roleData["Position"],
				"isDefault" => $roleData["isDefault"]
			]);
			if(!isset($this->roles[$roleData["ID"]])){
				$this->roles[$roleData["ID"]] = $role;
			} else {
				throw new RoleCollissionError("Role '{$role->getName()}'({$role->getId()}) has a colliding ID");
			}
			if($roleData["isDefault"]) {
				if($this->defaultRole === null) {
					$this->defaultRole = $role;
				} else {
					throw new RoleCollissionError("There can only be one default role");
				}
			}
		}
		if(!($this->defaultRole instanceof Role)){
			throw new RuntimeException("No default role is set");
		}
		$this->plugin->getLogger()->info("Loaded " . count($this->roles) . " roles");
	}

	public function getRole(int $id):?Role{
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
}