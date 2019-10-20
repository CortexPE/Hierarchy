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

/**
 * Class DataSource
 * @package  CortexPE\Hierarchy\data
 * @internal This class (and its children) are only used for the plugin's internal data storage. DO NOT TOUCH!
 */
abstract class DataSource {
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
	 * Gracefully shutdown the data source
	 */
	abstract public function shutdown(): void;

	/**
	 * Save current state to disk (if applicable)
	 */
	abstract public function flush(): void;
}