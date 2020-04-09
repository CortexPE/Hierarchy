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


use CortexPE\Hierarchy\data\legacy\IndexedLDR;
use CortexPE\Hierarchy\data\legacy\JSONLDR;
use CortexPE\Hierarchy\data\legacy\MySQLLDR;
use CortexPE\Hierarchy\data\legacy\SQLiteLDR;
use CortexPE\Hierarchy\data\legacy\SQLLDR;
use CortexPE\Hierarchy\data\legacy\YAMLLDR;
use CortexPE\Hierarchy\data\role\YAMLRoleDS;
use CortexPE\Hierarchy\exception\StartupFailureException;
use CortexPE\Hierarchy\Hierarchy;
use function file_exists;
use function unlink;
use function yaml_emit_file;
use function yaml_parse_file;

class DSMigrator extends BaseMigrator {
	public static function tryMigration(Hierarchy $plugin): void {
		if(file_exists(($fp = $plugin->getDataFolder() . "config.yml"))) {
			$data = yaml_parse_file($fp);
			if(!isset($data["configVersion"])) { // v1.0.0 -> v1.1.0
				$plugin->getLogger()->info("Migrating all data to newer Data Storage format");
				self::createBackup($plugin);
				unlink($fp);
				$plugin->saveConfig();
				$conf = $plugin->getConfig();
				switch($data["dataSource"]["type"]) {
					case "json":
						$source = new JSONLDR($plugin);
						break;
					case "yaml":
						$source = new YAMLLDR($plugin);
						break;
					case "mysql":
						$source = new MySQLLDR($plugin, $data["dataSource"]["mysql"]);
						break;
					case "sqlite3":
						$source = new SQLiteLDR($plugin, $data["dataSource"]["sqlite3"]);
						break;
					default:
						unlink($fp); // delete new updated config
						yaml_emit_file($fp, $data); // restore old
						throw new StartupFailureException("Invalid legacy configuration file");
				}
				$conf->setNested("memberDataSource.type", $data["dataSource"]["type"]);
				$conf->setNested("memberDataSource.sqlite3", $data["dataSource"]["sqlite3"]);
				$conf->setNested("memberDataSource.mysql", $data["dataSource"]["mysql"]);
				if(!($source instanceof IndexedLDR)) {
					// default to yaml
					$conf->setNested("roleDataSource.type", "yaml");

					// this will only run when it's roles from mysql / sqlite -> yaml anyway
					$target = new YAMLRoleDS($plugin);
					$target->setRoles($source->getRoles());
					$target->flush();
					$target->shutdown();
				}
				if($source instanceof SQLLDR){
					$db = $source->getDB();
					$db->executeGeneric("hierarchy.drop.rolesTable");
					$db->executeGeneric("hierarchy.drop.rolePermissionTable");
					$db->waitAll();
				}

				$conf->save();
				$source->shutdown();
			}
		}
	}
}