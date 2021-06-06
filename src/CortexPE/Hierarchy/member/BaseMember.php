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


use CortexPE\Hierarchy\data\member\MemberDataSource;
use CortexPE\Hierarchy\event\MemberRoleAddEvent;
use CortexPE\Hierarchy\event\MemberRoleRemoveEvent;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
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
	/** @var array */
	protected $roleAdditionalData = [];
	/** @var array */
	protected $permissionAdditionalData = [];

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
		$this->dataSource = $plugin->getMemberDataSource();
		$this->permMgr = PermissionManager::getInstance();
		$this->server = $plugin->getServer();
	}

	/**
	 * Returns a list of all the Roles a member has
	 *
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	/**
	 * @internal
	 *
	 * @param array $memberData
	 */
	public function loadData(array $memberData): void {
		foreach($memberData["roles"] ?? [] as $roleId => $additionalData) {
			$role = $this->plugin->getRoleManager()->getRole($roleId);
			if($role instanceof Role) {
				if($additionalData !== null) {
					$this->roleAdditionalData[$roleId] = $additionalData;
				}
				$this->roles[$roleId] = $role;
			} else {
				// un-existent role
				$this->plugin->getLogger()->debug("Ignoring non-existent role ID $roleId from " . $this->getName());
			}
		}
		foreach($memberData["permissions"] ?? [] as $perm => $additionalData) {
			$inverted = false;
			if($perm[0] === "-") {
				$inverted = true;
				$perm = substr($perm, 1);
			}
			$permission = $this->permMgr->getPermission($perm);
			if($permission instanceof Permission) {
				if($additionalData !== null){
					$this->permissionAdditionalData[$permission->getName()] = $additionalData;
				}
				$this->memberPermissions[$permission->getName()] = !$inverted;
			} // ignore missing permissions
		}
		$this->recalculatePermissions();
	}

	/**
	 * Allow a member to use a permission
	 *
	 * @param Permission $permission
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function addMemberPermission(Permission $permission, bool $recalculate = true, bool $save = true): void {
		$permission = $permission->getName();
		$this->memberPermissions[$permission] = true;
		if($recalculate) {
			$this->recalculatePermissions();
		}
		if($save) {
			$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_PERMS_ADD, $permission);
		}
	}

	/**
	 * Deny a member from using a permission
	 *
	 * @param Permission $permission
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function denyMemberPermission(Permission $permission, bool $recalculate = true, bool $save = true): void {
		$permission = $permission->getName();
		$this->memberPermissions[$permission] = false;
		if($recalculate) {
			$this->recalculatePermissions();
		}
		if($save) {
			$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_PERMS_ADD, "-" . $permission);
		}
	}

	/**
	 * This function allows the removal of permissions based on their name because it may be a missing permission
	 * intentionally being removed... A consideration for multi-server scenarios.
	 *
	 * @param Permission|string $permission
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function removeMemberPermission(Permission|string $permission, bool $recalculate = true, bool $save = true): void {
		if($permission instanceof Permission) {
			$permission = $permission->getName();
		}
		unset($this->memberPermissions[$permission]);
		unset($this->permissionAdditionalData[$permission]);
		if($recalculate) {
			$this->recalculatePermissions();
		}
		if($save){
			$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_PERMS_REMOVE, $permission);
		}
	}

	/**
	 * Gives the member a role
	 *
	 * @param Role $role
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function addRole(Role $role, bool $recalculate = true, bool $save = true): void {
		if(!$this->hasRole($role)) {
			$this->roles[$role->getId()] = $role;
			$this->onRoleAdd($role);
			(new MemberRoleAddEvent($this, $role))->call();
			if($save) {
				$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_ROLE_ADD, $role->getId());
			}

			if($recalculate) {
				$this->recalculatePermissions();
			}
		}
	}

	/**
	 * Called after a role is added, before being saved into the database. Used for giving a reference to the member
	 * to a role on Online Members.
	 *
	 * @param Role $role
	 */
	abstract protected function onRoleAdd(Role $role): void;

	/**
	 * Allows to check if the member has a specific role.
	 *
	 * @param Role $role
	 * @return bool
	 */
	public function hasRole(Role $role): bool {
		return in_array($role, $this->roles, true);
	}

	/**
	 * Called to recalculate member permissions.
	 */
	public function recalculatePermissions(): void {
		$this->permissions = [];
		$perms = []; // default
		foreach($this->roles as $role) {
			$perms[$role->getPosition()] = $role->getCombinedPermissions();
		}
		$perms[PHP_INT_MAX] = $this->memberPermissions; // this overrides other permissions
		krsort($perms);
		$this->permissions = array_replace_recursive(...$perms);
	}

	/**
	 * Removes all roles that are loaded. (This excludes roles that are missing)
	 *
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function clearRoles(bool $recalculate = true, bool $save = true): void {
		foreach($this->roles as $role) {
			$this->removeRole($role, false, $save);
		}
		$this->roles = [];
		if($recalculate) {
			$this->recalculatePermissions();
		}
	}

	/**
	 * Removes a specific role
	 *
	 * @param Role $role
	 * @param bool $recalculate
	 * @param bool $save
	 */
	public function removeRole(Role $role, bool $recalculate = true, bool $save = true): void {
		if($this->hasRole($role)) {
			unset($this->roles[$role->getId()]);
			unset($this->roleAdditionalData[$role->getId()]);
			$this->onRoleRemove($role);
			(new MemberRoleRemoveEvent($this, $role))->call();
			if($save) {
				$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_ROLE_REMOVE, $role->getId());
			}
			if($recalculate) {
				$this->recalculatePermissions();
			}
		}
	}

	abstract protected function onRoleRemove(Role $role): void;

	public function getTopRole(): Role {
		$maxPos = $basePos = ($defaultRole = $this->plugin->getRoleManager()->getDefaultRole())->getPosition();
		$ri = 0;
		foreach($this->roles as $i => $role) {
			if($role->getPosition() > $maxPos) {
				$maxPos = $role->getPosition();
				$ri = $i;
			}
		}
		if($maxPos === $basePos) {
			return $defaultRole;
		}
		return $this->roles[$ri];
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions(): array {
		return $this->permissions;
	}

	/**
	 * @param Permission|string $permissionNode
	 * @param BaseMember $target
	 *
	 * @return bool
	 */
	public function hasHigherPermissionHierarchy(Permission|string $permissionNode, BaseMember $target): bool {
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
	public function getTopRoleWithPermission(Permission|string $permissionNode): ?Role {
		if($permissionNode instanceof Permission) {
			$permissionNode = $permissionNode->getName();
		}
		$topRolePosition = PHP_INT_MIN;
		$topRoleWithPerm = null;
		foreach($this->roles as $role) {
			if(
				isset(($role->getCombinedPermissions())[$permissionNode]) &&
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

	/**
	 * @internal
	 */
	abstract public function onDestroy(): void;

	/**
	 * @return array
	 */
	public function getRoleAdditionalData(): array {
		return $this->roleAdditionalData;
	}

	/**
	 * @return array
	 */
	public function getPermissionAdditionalData(): array {
		return $this->permissionAdditionalData;
	}

	/**
	 * @param Role $role
	 * @param array $data
	 */
	public function setRoleAdditionalData(Role $role, array $data):void {
		$this->roleAdditionalData[$role->getId()] = $data;
		$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_UPDATE_ROLE_ETC, [$role->getId(), $data]);
	}

	/**
	 * @param Permission $permission
	 * @param array $data
	 */
	public function setPermissionAdditionalData(Permission $permission, array $data):void {
		$this->permissionAdditionalData[$permission->getName()] = $data;
		$this->dataSource->updateMemberData($this, MemberDataSource::ACTION_MEMBER_UPDATE_PERMISSION_ETC, [$permission->getName(), $data]);
	}
}