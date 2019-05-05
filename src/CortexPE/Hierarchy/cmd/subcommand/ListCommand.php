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

namespace CortexPE\Hierarchy\cmd\subcommand;


use CortexPE\Hierarchy\cmd\RoleCommand;
use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\lang\MessageStore;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ListCommand extends SubCommand {
	public function __construct(
		Hierarchy $plugin,
		Command $parent,
		string $name,
		array $aliases,
		string $usageMessage,
		string $descriptionMessage
	) {
		parent::__construct($plugin, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.list_roles");
	}

	public function execute(CommandSender $sender, array $args): void {

		$roles = $this->plugin->getRoleManager()->getRoles();

		if($sender instanceof Player) {

			foreach($roles as $roleID => $role) {
				$options[] = new MenuOption("{$role->getName()} (ID: {$roleID})");
			}

			$options = $options ?? [new MenuOption("err.no_roles")];

			$roleForm = new MenuForm(MessageStore::getMessage("form.title"), "Roles:", $options,
				function (Player $player, int $selected): void {

					/** @var RoleCommand $parent */
					$parent = $this->getParent();

					/** @var RoleOptionsCommand $options */
					$options = $parent->getCommand('options');

					$options->execute($player, [++$selected], true);

				});

			$sender->sendForm($roleForm);

		} else {
			$sender->sendMessage("Roles:");
			foreach($roles as $roleID => $role) {
				$sender->sendMessage(MessageStore::getMessage("cmd.list.role_format", [
					"role" => $role->getName(),
					"role_id" => $role->getId(),
				]));
			}
		}
	}
}