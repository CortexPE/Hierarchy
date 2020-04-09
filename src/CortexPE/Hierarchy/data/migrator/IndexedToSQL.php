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


use CortexPE\Hierarchy\data\member\MemberDataSource;
use CortexPE\Hierarchy\data\member\SQLiteMemberDS;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\OfflineMember;

class IndexedToSQL extends BaseMigrator {
	public static function tryMigration(Hierarchy $plugin): void {
		if(is_dir(($dir = $plugin->getDataFolder() . "members/"))){
			$plugin->getLogger()->info("Migrating member data to newer Data Storage format");
			self::createBackup($plugin);
			$target = new SQLiteMemberDS($plugin, $plugin->getConfig()->getNested("memberDataSource.sqlite3"));
			foreach(self::iterateDirectoryFiles($dir) as $file){
				if(!is_dir($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ["json", "yml"])){
					$data = yaml_parse(file_get_contents($file)); // yaml accepts json too
					$member = new OfflineMember($plugin, pathinfo($file, PATHINFO_FILENAME));
					foreach($data["roles"] ?? [] as $roleID){
						$target->updateMemberData($member, MemberDataSource::ACTION_MEMBER_ROLE_ADD, $roleID);
					}
					foreach($data["permissions"] ?? [] as $permission){
						$target->updateMemberData($member, MemberDataSource::ACTION_MEMBER_PERMS_ADD, $permission);
					}
					unlink($file);
				}
			}
			$target->getDB()->waitAll();
			$target->shutdown();
			rmdir($dir);

			$plugin->getConfig()->setNested("memberDataSource.type", "sqlite3");
			$plugin->getConfig()->set("configVersion", "1.6");
			$plugin->getConfig()->save();
		}
	}
}