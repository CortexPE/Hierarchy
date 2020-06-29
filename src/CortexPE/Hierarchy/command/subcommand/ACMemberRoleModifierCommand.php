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


use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Hierarchy\command\args\MemberArgument;
use CortexPE\Hierarchy\command\args\RoleArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\Toggle;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

abstract class ACMemberRoleModifierCommand extends HierarchySubCommand implements FormedCommand {
	protected const CHILD_PERMISSION = null;
	protected const MESSAGE_ROOT = null;

	protected function prepare(): void {
		$this->registerArgument(0, new MemberArgument("member", true));
		$this->registerArgument(1, new RoleArgument("role", true));
		$this->registerArgument(2, new BooleanArgument("temporary", true));
		$this->setPermission("hierarchy;hierarchy.role;hierarchy.role." . static::CHILD_PERMISSION);
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 2) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		/** @var BaseMember $member */
		$member = $args["member"];
		/** @var Role|null $role */
		$role = $args["role"];

		if($role instanceof Role) {
			if(!$this->doHierarchyPositionCheck($member) || !$this->doHierarchyPositionCheck($role)) {
				return;
			}

			$formats = [
				"member" => $member->getName(),
				"role" => $role->getName(),
				"id" => $role->getId()
			];

			if(!$role->isDefault()) {
				$this->doOperationOnMember($member, $role, ($args["temporary"] ?? false), $formats);
			} else {
				$this->sendFormattedMessage("cmd." . static::MESSAGE_ROOT . ".default", $formats);
			}
		} else {
			$this->sendFormattedMessage("err.unknown_role");
		}
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			if(!$this->getRolesApplicable($roles, $roles_i)) {
				return;
			}
			$this->currentSender->sendForm(new CustomForm($this->plugin->getName(), [
				new Label("description", $this->getDescription()),
				new Input("member", "Member", "Member Name"),
				new Dropdown("role", "Role", $roles),
				new Toggle("temporary", "Temporary", false)
			], function (Player $player, CustomFormResponse $response) use ($roles, $roles_i): void {
				$this->setCurrentSender($player);
				$this->onRun($player, $this->getName(), [
					"member" => $this->memberFactory->getMember($response->getString("member")),
					"role" => $this->roleManager->getRole($roles_i[$response->getInt("role")]),
					"temporary" => $response->getBool("temporary")
				]);
			}));
		}
	}

	abstract protected function doOperationOnMember(BaseMember $member, Role $role, bool $temporary, array $msgFormats): void;
}