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

class ListPermissionsCommand extends SubCommand {
	public function __construct(
		Hierarchy $hierarchy,
		Command $parent,
		string $name,
		array $aliases,
		string $usageMessage,
		string $descriptionMessage
	) {
		parent::__construct($hierarchy, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.list_permissions");
	}

	public function execute(CommandSender $sender, array $args, bool $back = false, bool $previousBack = false): void {
		if(isset($args[0])) {
			$role = $this->resolveRole($sender, (int)$args[0]);
			if($role !== null) {
				$permissions = $role->getPermissions();

				$permissionKeys = array_keys($permissions);

				if($sender instanceof Player) {

					foreach($permissionKeys as $permission) {
						if($permissions[$permission]) {
							$options[] = new MenuOption(MessageStore::getMessage("cmd.permission.true",
								["permission" => $permission]));
						} else {
							$options[] = new MenuOption(MessageStore::getMessage("cmd.permission.false",
								["permission" => $permission]));
						}
					}

					$options = $options ?? [new MenuOption("err.no_permissions")];

					// Send the previous form if back is true, since there is no way to edit permissions I can't make a customform gui to do so..

					$onClose = $back ? function (Player $player, int $selected) use ($role, $previousBack): void {
						/** @var RoleCommand $parent */
						$parent = $this->getParent();
						/** @var RoleOptionsCommand $optionsCommand */
						$optionsCommand = $parent->getCommand('options');
						$optionsCommand->execute($player, [$role->getId()], $previousBack);
					} : function (Player $player, int $selected): void {
					};

					$permissionForm = new MenuForm(MessageStore::getMessage("form.title"),
						MessageStore::getMessage("cmd.permission.header"), $options, $onClose);

					$sender->sendForm($permissionForm);

				} else {
					$sender->sendMessage(MessageStore::getMessage("cmd.permission.header"));
					foreach($permissionKeys as $permission) {
						if($permissions[$permission]) {
							$sender->sendMessage(MessageStore::getMessage("cmd.permission.true",
								["permission" => $permission]));
						} else {
							$sender->sendMessage(MessageStore::getMessage("cmd.permission.false",
								["permission" => $permission]));
						}
					}
				}
			}
		} else {
			$this->sendUsage($sender);
		}
	}
}