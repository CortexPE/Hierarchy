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


use CortexPE\Hierarchy\cmd\subcommand\GiveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\ListCommand;
use CortexPE\Hierarchy\cmd\subcommand\RemoveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\UserInfoCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RoleCommand extends Command {

	/** @var SubCommand[] */
	private $subCommands = [];

	public function __construct(string $name, string $description) {
		parent::__construct($name, $description);

		$this->registerCommand(new GiveRoleCommand("give", ["add"], "/role give <player> <roleID>", "Give role to player"));
		$this->registerCommand(new UserInfoCommand("who", [], "/role who <player>", "Check user info"));
		$this->registerCommand(new ListCommand("list", [], "/role list", "Lists all roles"));
		$this->registerCommand(new RemoveRoleCommand("remove", [], "/role remove <player> <roleID>", "Remove role from player"));
	}

	/**
	 * @return SubCommand[]
	 */
	public function getCommands(): array {
		return $this->subCommands;
	}

	/**
	 * @param SubCommand $command
	 */
	public function registerCommand(SubCommand $command) {
		$this->subCommands[] = $command;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	final public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if(isset($args[0]) && $this->getCommand($args[0]) != null) {
			$cmd = $this->getCommand(array_shift($args));
			if(($perm = $cmd->getPermission()) !== null && !$sender->hasPermission($perm)) {
				$sender->sendMessage(TextFormat::RED . "You do not have permissions to use this command.");

				return;
			}
			$cmd->execute($sender, $args);
		} else {
			$sender->sendMessage(TextFormat::GOLD . "Available Hierarchy Role Commands:");
			foreach($this->subCommands as $subCommand) {
				$sender->sendMessage(TextFormat::AQUA . $subCommand->getUsage() . TextFormat::GRAY . " - " . TextFormat::GREEN . $subCommand->getDescription());
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
			if(in_array(strtolower($alias), $command->getAliases()) or $alias == $command->getName()) {
				return $command;
			}
		}

		return null;
	}
}