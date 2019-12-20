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

namespace CortexPE\Hierarchy\data\traits;


use function array_search;
use function array_unique;
use function array_values;
use function in_array;

trait IndexedDataUtilities {
	private static function permissionInArray(string $permission, array $array): bool {
		return in_array($permission, $array) || in_array("-" . $permission, $array);
	}

	private static function removePermissionFromArray(string $permission, array &$array): void {
		if(self::permissionInArray($permission, $array)) {
			unset(
				$array[array_search($permission, $array)],
				$array[array_search("-" . $permission, $array)]
			);
		}
	}

	private function reIndex(array &$array): void {
		$array = array_values(array_unique($array));
	}
}