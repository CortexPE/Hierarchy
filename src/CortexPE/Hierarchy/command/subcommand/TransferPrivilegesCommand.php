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


use CortexPE\Commando\BaseCommand;
use CortexPE\Hierarchy\command\args\MemberArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\member\BaseMember;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;

class TransferPrivilegesCommand extends HierarchySubCommand implements FormedCommand {

	protected function prepare(): void {
		$this->registerArgument(0, new MemberArgument("sourceMember", false));
		$this->registerArgument(1, new MemberArgument("targetMember", false));
		$this->setPermission(implode(";", [
			"hierarchy",
			"hierarchy.member",
			"hierarchy.member.transfer_privileges"
		]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 2) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		/** @var BaseMember $source */
		$source = $args["sourceMember"];
		/** @var BaseMember $target */
		$target = $args["targetMember"];

		if($source === $target) {
			$this->sendFormattedMessage("cmd.transfer_privileges.same_member");
			return;
		}

		if(!$this->doHierarchyPositionCheck($source) || !$this->doHierarchyPositionCheck($target)) {
			return;
		}

		foreach($source->getRoles() as $role) {
			$target->addRole($role);
			$source->removeRole($role);
		}
		$pMgr = PermissionManager::getInstance();
		foreach($source->getMemberPermissions() as $permissionName => $value) {
			$perm = $pMgr->getPermission($permissionName);
			if($perm instanceof Permission) {
				if($value){
					$target->addMemberPermission($perm);
				} else {
					$target->denyMemberPermission($perm);
				}
			}
			$source->removeMemberPermission($permissionName);
		}
		$this->sendFormattedMessage("cmd.transfer_privileges.success", [
			"source" => $source->getName(),
			"target" => $target->getName()
		]);
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			$this->currentSender->sendForm(new CustomForm($this->plugin->getName(), [
				new Label("description", $this->getDescription()),
				new Input("sourceMember", "Source Member", "Source Member Name"),
				new Input("targetMember", "Target Member", "Target Member Name")
			],
				function(Player $player, CustomFormResponse $response): void {
					$this->setCurrentSender($player);
					$this->onRun($player, $this->getName(), [
						"sourceMember" => $this->memberFactory->getMember($response->getString("sourceMember")),
						"targetMember" => $this->memberFactory->getMember($response->getString("targetMember"))
					]);
				}
			));
		}
	}
}