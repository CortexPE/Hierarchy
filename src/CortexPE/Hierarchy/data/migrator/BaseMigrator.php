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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class BaseMigrator {
	private final function __construct() {
	}

	abstract public static function tryMigration(Hierarchy $plugin): void;

	protected static function createBackup(Hierarchy $plugin):void {
		self::recursiveCopy(
			$plugin->getDataFolder(),
			realpath($plugin->getDataFolder()) . "_backup_" . date("d-m-Y_H-i-s")
		);
	}

	/**
	 * @param string $path
	 * @param string $destination
	 */
	private static function recursiveCopy(string $path, string $destination): void {
		mkdir($destination);
		/** @var \SplFileInfo $item */
		foreach(
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST) as $item
		) {
			if($item->isDir()) {
				mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			} else {
				copy($item->getPathname(), $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			}
		}
	}
	public static function iterateDirectoryFiles(string $directoryPath):\Generator{
		if ($handle = opendir($directoryPath)) {
			while (false !== ($file = readdir($handle))) {
				if(in_array($file, [".", ".."])){
					continue;
				}

				yield $directoryPath . DIRECTORY_SEPARATOR . $file;
			}
			closedir($handle);
		}
	}
}