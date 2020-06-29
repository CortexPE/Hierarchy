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

namespace CortexPE\Hierarchy\task;


use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\RoleManager;
use pocketmine\permission\PermissionManager;
use pocketmine\scheduler\Task;

class InvalidRolePermissionCheckTask extends Task {
	/** @var Hierarchy */
	private $plugin;
	/** @var RoleManager */
	private $rMgr;

	public function __construct(Hierarchy $plugin) {
		$this->plugin = $plugin;
		$this->rMgr = $plugin->getRoleManager();
	}

	public function onRun():void {
		foreach($this->plugin->getServer()->getPluginManager()->getPlugins() as $plugin){
			if($plugin->isDisabled()){
				$this->plugin->getLogger()->warning("Skipping permission existence check to avoid un-necessary console spam, please fix crashed plugins first.");
				return;
			}
		}
		$pMgr = PermissionManager::getInstance();
		foreach($this->rMgr->getRoles() as $role){
			$missing = [];
			foreach($role->getPermissions() as $permission => $val) {
				if($pMgr->getPermission($permission) === null) {
					$missing[] = $permission;
					$role->removePermissionInternal($permission);
				}
			}
			if(!empty($missing)) {
				$this->plugin->getLogger()->warning("Unknown permission nodes " . implode(", ", $missing) . " on " . $role->getName() . "(" . $role->getId() . ") role");
				$role->updateMemberPermissions();
			}
		}
	}
}