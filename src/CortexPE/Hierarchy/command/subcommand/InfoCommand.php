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
use CortexPE\Hierarchy\command\args\InfoTargetEnumArgument;
use CortexPE\Hierarchy\command\args\MemberArgument;
use CortexPE\Hierarchy\command\args\RoleArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\lang\MessageStore;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\command\CommandSender;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_map;
use function count;
use function implode;

class InfoCommand extends HierarchySubCommand implements FormedCommand {
	/** @var array */
	private $opts;

	public function __construct(Hierarchy $plugin, string $name, string $description = "", array $aliases = []) {
		parent::__construct($plugin, $name, $description, $aliases);
		$roles = [];
		$roles_i = [];
		foreach($this->roleManager->getRoles() as $role) {
			$roles[] = "{$role->getName()} ({$role->getId()})";
			$roles_i[] = $role->getId();
		}
		$this->opts = [
			[
				new MenuOption("Member"),
				InfoTargetEnumArgument::TARGET_MEMBER,
				[
					new Label("instruction", MessageStore::getMessage("cmd.info.member_form.instruction")),
					new Input("member", MessageStore::getMessage("cmd.info.member_form.opt_text"))
				],
				function(Player $player, CustomFormResponse $response): void {
					$this->setCurrentSender($player);
					$this->onRun($player, $this->getName(), [
						"targetType" => InfoTargetEnumArgument::TARGET_MEMBER,
						"targetMember" => [$this->memberFactory->getMember($response->getString("member"))]
					]);
				}
			],
			[
				new MenuOption("Role"),
				InfoTargetEnumArgument::TARGET_ROLE,
				[
					new Label("instruction", MessageStore::getMessage("cmd.info.role_form.instruction")),
					new Dropdown(
						"roles",
						MessageStore::getMessage("cmd.info.role_form.opt_text"),
						$roles
					)
				],
				function(Player $player, CustomFormResponse $response) use ($roles, $roles_i): void {
					$this->setCurrentSender($player);
					$this->onRun($player, $this->getName(), [
						"targetType" => InfoTargetEnumArgument::TARGET_ROLE,
						"targetRole" => [$this->roleManager->getRole($roles_i[$response->getInt("roles")])]
					]);
				}
			],
			[
				new MenuOption("Role List"),
				InfoTargetEnumArgument::TARGET_ROLE_LIST,
				null,
				null
			],
			[
				new MenuOption("Permission List"),
				InfoTargetEnumArgument::TARGET_PERM_LIST,
				null,
				null
			],
		];
	}

	protected function prepare(): void {
		$this->registerArgument(0, new InfoTargetEnumArgument("targetType", true));
		$this->registerArgument(1, new RoleArgument("targetRole", true));
		$this->registerArgument(1, new MemberArgument("targetMember", true));
		$this->setPermission(implode(";", [
			"hierarchy",
			"hierarchy.info",
			"hierarchy.info.list_roles",
			"hierarchy.info.list_perms",
			"hierarchy.info.member",
			"hierarchy.info.role"
		]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 1) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}

		if($args["targetType"] === InfoTargetEnumArgument::TARGET_MEMBER && isset($args["targetMember"])) {
			if($sender->hasPermission("hierarchy.info.member")) {
				/** @var BaseMember $target */
				$target = $args["targetMember"];
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
				if(count($target->getMemberPermissions()) > 0) {
					foreach($target->getMemberPermissions() as $permission => $allowed) {
						$this->sendFormattedMessage("cmd.info.member.m_perm_entry", [
							"permission" => $permission,
							"color" => $allowed ? TextFormat::GREEN : TextFormat::RED . "-"
						]);
					}
				} else {
					$this->sendFormattedMessage("cmd.info.member.no_extra_perms");
				}
			} else {
				$this->sendPermissionError();
			}
		} elseif($args["targetType"] === InfoTargetEnumArgument::TARGET_ROLE && isset($args["targetRole"])) {
			if($sender->hasPermission("hierarchy.info.role")) {
				/** @var Role $target */
				$target = $args["targetRole"];
				$lines = [];
				$lines[] = MessageStore::getMessage("cmd.info.role.header", [
					"role" => $target->getName(),
					"role_id" => $target->getId()
				]);
				$lines[] = MessageStore::getMessage("cmd.info.role.position", [
					"position" => $target->getPosition()
				]);
				$lines[] = MessageStore::getMessage("cmd.info.role.default", [
					"isDefault" => $target->isDefault() ? TextFormat::GREEN . "YES" : TextFormat::RED . "NO"
				]);
				$lines[] = MessageStore::getMessage("cmd.info.role.perms_header");
				foreach($target->getCombinedPermissions() as $permission => $allowed) {
					$lines[] = MessageStore::getMessage("cmd.info.role.perm_entry", [
						"permission" => $permission,
						"color" => $allowed ? TextFormat::GREEN : TextFormat::RED . "-"
					]);
				}
				if(!$target->isDefault()) {
					$lines[] = MessageStore::getMessage("cmd.info.role.members_header", [
						"count" => ($c = count($target->getOnlineMembers()))
					]);
					if($c > 0) {
						foreach($target->getOnlineMembers() as $member) {
							$lines[] = MessageStore::getMessage("cmd.info.role.member_entry", [
								"member" => $member->getName()
							]);
						}
					} else {
						$lines[] = MessageStore::getMessage("cmd.info.role.no_online_members");
					}
					$target->getOfflineMembers(function(array $members) use ($lines, $sender): void {
						$lines[] = MessageStore::getMessage("cmd.info.role.offline_members_header", [
							"count" => ($c = count($members))
						]);
						if($c > 0) {
							foreach($members as $member) {
								$lines[] = MessageStore::getMessage("cmd.info.role.offline_member_entry", [
									"member" => $member->getName()
								]);
							}
						} else {
							$lines[] = MessageStore::getMessage("cmd.info.role.no_offline_members");
						}
						foreach($lines as $line) {
							$sender->sendMessage($line);
						}
					});
				} else {
					foreach($lines as $line) {
						$sender->sendMessage($line);
					}
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
		} elseif($args["targetType"] === InfoTargetEnumArgument::TARGET_PERM_LIST) {
			$list = PermissionManager::getInstance()->getPermissions();
			$this->sendFormattedMessage("cmd.info.perm_list.header", [
				"count" => ($c = count($list))
			]);
			if($c > 0) {
				foreach($list as $perm) {
					$this->sendFormattedMessage("cmd.info.perm_list.entry", [
						"permission" => $perm->getName(),
					]);
				}
			} else {
				$this->sendFormattedMessage("err.no_roles");
			}
		} else {
			$this->sendUsage();
		}
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			$this->currentSender->sendForm(
				new MenuForm(
					$this->plugin->getName(),
					MessageStore::getMessage("cmd.info.menu_form.description"),
					array_map(function(array $opt): MenuOption {
						return $opt[0];
					}, $this->opts),
					function(Player $player, int $selectedOption): void {
						if($this->opts[$selectedOption][2] !== null) {
							$player->sendForm(
								new CustomForm(
									$this->plugin->getName(),
									$this->opts[$selectedOption][2],
									$this->opts[$selectedOption][3]
								)
							);
						} else {
							$this->setCurrentSender($player);
							$this->onRun($player, $this->getName(), [
								"targetType" => $this->opts[$selectedOption][1]
							]);
						}
					}
				)
			);
		}
	}
}