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


use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\BaseMember;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

class Role {
	public const PERM_TYPE_ADD = 0;
	public const PERM_TYPE_REMOVE = 1;

	/** @var int */
	protected $id;
	/** @var string */
	protected $name;

	/** @var int */
	protected $position;
	/** @var bool[] */
	protected $permissions = [];
	/** @var bool */
	protected $isDefault = false;

	/** @var BaseMember[] */
	protected $members = [];

	public function __construct(Hierarchy $plugin, int $id, string $name, array $roleData) {
		$this->id = $id;
		$this->name = $name;
		$this->position = $roleData["position"];
		$this->isDefault = (bool)$roleData["isDefault"];

		$pMgr = PermissionManager::getInstance();
		foreach($roleData["permissions"] ?? [] as $permission) {
			if($permission == "*") {
				foreach($pMgr->getPermissions() as $perm) {
					$this->permissions[$perm->getName()] = true;
				}
				continue;
			}

			$invert = ($permission{0} == "-");
			$perm = $pMgr->getPermission(!$invert ? $permission : substr($permission, 1));
			if($perm instanceof Permission) {
				$this->permissions[$perm->getName()] = !$invert;
			} else {
				$plugin->getLogger()->warning("Unknown permission node '" . $permission . "' on " . $name . " role");
				//throw new UnknownPermissionNode("Unknown permission node '" . $permission . "' on " . $name . " role");
			}
		}
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return $this->position;
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions(): array {
		return $this->permissions;
	}

	public function bind(BaseMember $member): void {
		$this->members[$member->getName()] = $member;
	}

	public function unbind(BaseMember $member): void {
		unset($this->members[$member->getName()]);
	}

	/**
	 * @return BaseMember[]
	 */
	public function getMembers(): array {
		return $this->members;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool {
		return $this->isDefault;
	}
}