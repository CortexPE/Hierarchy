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


use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

class CreateRoleCommand extends HierarchySubCommand implements FormedCommand {
	protected function prepare(): void {
		$this->registerArgument(0, new RawStringArgument("roleName", true));
		$this->setPermission("hierarchy;hierarchy.role;hierarchy.role.create");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 1) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		$newRole = $this->roleManager->createRole($args["roleName"]);
		$this->sendFormattedMessage("cmd.createrole.success", [
			"role" => $newRole->getName(),
			"role_id" => $newRole->getId(),
		]);
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			$this->currentSender->sendForm(new CustomForm($this->plugin->getName(), [
				new Label("description", $this->getDescription()),
				new Input("roleName", "Role", "New Role Name"),
			], function (Player $player, CustomFormResponse $response): void {
				$this->setCurrentSender($player);
				$this->onRun($player, $this->getName(), [
					"roleName" => $response->getString("roleName")
				]);
			}));
		}
	}
}