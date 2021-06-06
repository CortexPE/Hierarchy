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


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;

abstract class RoleDataSource extends DataSource {
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
	abstract public function removeRolePermission(Role $role, Permission|string $permission): void;

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
}