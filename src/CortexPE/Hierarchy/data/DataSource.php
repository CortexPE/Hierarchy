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

abstract class DataSource {
	public const ACTION_ROLE_ADD = "role.add";
	public const ACTION_ROLE_REMOVE = "role.remove";

	/** @var Hierarchy */
	protected $plugin;

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return Hierarchy
	 */
	public function getPlugin(): Hierarchy {
		return $this->plugin;
	}

	/**
	 * @internal Get member data from the data source then pass to member object
	 *
	 * @param BaseMember $member
	 * @param callable $onLoad
	 */
	abstract public function loadMemberData(BaseMember $member, ?callable $onLoad = null): void;

	/**
	 * @internal Update member data on data source
	 *
	 * @param BaseMember $member
	 * @param string $action
	 * @param mixed  $data
	 */
	abstract public function updateMemberData(BaseMember $member, string $action, $data): void;

	/**
	 * Gracefully shutdown the data source
	 */
	abstract public function shutdown(): void;
}