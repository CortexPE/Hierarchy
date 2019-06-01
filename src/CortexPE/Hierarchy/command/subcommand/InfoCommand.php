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

namespace CortexPE\Hierarchy\command\subcommand;


use CortexPE\Hierarchy\command\args\InfoTargetEnumArgument;
use CortexPE\Hierarchy\command\args\MemberArgument;
use CortexPE\Hierarchy\command\args\RoleArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;
use function implode;

class InfoCommand extends HierarchySubCommand {
	protected function prepare(): void {
		$this->registerArgument(0, new InfoTargetEnumArgument("targetType", false));
		$this->registerArgument(1, new MemberArgument("target", true));
		$this->registerArgument(1, new RoleArgument("target", true));
		$this->setPermission(implode(";", [
			"hierarchy",
			"hierarchy.info",
			"hierarchy.info.list_roles",
			"hierarchy.info.member",
			"hierarchy.info.role"
		]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($args["targetType"] === InfoTargetEnumArgument::TARGET_MEMBER && isset($args["target"])) {
			if($sender->hasPermission("hierarchy.info.member")) {
				/** @var BaseMember $target */
				$target = $args["target"];
				$this->sendFormattedMessage("cmd.info.member.header", [
					"member" => $target->getName()
				]);
				$this->sendFormattedMessage("cmd.info.member.roles_header");
				foreach($target->getRoles() as $role) {
					$this->sendFormattedMessage("cmd.info.member.role_entry", [
						"role" => $role->getName(),
						"role_id" => $role->getId()
					]);
				}
				$this->sendFormattedMessage("cmd.info.member.m_perms_header");
				foreach($target->getMemberPermissions() as $permission => $allowed) {
					$this->sendFormattedMessage("cmd.info.member.m_perm_entry", [
						"permission" => $permission,
						"color" => $allowed ? TextFormat::GREEN : TextFormat::RED . "-"
					]);
				}
			} else {
				$this->sendPermissionError();
			}
		} elseif($args["targetType"] === InfoTargetEnumArgument::TARGET_ROLE && isset($args["target"])) {
			if($sender->hasPermission("hierarchy.info.role")) {
				/** @var Role $target */
				$target = $args["target"];
				$this->sendFormattedMessage("cmd.info.role.header", [
					"role" => $target->getName(),
					"role_id" => $target->getId()
				]);
				$this->sendFormattedMessage("cmd.info.role.position", [
					"position" => $target->getPosition()
				]);
				$this->sendFormattedMessage("cmd.info.role.default", [
					"isDefault" => $target->isDefault() ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO"
				]);
				$this->sendFormattedMessage("cmd.info.role.perms_header");
				foreach($target->getPermissions() as $permission => $allowed) {
					$this->sendFormattedMessage("cmd.info.role.perm_entry", [
						"permission" => $permission,
						"color" => $allowed ? TextFormat::GREEN : TextFormat::RED . "-"
					]);
				}
				$this->sendFormattedMessage("cmd.info.role.members_header", [
					"count" => ($c = count($target->getMembers()))
				]);
				if($c > 0) {
					foreach($target->getMembers() as $member) {
						$this->sendFormattedMessage("cmd.info.role.member_entry", [
							"member" => $member->getName()
						]);
					}
				} else {
					$this->sendFormattedMessage("cmd.info.role.no_online_members");
				}
			} else {
				$this->sendPermissionError();
			}
		} elseif($args["targetType"] === InfoTargetEnumArgument::TARGET_ROLE_LIST) {
			$list = $this->roleManager->getRoles();
			$this->sendFormattedMessage("cmd.info.role_list.header", [
				"count" => ($c = count($list))
			]);
			if($c > 0) {
				foreach($list as $role) {
					$this->sendFormattedMessage("cmd.info.role_list.entry", [
						"role" => $role->getName(),
						"role_id" => $role->getId(),
					]);
				}
			} else {
				$this->sendFormattedMessage("err.no_roles");
			}
		} else {
			$this->sendUsage();
		}
	}
}