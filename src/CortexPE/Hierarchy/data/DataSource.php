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
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;

/**
 * Class DataSource
 * @package  CortexPE\Hierarchy\data
 * @internal This class (and its subclasses) are only used for the plugin's internal data storage. DO NOT TOUCH!
 */
abstract class DataSource {
	public const ACTION_MEMBER_ROLE_ADD = "member.role.add";
	public const ACTION_MEMBER_ROLE_REMOVE = "member.role.remove";
	public const ACTION_MEMBER_PERMS_ADD = "member.perm.add";
	public const ACTION_MEMBER_PERMS_REMOVE = "member.perm.remove";

	/** @var Hierarchy */
	protected $plugin;

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
	}

	abstract public function initialize(): void;

	protected function postInitialize(array $roles): void {
		$this->plugin->getRoleManager()->loadRoles($roles);
		$this->plugin->continueStartup();
	}

	/**
	 * @return Hierarchy
	 */
	public function getPlugin(): Hierarchy {
		return $this->plugin;
	}

	/**
	 * @param BaseMember $member
	 * @param callable   $onLoad
	 *
	 * @internal Get member data from the data source then pass to member object
	 *
	 */
	abstract public function loadMemberData(BaseMember $member, ?callable $onLoad = null): void;

	/**
	 * @param BaseMember $member
	 * @param string     $action
	 * @param mixed      $data
	 *
	 * @internal Update member data on data source
	 *
	 */
	abstract public function updateMemberData(BaseMember $member, string $action, $data): void;

	/**
	 * @param Role       $role
	 * @param Permission $permission
	 * @param bool       $inverted
	 *
	 * @internal Add role permission
	 *
	 */
	abstract public function addRolePermission(Role $role, Permission $permission, bool $inverted = false): void;

	/**
	 * @param Role              $role
	 * @param Permission|string $permission
	 *
	 * @internal Remove role permission
	 *
	 */
	abstract public function removeRolePermission(Role $role, $permission): void;

	/**
	 * @param string $name
	 * @param int    $id
	 * @param int    $position
	 *
	 * @internal Create role on storage
	 */
	abstract public function createRoleOnStorage(string $name, int $id, int $position): void;

	/**
	 * @param Role $role
	 *
	 * @internal Delete role from storage
	 */
	abstract public function deleteRoleFromStorage(Role $role): void;

	/**
	 * @param int $offset
	 * @param int $amount
	 *
	 * @internal Shift role positions
	 */
	abstract public function shiftRoles(int $offset, int $amount = 1): void;

	/**
	 * @param int $offset
	 * @param int $amount
	 *
	 * @internal Unshift role positions
	 */
	abstract public function unshiftRoles(int $offset, int $amount = 1): void;

	/**
	 * Gracefully shutdown the data source
	 */
	abstract public function shutdown(): void;

	/**
	 * Save current state to disk (if applicable)
	 */
	abstract public function flush(): void;
}