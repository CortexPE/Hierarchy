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

namespace CortexPE\Hierarchy\command;


use CortexPE\Commando\BaseSubCommand;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\lang\MessageStore;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\member\MemberFactory;
use CortexPE\Hierarchy\role\Role;
use CortexPE\Hierarchy\role\RoleManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

abstract class HierarchySubCommand extends BaseSubCommand {
	/** @var Hierarchy */
	protected $plugin;
	/** @var RoleManager */
	protected $roleManager;
	/** @var MemberFactory */
	protected $memberFactory;
	/** @var bool */
	protected $opBypass;

	public function __construct(Hierarchy $plugin, string $name, string $description = "", array $aliases = []) {
		parent::__construct($name, $description, $aliases);
		$this->plugin = $plugin;
		$this->roleManager = $plugin->getRoleManager();
		$this->memberFactory = $plugin->getMemberFactory();
		$this->opBypass = $plugin->isSuperAdminOPs();
	}

	protected function sendFormattedMessage(string $key, array $arguments = []): void {
		$this->currentSender->sendMessage(MessageStore::getMessage($key, $arguments));
	}

	protected function isSenderInGame(): bool {
		return $this->currentSender instanceof Player;
	}

	/**
	 * @param BaseMember|Role $target
	 * @param Permission|string|null $permission
	 *
	 * @return bool
	 */
	protected function isSenderHigher(BaseMember|Role $target, Permission|string|null $permission = null): bool {
		if(!$this->isSenderInGame()) {
			return true;
		}
		if($this->currentSender->hasPermission(DefaultPermissions::ROOT_OPERATOR) && $this->opBypass) {
			return true;
		}
		if($permission === null) {
			if($target instanceof BaseMember) {
				$targetPos = $target->getTopRole()->getPosition();
			} elseif($target instanceof Role) {
				$targetPos = $target->getPosition();
			} else {
				throw new \InvalidArgumentException("Passed argument is neither a Role nor a Member");
			}
			$senderPos = $this->memberFactory->getMember($this->currentSender)->getTopRole()->getPosition();
		} else {
			if($target instanceof BaseMember) {
				$targetPos = $target->getTopRoleWithPermission($permission)->getPosition();
			} else {
				throw new \InvalidArgumentException("Passed argument is not a Member");
			}
			$senderPos = $this->memberFactory->getMember($this->currentSender)
											 ->getTopRoleWithPermission($permission)
											 ->getPosition();
		}

		return $senderPos > $targetPos;
	}

	/**
	 * @param BaseMember|Role       $target
	 * @param Permission|string $permission
	 *
	 * @return bool
	 */
	protected function doHierarchyPositionCheck(BaseMember|Role $target, Permission|string|null $permission = null): bool {
		if(!($valid = $this->isSenderHigher($target, $permission))) {
			$this->sendFormattedMessage("err.target_higher_hrk", [
				"target" => $target->getName()
			]);
		}

		return $valid;
	}

	protected function getSenderMember(): BaseMember {
		return $this->memberFactory->getMember($this->currentSender);
	}

	protected function isSenderInGameNoArguments(array $args):bool {
		return $this->isSenderInGame() && empty($args);
	}

	protected function sendPermissionError():void {
		$this->currentSender->sendMessage(
			$this->currentSender->getServer()->getLanguage()->translateString(
				TextFormat::RED . "%commands.generic.permission"
			)
		);
	}

	protected function getRolesApplicable(&$roles, &$roles_i):bool {
		$roles = [];
		$roles_i = [];
		foreach($this->roleManager->getRoles() as $role) {
			if($role->isDefault() || !$this->isSenderHigher($role)) {
				continue;
			}
			$roles[] = "{$role->getName()} ({$role->getId()})";
			$roles_i[] = $role->getId();
		}
		if(empty($roles)) {
			$this->sendFormattedMessage("err.no_roles_for_action");

			return false;
		}
		return true;
	}
}