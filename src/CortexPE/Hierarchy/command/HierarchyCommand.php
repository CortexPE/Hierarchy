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

namespace CortexPE\Hierarchy\command;


use CortexPE\Commando\BaseCommand;
use CortexPE\Hierarchy\command\subcommand\CreateRoleCommand;
use CortexPE\Hierarchy\command\subcommand\DeleteRoleCommand;
use CortexPE\Hierarchy\command\subcommand\DenyPermCommand;
use CortexPE\Hierarchy\command\subcommand\FlushCommand;
use CortexPE\Hierarchy\command\subcommand\GiveRoleCommand;
use CortexPE\Hierarchy\command\subcommand\GrantPermCommand;
use CortexPE\Hierarchy\command\subcommand\InfoCommand;
use CortexPE\Hierarchy\command\subcommand\RevokePermCommand;
use CortexPE\Hierarchy\command\subcommand\TakeRoleCommand;
use CortexPE\Hierarchy\command\subcommand\TransferPrivilegesCommand;
use CortexPE\Hierarchy\Hierarchy;
use pocketmine\command\CommandSender;

class HierarchyCommand extends BaseCommand {
	/** @var Hierarchy */
	private $plugin;

	public function __construct(Hierarchy $plugin, string $name, string $description = "", array $aliases = []) {
		$this->plugin = $plugin;
		parent::__construct($plugin, $name, $description, $aliases);
	}

	protected function prepare(): void {
		$this->registerSubCommand(
			new CreateRoleCommand(
				$this->plugin,
				"createrole",
				"Create a new Role"
			)
		);
		$this->registerSubCommand(
			new GiveRoleCommand(
				$this->plugin,
				"giverole",
				"Give role to member"
			)
		);
		$this->registerSubCommand(
			new TakeRoleCommand(
				$this->plugin,
				"takerole",
				"Take role from member"
			)
		);
		$this->registerSubCommand(
			new DeleteRoleCommand(
				$this->plugin,
				"deleterole",
				"Delete a role"
			)
		);
		$this->registerSubCommand(
			new GrantPermCommand(
				$this->plugin,
				"grantperm",
				"Grant permission to role/member"
			)
		);
		$this->registerSubCommand(
			new DenyPermCommand(
				$this->plugin,
				"denyperm",
				"Deny permission from role/member"
			)
		);
		$this->registerSubCommand(
			new RevokePermCommand(
				$this->plugin,
				"revokeperm",
				"Revoke or remove permission from role/member",
				["removeperm"]
			)
		);
		$this->registerSubCommand(
			new FlushCommand(
				$this->plugin,
				"flush",
				"Save role configuration to disk (if applicable)",
				["save"]
			)
		);
		$this->registerSubCommand(
			new InfoCommand(
				$this->plugin,
				"info",
				"Get the role list and role or member information",
				["i"]
			)
		);
		$this->registerSubCommand(
			new TransferPrivilegesCommand(
				$this->plugin,
				"transferprvlgs",
				"Transfer privileges between players"
			)
		);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$this->sendUsage();
	}
}