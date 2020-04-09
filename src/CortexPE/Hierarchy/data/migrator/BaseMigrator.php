<?php


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
		foreach(
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
				RecursiveIteratorIterator::SELF_FIRST) as $item
		) {
			if($item->isDir()) {
				mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
			} else {
				copy($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
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