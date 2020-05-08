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

namespace CortexPE\Hierarchy\data\migrator;


use CortexPE\Hierarchy\Hierarchy;

class RolePositionSimplifier extends BaseMigrator {
	public static function tryMigration(Hierarchy $plugin): void {
		switch($plugin->getConfig()->getNested("roleDataSource.type")){
			case "yaml":
				$data = file_exists($fn = $plugin->getDataFolder() . "roles.yml") ? yaml_parse_file($fn) : [];
				break;
			case "json":
				$data = file_exists($fn = $plugin->getDataFolder() . "roles.json") ? json_decode(file_get_contents($fn), true) : [];
				break;
			default:
				throw new \InvalidStateException("Invalid role DataSource type, please use either 'json' or 'yaml'");
		}
		$migrated = false;
		$roles = [];
		foreach($data as $n => $role){
			if(isset($role["Position"])){
				$n = $role["Position"];
				unset($role["Position"]);
				if(isset($roles[$n])){
					array_splice($roles, $n, 0, $role);
				} else {
					$roles[$n] = $role;
				}
				$migrated = true;
			} else {
				$roles[$n] = $role;
			}
		}
		if(!$migrated){
			return;
		}
		$plugin->getLogger()->info("Migrating role configuration to new format");
		self::createBackup($plugin);
		ksort($roles);
		$roles = array_values($roles); // remove keys
		switch($plugin->getConfig()->getNested("roleDataSource.type")){
			case "yaml":
				file_put_contents($fn, yaml_emit($roles));
				break;
			case "json":
				file_put_contents($fn, json_encode($roles, JSON_PRETTY_PRINT));
				break;
		}
	}
}