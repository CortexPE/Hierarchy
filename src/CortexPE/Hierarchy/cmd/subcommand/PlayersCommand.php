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
use function array_values;

class PlayersCommand extends SubCommand {
	public function __construct(
		Hierarchy $hierarchy,
		Command $parent,
		string $name,
		array $aliases,
		string $usageMessage,
		string $descriptionMessage
	) {
		parent::__construct($hierarchy, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.list_players");
	}

	public function execute(CommandSender $sender, array $args, bool $back = false, bool $previousBack = false): void {
		if(isset($args[0])) {

			$role = $this->resolveRole($sender, (int)$args[0]);

			if($role !== null) {

				$members = $role->getMembers();

				if($sender instanceof Player) {

					foreach($members as $member) {
						$options[] = new MenuOption(MessageStore::getMessage("form.player",
							["player" => $member->getName()]));
					}

					$options = $options ?? [new MenuOption(MessageStore::getMessage("err.no_players"))];

					$memberForm = new MenuForm(MessageStore::getMessage("form.title"), "Members:", $options,
						function (Player $player, int $selected) use ($role, $members, $back, $previousBack): void {
							$values = array_values($members);
							/** @var RoleCommand $parent */
							$parent = $this->getParent();
							if(isset($values[$selected])) {
								$member = $values[$selected];
								$parent->getCommand('who')->execute($player, [$member->getName()]);
							} elseif($back) /** @var RoleOptionsCommand $optionsCommand */ {
								$optionsCommand = $parent->getCommand('options');
							}
							if(isset($optionsCommand)) {
								/** @var $optionsCommand PlayersCommand */
								$optionsCommand->execute($player, [$role->getId()], $previousBack);
							}
						});

					$sender->sendForm($memberForm);

				} else {
					foreach($members as $member) {
						$sender->sendMessage(MessageStore::getMessage("form.player", ["player" => $member->getName()]));
					}
				}
			}

		} else {
			$this->sendUsage($sender);
		}
	}
}