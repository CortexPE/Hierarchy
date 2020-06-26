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

namespace CortexPE\Hierarchy\data\member;


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;

abstract class MemberDataSource extends DataSource {
	public const ACTION_MEMBER_ROLE_ADD = "member.role.add";
	public const ACTION_MEMBER_ROLE_REMOVE = "member.role.remove";
	public const ACTION_MEMBER_PERMS_ADD = "member.perm.add";
	public const ACTION_MEMBER_PERMS_REMOVE = "member.perm.remove";
	public const ACTION_MEMBER_UPDATE_ROLE_ETC = "member.etc.update.role";
	public const ACTION_MEMBER_UPDATE_PERMISSION_ETC = "member.etc.update.permission";

	/**
	 * @param BaseMember $member
	 *
	 * @internal Get member data from the data source then pass to member object
	 *
	 */
	abstract public function loadMemberData(BaseMember $member): void;

	/**
	 * @param BaseMember $member
	 * @param string     $action
	 * @param mixed      $data
	 *
	 * @internal Update member data on data source
	 *
	 */
	abstract public function updateMemberData(BaseMember $member, string $action, $data): void;

	abstract public function getMemberNamesOf(Role $role): \Generator;
}