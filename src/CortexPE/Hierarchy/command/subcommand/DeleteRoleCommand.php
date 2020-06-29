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
use CortexPE\Hierarchy\command\args\RoleArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\role\Role;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Label;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

class DeleteRoleCommand extends HierarchySubCommand implements FormedCommand {
	protected function prepare(): void {
		$this->registerArgument(0, new RoleArgument("role", true));
		$this->setPermission("hierarchy;hierarchy.role;hierarchy.role.delete");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 1) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		/** @var Role|null $role */
		$role = $args["role"];
		if($role instanceof Role) {
			$formats = [
				"role" => $role->getName(),
				"role_id" => $role->getId(),
			];

			if(!$this->doHierarchyPositionCheck($role)) {
				return;
			}

			if(!$role->isDefault()) {
				$this->roleManager->deleteRole($role);
				$this->sendFormattedMessage("cmd.deleterole.success", $formats);
			} else {
				$this->sendFormattedMessage("cmd.deleterole.fail_role_default", $formats);
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
				new Dropdown("role", "Role", $roles),
			], function (Player $player, CustomFormResponse $response) use ($roles_i): void {
				$this->setCurrentSender($player);
				$this->onRun($player, $this->getName(), [
					"role" => $roles_i[$response->getInt("role")]
				]);
			}));
		}
	}
}