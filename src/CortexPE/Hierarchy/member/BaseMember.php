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
use CortexPE\Hierarchy\data\member\MemberDataSource;
use CortexPE\Hierarchy\event\MemberRoleAddEvent;
use CortexPE\Hierarchy\event\MemberRoleRemoveEvent;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\Server;
use function in_array;
use function substr;

abstract class BaseMember {
	/** @var Hierarchy */
	protected $plugin;
	/** @var MemberDataSource */
	protected $dataSource;
	/** @var PermissionManager */
	protected $permMgr;
	/** @var bool[] */
	protected $permissions = [], $memberPermissions = []; // (string) PermissionNode => (bool) ALLOW/DISALLOW
	/** @var Role[] */
	protected $roles = [];
	/** @var Server */
	protected $server;

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
		$this->dataSource = $plugin->getMemberDataSource();
		$this->permMgr = PermissionManager::getInstance();
		$this->server = $plugin->getServer();
	}

	/**
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	public function loadData(array $memberData): void {
		foreach($memberData["roles"] ?? [] as $roleId) {
			$role = $this->plugin->getRoleManager()->getRole($roleId);
			if($role instanceof Role) {
				$this->roles[$roleId] = $role;
			} else {
				// un-existent role
				$this->plugin->getLogger()->debug("Ignoring non-existent role ID $roleId from " . $this->getName());
			}
		}
		foreach($memberData["permissions"] ?? [] as $perm) {
			$inverted = false;
			if($perm{0} === "-") {
				$inverted = true;
				$perm = substr($perm, 1);
			}
			$permission = $this->permMgr->getPermission($perm);
			if($permission instanceof Permission) {
				$this->memberPermissions[$permission->getName()] = !$inverted;
			} // ignore missing permissions
		}
		$this->recalculatePermissions();
	}

	public function addMemberPermission(Permission $permission, bool $recalculate = true, bool $save = true): void {
		$permission = $permission->getName();
		$this->memberPermissions[$permission] = true;
		if($recalculate) {
			$this->recalculatePermissions();
		}
		if($save) {
			$this->dataSource->updateMemberData($this, DataSource::ACTION_MEMBER_PERMS_ADD, $permission);
		}
	}

	public function denyMemberPermission(Permission $permission, bool $recalculate = true, bool $save = true): void {
		$permission = $permission->getName();
		$this->memberPermissions[$permission] = false;
		if($recalculate) {
			$this->recalculatePermissions();
		}
		if($save) {
			$this->dataSource->updateMemberData($this, DataSource::ACTION_MEMBER_PERMS_ADD, "-" . $permission);
		}
	}

	/**
	 * @param Permission|string $permission
	 * @param bool              $recalculate
	 */
	public function removeMemberPermission($permission, bool $recalculate = true): void {
		if($permission instanceof Permission) {
			$permission = $permission->getName();
		}
		unset($this->memberPermissions[$permission]);
		if($recalculate) {
			$this->recalculatePermissions();
		}
		$this->dataSource->updateMemberData($this, DataSource::ACTION_MEMBER_PERMS_REMOVE, $permission);
	}

	public function addRole(Role $role, bool $recalculate = true, bool $save = true): void {
		if(!$this->hasRole($role)) {
			$ev = new MemberRoleAddEvent($this, $role);
			$ev->call();
			$this->roles[$role->getId()] = $role;
			$role->bind($this);
			if($save) {
				$this->dataSource->updateMemberData($this, DataSource::ACTION_MEMBER_ROLE_ADD, $role->getId());
			}

			if($recalculate) {
				$this->recalculatePermissions();
			}
		}
	}

	public function hasRole(Role $role): bool {
		return in_array($role, $this->roles, true);
	}

	public function recalculatePermissions(): void {
		$this->permissions = [];
		$perms = []; // default
		foreach($this->roles as $role) {
			$perms[$role->getPosition()] = $role->getPermissions();
		}
		$perms[PHP_INT_MAX] = $this->memberPermissions; // this overrides other permissions
		krsort($perms);
		$this->permissions = array_replace_recursive(...$perms);
	}

	public function clearRoles(bool $recalculate = true, bool $save = true): void {
		foreach($this->roles as $role) {
			$this->removeRole($role, false, $save);
		}
		$this->roles = [];
		if($recalculate) {
			$this->recalculatePermissions();
		}
	}

	public function removeRole(Role $role, bool $recalculate = true, bool $save = true): void {
		if($this->hasRole($role)) {
			$ev = new MemberRoleRemoveEvent($this, $role);
			$ev->call();
			unset($this->roles[$role->getId()]);
			$role->unbind($this);
			if($save) {
				$this->dataSource->updateMemberData($this, DataSource::ACTION_MEMBER_ROLE_REMOVE, $role->getId());
			}
			if($recalculate) {
				$this->recalculatePermissions();
			}
		}
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
	 * @return bool[]
	 */
	public function getMemberPermissions(): array {
		return $this->memberPermissions;
	}

	abstract public function getPlayer(): ?Player;

	abstract public function getName(): string;
}