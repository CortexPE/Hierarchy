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

namespace CortexPE\Hierarchy\cmd;


use CortexPE\Hierarchy\cmd\subcommand\AddMemberPermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\AddRolePermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\CreateRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\DeleteRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\DenyMemberPermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\DenyRolePermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\FlushCommand;
use CortexPE\Hierarchy\cmd\subcommand\GiveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\ListCommand;
use CortexPE\Hierarchy\cmd\subcommand\ListPermissionsCommand;
use CortexPE\Hierarchy\cmd\subcommand\PlayersCommand;
use CortexPE\Hierarchy\cmd\subcommand\RemoveMemberPermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\RemoveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\RemoveRolePermissionCommand;
use CortexPE\Hierarchy\cmd\subcommand\RoleOptionsCommand;
use CortexPE\Hierarchy\cmd\subcommand\UserInfoCommand;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\lang\MessageStore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class RoleCommand extends Command {

	/** @var SubCommand[] */
	private $subCommands = [];

	public function __construct(Hierarchy $plugin, string $name, string $description) {
		parent::__construct($name, $description);

		$this->registerCommand(new GiveRoleCommand($plugin, $this, "give", ["add"], "/role give <player> <roleID>",
			"Give role to player"));
		$this->registerCommand(new UserInfoCommand($plugin, $this, "who", [], "/role who <player>", "Check user info"));
		$this->registerCommand(new ListCommand($plugin, $this, "list", [], "/role list", "Lists all roles"));
		$this->registerCommand(new RemoveRoleCommand($plugin, $this, "remove", [], "/role remove <player> <roleID>",
			"Remove role from player"));
		$this->registerCommand(new ListPermissionsCommand($plugin, $this, "roleperm", [], "/role roleperm <roleID>",
			"Get the permissions of a role"));
		$this->registerCommand(new PlayersCommand($plugin, $this, "players", [], "/role players <roleID>",
			"Get the players in a group"));
		$this->registerCommand(new RoleOptionsCommand($plugin, $this, "options", [], "/role options <roleID>",
			"Menu for selecting either players or permissions"));
		$this->registerCommand(new CreateRoleCommand($plugin, $this, "create", [], "/role create <roleName>",
			"Creates a new role"));
		$this->registerCommand(new DeleteRoleCommand($plugin, $this, "delete", [], "/role delete <roleID>",
			"Delete role"));
		$this->registerCommand(new FlushCommand($plugin, $this, "flush", [], "/role flush",
			"Save roles to disk"));
		$this->registerCommand(new AddRolePermissionCommand($plugin, $this, "addrperm", [],
			"/role addrperm <roleID> <permission>",
			"Add permission to role"));
		$this->registerCommand(new DenyRolePermissionCommand($plugin, $this, "denyrperm", [],
			"/role denyrperm <roleID> <permission>",
			"Deny permission from role"));
		$this->registerCommand(new RemoveRolePermissionCommand($plugin, $this, "removerperm", [],
			"/role removerperm <roleID> <permission>",
			"Remove permission from role"));
		$this->registerCommand(new AddMemberPermissionCommand($plugin, $this, "addmperm", [],
			"/role addmperm <memberName> <permission>",
			"Add permission to member"));
		$this->registerCommand(new DenyMemberPermissionCommand($plugin, $this, "denymperm", [],
			"/role denymperm <memberName> <permission>",
			"Deny permission from member"));
		$this->registerCommand(new RemoveMemberPermissionCommand($plugin, $this, "removemperm", [],
			"/role removemperm <memberName> <permission>",
			"Remove permission from member"));
	}

	/**
	 * @param SubCommand $command
	 */
	public function registerCommand(SubCommand $command) {
		$this->subCommands[] = $command;
	}

	/**
	 * @return SubCommand[]
	 */
	public function getCommands(): array {
		return $this->subCommands;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	final public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if(isset($args[0]) && $this->getCommand($args[0]) !== null) {
			$cmd = $this->getCommand(array_shift($args));
			if(($perm = $cmd->getPermission()) !== null && !$sender->hasPermission($perm)) {
				$sender->sendMessage(MessageStore::getMessage("err.insufficient_permissions"));

				return;
			}
			$cmd->execute($sender, $args);
		} else {
			$sender->sendMessage(MessageStore::getMessage("cmd.help_header"));
			foreach($this->subCommands as $subCommand) {
				$sender->sendMessage(MessageStore::getMessage("cmd.help_format", [
					"usage" => $subCommand->getUsage(),
					"description" => $subCommand->getDescription()
				]));
			}
		}
	}

	/**
	 * @param string $alias
	 *
	 * @return null|SubCommand
	 */
	public function getCommand(string $alias): ?SubCommand {
		foreach($this->subCommands as $key => $command) {
			if(in_array(strtolower($alias), $command->getAliases(), true) or $alias === $command->getName()) {
				return $command;
			}
		}

		return null;
	}
}