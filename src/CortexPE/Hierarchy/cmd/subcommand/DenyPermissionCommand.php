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


use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\lang\MessageStore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\PermissionManager;

class DenyPermissionCommand extends SubCommand {
	public function __construct(
		Hierarchy $hierarchy,
		Command $parent,
		string $name,
		array $aliases,
		string $usageMessage,
		string $descriptionMessage
	) {
		parent::__construct($hierarchy, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.deny_permission");
	}

	public function execute(CommandSender $sender, array $args): void {
		if(count($args) === 2) {
			$role = $this->resolveRole($sender, (int)$args[0]);
			if($role !== null) {
				$permission = PermissionManager::getInstance()->getPermission($args[1]);
				if($permission !== null) {
					$role->denyPermission($permission);
					$sender->sendMessage(MessageStore::getMessage("cmd.deny_perm.success", [
						"role" => $role->getName(),
						"role_id" => $role->getId(),
						"permission" => $permission->getName()
					]));
				} else {
					$sender->sendMessage(MessageStore::getMessage("err.unknown_permission"));
				}
			} else {
				$sender->sendMessage(MessageStore::getMessage("err.unknown_role"));
			}
		} else {
			$this->sendUsage($sender);
		}
	}
}