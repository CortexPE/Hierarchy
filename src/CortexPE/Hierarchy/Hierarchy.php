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

use CortexPE\Commando\PacketHooker;
use CortexPE\Hierarchy\command\HierarchyCommand;
use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\data\JSONDataSource;
use CortexPE\Hierarchy\data\MySQLDataSource;
use CortexPE\Hierarchy\data\SQLiteDataSource;
use CortexPE\Hierarchy\data\YAMLDataSource;
use CortexPE\Hierarchy\lang\MessageStore;
use CortexPE\Hierarchy\member\MemberFactory;
use CortexPE\Hierarchy\role\RoleManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use function strtolower;

class Hierarchy extends PluginBase {
	/** @var DataSource */
	private $dataSource;
	/** @var RoleManager */
	private $roleManager;
	/** @var MemberFactory */
	private $memberFactory;

	/** @var bool */
	private $superAdminOPs = false;

	public function onEnable(): void {
		$this->saveResource("config.yml");
		(new MessageStore($this->getDataFolder() . "messages.yml"));
		$conf = $this->getConfig();

		$this->superAdminOPs = $conf->get("superAdminOPs", $this->superAdminOPs);

		switch($conf->getNested("dataSource.type", "sqlite3")) {
			case "json":
				$this->dataSource = new JSONDataSource($this, $conf->getNested("dataSource.json"));
				break;
			case "yaml":
				$this->dataSource = new YAMLDataSource($this);
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
				$this->dataSource = new MySQLDataSource($this, $conf->getNested("dataSource.mysql"));
				break;
			default:
				$this->getLogger()
					 ->error("Invalid data source type, must be one of the following: 'json', 'sqlite3', 'mysql', 'yaml'");
				$this->getServer()->getPluginManager()->disablePlugin($this);

				return;
		}

		DefaultPermissions::registerCorePermissions();

		$this->roleManager = new RoleManager($this);
		$this->memberFactory = new MemberFactory($this);

		$this->dataSource->initialize();
	}

	/**
	 * @internal used to continue startup after the data source has finished initialization
	 */
	public function continueStartup(): void {
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		if(!PacketHooker::isRegistered()) {
			PacketHooker::register($this);
		}
		$this->getServer()->getCommandMap()->register(
			strtolower($this->getName()),
			new HierarchyCommand($this, "hrk", "Hierarchy main command")
		);
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
	 * @return DataSource
	 */
	public function getDataSource(): DataSource {
		return $this->dataSource;
	}

	/**
	 * @return RoleManager
	 */
	public function getRoleManager(): RoleManager {
		return $this->roleManager;
	}

	/**
	 *
	 * @return MemberFactory
	 */
	public function getMemberFactory(): MemberFactory {
		return $this->memberFactory;
	}

	/**
	 * @return bool
	 */
	public function isSuperAdminOPs(): bool {
		return $this->superAdminOPs;
	}
}
