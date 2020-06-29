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
use CortexPE\Hierarchy\command\args\PermissionArgument;
use CortexPE\Hierarchy\command\args\RoleArgument;
use CortexPE\Hierarchy\command\args\TargetEnumArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\lang\MessageStore;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\ModalForm;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use function count;
use function implode;

abstract class ACPermissionModifierCommand extends HierarchySubCommand implements FormedCommand {
	protected const CHILD_PERMISSION = null;
	protected const MESSAGE_ROOT = null;

	protected function prepare(): void {
		$this->registerArgument(0, new TargetEnumArgument("targetType", true));
		$this->registerArgument(1, new RoleArgument("targetRole", true));
		$this->registerArgument(1, new MemberArgument("targetMember", true));
		$this->registerArgument(2, new PermissionArgument("permission", true));
		$this->setPermission(implode(";", [
			"hierarchy",
			"hierarchy.role",
			"hierarchy.role." . static::CHILD_PERMISSION,
			"hierarchy.member",
			"hierarchy.member." . static::CHILD_PERMISSION
		]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 3) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		/** @var Permission|null $permission */
		$permission = $args["permission"];

		if($permission instanceof Permission) {
			switch($args["targetType"] ?? "undefined") {
				case TargetEnumArgument::TARGET_MEMBER:
					$target = $args["targetMember"];
					if($target instanceof BaseMember) {
						if($sender->hasPermission("hierarchy.member." . static::CHILD_PERMISSION)) {
							if($this->doHierarchyPositionCheck($target, $permission)) {
								$this->doOperationOnMember($target, $permission);
								$this->sendFormattedMessage("cmd." . static::MESSAGE_ROOT . ".member.success", [
									"permission" => $permission->getName(),
									"member" => $target->getName()
								]);
							} else {
								$this->sendFormattedMessage("err.target_higher_hrk");
							}
						} else {
							$this->sendPermissionError();
						}
						break;
					}
					break;
				case TargetEnumArgument::TARGET_ROLE:
					$target = $args["targetRole"];
					if($target instanceof Role) {
						if($sender->hasPermission("hierarchy.role." . static::CHILD_PERMISSION)) {
							if($this->doHierarchyPositionCheck($target)) {
								$this->doOperationOnRole($target, $permission);
								$this->sendFormattedMessage("cmd." . static::MESSAGE_ROOT . ".role.success", [
									"permission" => $permission->getName(),
									"role" => $target->getName(),
									"role_id" => $target->getId()
								]);
							} else {
								$this->sendFormattedMessage("err.target_higher_hrk");
							}
						} else {
							$this->sendPermissionError();
						}
						break;
					}
					break;
			}
		} else {
			$this->sendFormattedMessage("err.unknown_permission");
		}
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			$this->currentSender->sendForm(new ModalForm(
				$this->plugin->getName(),
				MessageStore::getMessage("cmd." . static::MESSAGE_ROOT . ".form.choose_type"),
				function(Player $player, bool $choice): void {
					if($choice) {
						$this->setCurrentSender($player);
						if(!$this->getRolesApplicable($roles, $roles_i)) {
							return;
						}
						$player->sendForm(new CustomForm($this->plugin->getName(), [
							new Label("description", $this->getDescription()),
							new Dropdown("role", "Role", $roles),
							new Input("permission", "Permission"),
						], function(Player $player, CustomFormResponse $response) use ($roles, $roles_i): void {
							$this->setCurrentSender($player);
							$this->onRun($player, $this->getName(), [
								"targetType" => TargetEnumArgument::TARGET_ROLE,
								"target" => $this->roleManager->getRole($roles_i[$response->getInt("role")]),
								"permission" => PermissionManager::getInstance()
									->getPermission($response->getString("permission")),
							]);
						}));
					} else {
						$player->sendForm(new CustomForm($this->plugin->getName(), [
							new Label("description", $this->getDescription()),
							new Input("member", "Member"),
							new Input("permission", "Permission"),
						], function(Player $player, CustomFormResponse $response): void {
							$this->setCurrentSender($player);
							$this->onRun($player, $this->getName(), [
								"targetType" => TargetEnumArgument::TARGET_MEMBER,
								"target" => $this->memberFactory->getMember($response->getString("member")),
								"permission" => PermissionManager::getInstance()
									->getPermission($response->getString("permission")),
							]);
						}));
					}
				},
				MessageStore::getMessage("cmd." . static::MESSAGE_ROOT . ".form.type_role"),
				MessageStore::getMessage("cmd." . static::MESSAGE_ROOT . ".form.type_member")
			));
		}
	}

	abstract protected function doOperationOnRole(Role $member, Permission $permission): void;

	abstract protected function doOperationOnMember(BaseMember $member, Permission $permission): void;
}