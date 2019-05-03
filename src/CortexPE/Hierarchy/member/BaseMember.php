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


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\event\MemberRoleAddEvent;
use CortexPE\Hierarchy\event\MemberRoleRemoveEvent;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;

abstract class BaseMember {
	/** @var bool[] */
	protected $permissions = [];
	/** @var Role[] */
	protected $roles = [];

	/**
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	public function loadData(array $memberData): void {
		foreach($memberData["roles"] ?? [] as $roleId) {
			$this->addRoleById($roleId, false);
		}
		$this->recalculatePermissions();
	}

	public function addRoleById(int $roleId, bool $recalculate = true): void {
		$role = Hierarchy::getRoleManager()->getRole($roleId);
		$this->addRole($role, $recalculate);
	}

	public function addRole(Role $role, bool $recalculate = true): void {
		if(!$this->hasRole($role)) {
			$ev = new MemberRoleAddEvent($this, $role);
			$ev->call();
			if(!$ev->isCancelled()) {
				Hierarchy::getInstance()
						 ->getDataSource()
						 ->updateMemberData($this, DataSource::ACTION_ROLE_ADD, $role->getId());
				$this->roles[$role->getId()] = $role;
				$role->bind($this);
				if($recalculate) {
					$this->recalculatePermissions();
				}
			}

			return;
		}
	}

	public function hasRole(Role $role): bool {
		return isset($this->roles[$role->getId()]);
	}

	public function removeRole(Role $role, bool $recalculate = true): void {
		if($this->hasRole($role)) {
			$ev = new MemberRoleRemoveEvent($this, $role);
			$ev->call();
			if(!$ev->isCancelled()) {
				unset($this->roles[$role->getId()]);
				Hierarchy::getInstance()
						 ->getDataSource()
						 ->updateMemberData($this, DataSource::ACTION_ROLE_REMOVE, $role->getId());
				$role->unbind($this);
				if($recalculate) {
					$this->recalculatePermissions();
				}
			}
		}
	}

	public function clearRoles(bool $recalculate = true): void {
		foreach($this->roles as $role) {
			$this->removeRole($role, false);
		}
		$this->roles = [];
		if($recalculate) {
			$this->recalculatePermissions();
		}
	}

	public function recalculatePermissions(): void {
		$this->permissions = [];
		$perms = []; // default
		foreach($this->roles as $role) {
			$perms[$role->getPosition()] = $role->getPermissions();
		}
		krsort($perms);
		$this->permissions = array_replace_recursive(...$perms);
	}

	public function getTopRole(): Role {
		return $this->roles[max(array_keys($this->roles))];
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions(): array {
		return $this->permissions;
	}

	/**
	 * @param Permission|string $permissionNode
	 *
	 * @return Role|null
	 */
	public function getTopRoleWithPermission($permissionNode): ?Role {
		if($permissionNode instanceof Permission) {
			$permissionNode = $permissionNode->getName();
		}
		$topRolePosition = PHP_INT_MIN;
		$topRoleWithPerm = null;
		foreach($this->roles as $role) {
			if(
				isset(($role->getPermissions())[$permissionNode]) &&
				$role->getPosition() > $topRolePosition
			) {
				$topRolePosition = $role->getPosition();
				$topRoleWithPerm = $role;
			}
		}

		return $topRoleWithPerm;
	}

	/**
	 * @param Permission|string $permissionNode
	 * @param BaseMember        $target
	 *
	 * @return bool
	 */
	public function hasHigherPermissionHierarchy($permissionNode, BaseMember $target): bool {
		if($permissionNode instanceof Permission) {
			$permissionNode = $permissionNode->getName();
		}
		$myTopRole = $this->getTopRoleWithPermission($permissionNode);
		if($myTopRole instanceof Role) {
			$targetTopRole = $target->getTopRoleWithPermission($permissionNode);
			if($targetTopRole instanceof Role) {
				return $myTopRole->getPosition() > $targetTopRole->getPosition();
			}

			return true;
		}

		return false;
	}

	abstract public function getAttachment(): ?PermissionAttachment;

	abstract public function getPlayer(): ?Player;

	abstract public function getName(): string;
}