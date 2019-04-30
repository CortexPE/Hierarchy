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

namespace CortexPE\Hierarchy;

use CortexPE\Hierarchy\cmd\RoleCommand;
use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\data\SQLiteDataSource;
use CortexPE\Hierarchy\member\MemberFactory;
use CortexPE\Hierarchy\role\RoleManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Loader extends PluginBase {
	/** @var Loader */
	protected static $instance;
	/** @var DataSource */
	protected $dataSource;
	/** @var RoleManager */
	protected $roleManager;
	/** @var MemberFactory */
	protected $memberFactory;

	public function onEnable(): void {
		self::$instance = $this;
		$this->saveResource("config.yml");
		$conf = new Config($this->getDataFolder() . "config.yml", Config::YAML);

		switch($conf->getNested("dataSource.type", "json")) {
			case "json":
				// TODO: implement
				break;
			case "sqlite3":
				if(!extension_loaded("sqlite3")) {
					$this->getLogger()
						 ->error("SQLite3 PHP Extension is not enabled! Please check php.ini or choose a different data source type");
					$this->getServer()->getPluginManager()->disablePlugin($this);

					return;
				}
				$this->dataSource = new SQLiteDataSource($this, $conf->getNested("dataSource.sqlite3"));
				break;
			case "mysql":
				if(!extension_loaded("mysqli")) {
					$this->getLogger()
						 ->error("MySQLi PHP Extension is not enabled! Please check php.ini or choose a different data source type");
					$this->getServer()->getPluginManager()->disablePlugin($this);

					return;
				}
				// TODO: use libasyql
				break;
			default:
				$this->getLogger()
					 ->error("Invalid data source type, must be one of the following: 'json', 'sqlite3', 'mysql'");
				$this->getServer()->getPluginManager()->disablePlugin($this);

				return;
		}

		$this->roleManager = new RoleManager($this);
		$this->memberFactory = new MemberFactory($this);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$cmd = new RoleCommand("role", "Hierarchy main command");

		$this->getServer()->getCommandMap()->register("hierarchy", $cmd);
	}

	public function getDataSource(): DataSource {
		return $this->dataSource;
	}

	public function onDisable(): void {
		if($this->memberFactory instanceof MemberFactory) {
			$this->memberFactory->shutdown();
		}
		// last
		if($this->dataSource instanceof DataSource) {
			$this->dataSource->shutdown();
		}
	}

	/**
	 * @return RoleManager
	 */
	public function getRoleManager(): RoleManager {
		return $this->roleManager;
	}

	/**
	 * @return MemberFactory
	 */
	public function getMemberFactory(): MemberFactory {
		return $this->memberFactory;
	}

	/**
	 * @return Loader
	 */
	public static function getInstance(): Loader {
		return self::$instance;
	}
}
