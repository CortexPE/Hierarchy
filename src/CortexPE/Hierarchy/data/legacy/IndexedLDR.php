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

namespace CortexPE\Hierarchy\data\legacy;

use DirectoryIterator;
use Generator;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\PermissionManager;
use function array_merge;
use function file_exists;
use function file_get_contents;

abstract class IndexedLDR extends LegacyDataReader {
	/** @var string */
	protected const FILE_EXTENSION = null;

	abstract function decode(string $string): array;

	public function getMemberDatum(): Generator {
		foreach(new DirectoryIterator($this->plugin->getDataFolder() . "members") as $fInfo) {
			if($fInfo->getExtension() === self::FILE_EXTENSION) {
				$dat = $this->decode(file_get_contents($fInfo->getPathname()));

				yield [
					"name" => $fInfo->getBasename("." . $fInfo->getExtension()), // strip off extension
					"roles" => array_merge([$this->getDefaultRoleID()], $dat["roles"] ?? []),
					"permissions" => $dat["permissions"] ?? []
				];
			}
		}
	}

	public function shutdown(): void {
		// noop
	}

	public function getRoles(): array {
		if(file_exists(($fp = $this->plugin->getDataFolder() . "roles." . static::FILE_EXTENSION))) {
			return $this->decode(file_get_contents($fp));
		} else {
			$pMgr = PermissionManager::getInstance();

			return [
				[
					"ID" => 1,
					"Position" => 0,
					"Name" => "Member",
					"isDefault" => true,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_USER)->getChildren())
				],
				[
					"ID" => 2,
					"Position" => 1,
					"Name" => "Operator",
					"isDefault" => false,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_OPERATOR)->getChildren())
				],
			];
		}
	}
}