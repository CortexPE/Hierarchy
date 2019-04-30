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
use CortexPE\Hierarchy\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class UserInfoCommand extends SubCommand {
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
		parent::__construct($name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.user_info");
	}

	public function execute(CommandSender $sender, array $args): void {
		$target = $sender->getServer()->getPlayer($args[0]);
		if($target instanceof Player){
			$member = Loader::getInstance()->getMemberFactory()->getMember($target);
			$roles = $member->getRoles();
			$permissions = $member->getPermissions();
			$sender->sendMessage(TextFormat::GOLD . $member->getName() . "'s Role(s) and Permissions:");
			if(!empty($roles)){
				$sender->sendMessage("Role(s):");
				foreach($roles as $role){
					$sender->sendMessage(" - " . $role->getName());
				}
			}
			if(!empty($permissions)){
				$sender->sendMessage("Permission(s):");
				foreach($permissions as $permission => $allowed){
					$sender->sendMessage(" - " . ($allowed ? TextFormat::GREEN : TextFormat::RED) . $permission);
				}
			}
		} else {
			$sender->sendMessage(TextFormat::RED . "Player is offline.");
		}
	}
}