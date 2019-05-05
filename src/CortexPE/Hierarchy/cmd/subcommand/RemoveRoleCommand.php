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
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class RemoveRoleCommand extends SubCommand {
	public function __construct(
		Hierarchy $plugin,
		Command $parent,
		string $name,
		array $aliases,
		string $usageMessage,
		string $descriptionMessage
	) {
		parent::__construct($plugin, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.remove");
	}

	public function execute(CommandSender $sender, array $args): void {
		if(count($args) == 2) {
			$role = $this->plugin->getRoleManager()->getRole((int)$args[1]);
			if($role instanceof Role) {
				$target = $args[0];
				$tmp = $sender->getServer()->getPlayer($target);
				if($tmp instanceof Player) {
					$target = $tmp;
				}

				$this->plugin->getMemberFactory()
							 ->getMember($target, true, function (BaseMember $member) use ($role, $sender) {
								 if($sender instanceof Player) {
									 $sMember = $this->plugin->getMemberFactory()->getMember($sender);
									 if(
										 $sMember->getTopRole()->getPosition() <= $role->getPosition() ||
										 !$sMember->hasHigherPermissionHierarchy($this->getPermission(), $member)
									 ) {
										 $sender->sendMessage(MessageStore::getMessage("err.target_higher_hrk", [
											 "target" => $member->getName()
										 ]));

										 return;
									 }
								 }
								 if(!$role->isDefault()) {
									 if($member->hasRole($role)) {
										 $member->removeRole($role);
										 $sender->sendMessage(MessageStore::getMessage("cmd.remove.success", [
											 "role" => $role->getName()
										 ]));
									 } else {
										 $sender->sendMessage(MessageStore::getMessage("cmd.remove.no_role", [
											 "role" => $role->getName()
										 ]));
									 }
								 } else {
									 $sender->sendMessage(MessageStore::getMessage("cmd.remove.default"));
								 }
							 });
			} else {
				$sender->sendMessage(MessageStore::getMessage("err.unknown_role"));
			}
		} else {
			$this->sendUsage($sender);
		}
	}
}