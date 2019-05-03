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

class RoleOptionsCommand extends SubCommand
{
    private const PERMISSIONS = 0;
    private const PLAYERS = 1;
    private const BACK = 2;

    public function __construct(Hierarchy $plugin, Command $parent, string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
        parent::__construct($plugin, $parent, $name, $aliases, $usageMessage, $descriptionMessage);
        $this->setPermission("hierarchy.role.options");
    }

    public function execute(CommandSender $sender, array $args, bool $back = false): void {
        if(isset($args[0])) {
            $role = $this->resolveRole($sender, (int)$args[0]);
                if($role !== null) {
                    if($sender instanceof Player) {
                        $options = [
                            new MenuOption("Permissions"),
                            new MenuOption("Players"),
                        ];

                        if($back)
                            $options[] = new MenuOption("Back");

                        $optionForm = new MenuForm(MessageStore::getMessage("form.title"), "Options", $options, function (Player $player, int $selected) use ($role, $back): void {
                            /** @var RoleCommand $parent */
                            $parent = $this->getParent();
                            switch ($selected) {
                                case self::PERMISSIONS:
                                    /** @var ListPermissionsCommand $rolePermissionCommand */
                                    $rolePermissionCommand = $parent->getCommand("roleperm");
                                    $rolePermissionCommand->execute($player, [$role->getId()], true, $back);
                                    break;
                                case self::PLAYERS:
                                    /** @var PlayersCommand $playersCommand */
                                    $playersCommand = $parent->getCommand("players");
                                    $playersCommand->execute($player, [$role->getId()], true, $back);
                                    break;
                                case self::BACK:
                                    $parent->getCommand("list")->execute($player, []);
                            }
                        });

                        $sender->sendForm($optionForm);

                } else
                    $sender->sendMessage(MessageStore::getMessage("err.unknown_role"));
            } else
                $sender->sendMessage(MessageStore::getMessage("err.player_only"));
        } else
            $this->sendUsage($sender);
    }
}