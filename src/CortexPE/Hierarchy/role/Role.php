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

namespace CortexPE\Hierarchy\role;


use CortexPE\Hierarchy\exception\HierarchyException;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\member\Member;
use CortexPE\Hierarchy\member\OfflineMember;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function substr;

class Role {
	/** @var Hierarchy */
	protected $plugin;

	/** @var int */
	protected $id;
	/** @var string */
	protected $name;

	/** @var int */
	protected $position;
	/** @var bool[] */
	protected $permissions = [];
	/** @var bool[] */
	protected $extraPermissions = [];
	/** @var bool */
	protected $isDefault = false;

	/** @var Member[] */
	protected $onlineMembers = [];
	/** @var Role[] */
	protected $parents = [];
	/** @var Role[] */
	protected $children = [];
	/** @var bool[] */
	protected $combinedPermissions;

	/** @var bool */
	protected $hasAllPermissions = false;

	public function __construct(Hierarchy $plugin, int $id, string $name, array $roleData) {
		$this->plugin = $plugin;
		$this->id = $id;
		$this->name = $name;
		$this->position = $roleData["position"];
		$this->isDefault = (bool)($roleData["isDefault"] ?? false);
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getPosition(): int {
		return $this->position;
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions(): array {
		return $this->permissions;
	}

	public function bind(BaseMember $member): void {
		$this->onlineMembers[strtolower($member->getName())] = $member;
	}

	public function unbind(BaseMember $member): void {
		unset($this->onlineMembers[strtolower($member->getName())]);
	}

	/**
	 * @return Member[]
	 */
	public function getOnlineMembers(): array {
		return $this->onlineMembers;
	}

	/**
	 * Triggers the callback with a list of OfflineMember objects
	 *
	 * @param callable $callback
	 * @throws HierarchyException
	 */
	public function getOfflineMembers(callable $callback): void {
		Utils::validateCallableSignature(function(array $members):void{}, $callback);
		if($this->isDefault){ // basically everyone who joined the server and will ever join the server
			throw new HierarchyException("Cannot get offline members of default role");
		}
		Await::f2c(function() use ($callback):\Generator{
			$members = [];
			foreach(yield $this->plugin->getMemberDataSource()->getMemberNamesOf($this) as $row){
				$n = strtolower($row["Player"]);
				if(isset($this->onlineMembers[$n])){
					continue;
				}
				if(isset($members[$n])){
					continue;
				}
				$members[$n] = new OfflineMember($this->plugin, $row["Player"]);
			}
			$callback($members);
		}, null, function(\Throwable $e):void{
			$this->plugin->getLogger()->logException($e);
		});
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool {
		return $this->isDefault;
	}

	/**
	 * @param Permission $permission
	 * @param bool       $update
	 */
	public function addPermission(Permission $permission, bool $update = true): void {
		$this->permissions[$permission->getName()] = true;
		$this->recalculateCombinedPermissions();
		$this->plugin->getRoleDataSource()->addRolePermission($this, $permission, false);
		if($update) {
			$this->updateChildrenPermissions();
			$this->updateMemberPermissions();
		}
	}

	public function denyPermission(Permission $permission, bool $update = true): void {
		$this->permissions[$permission->getName()] = false;
		$this->recalculateCombinedPermissions();
		$this->plugin->getRoleDataSource()->addRolePermission($this, $permission, true);
		if($update) {
			$this->updateChildrenPermissions();
			$this->updateMemberPermissions();
		}
	}

	/**
	 * @param Permission|string $permission
	 * @param bool              $update
	 */
	public function removePermission($permission, bool $update = true): void {
		if($permission instanceof Permission) {
			$permission = $permission->getName();
		}
		$this->removePermissionInternal($permission);
		$this->plugin->getRoleDataSource()->removeRolePermission($this, $permission);
		if($update) {
			$this->updateMemberPermissions();
		}
	}

	/**
	 * @internal
	 *
	 * @param string $permission
	 */
	public function removePermissionInternal(string $permission): void {
		unset($this->permissions[$permission]);
		$this->recalculateCombinedPermissions();
		$this->updateChildrenPermissions();
	}

	public function updateMemberPermissions(): void {
		foreach($this->onlineMembers as $member) {
			$member->recalculatePermissions();
		}
	}

	/**
	 * @internal Adds 1 to the role position to make way for a newly created role
	 */
	public function bumpPosition(): void {
		$this->position++;
		$this->updateMemberPermissions();
	}

	/**
	 * @internal Used internally for loading permissions from an inherited role
	 *
	 * @param Role $role
	 */
	public function inheritRole(Role $role):void {
		$this->parents[$role->getId()] = $role;
		$role->addChild($this);
	}

	/**
	 * @internal Used internally for loading permissions from an inherited role
	 *
	 * @param Role $role
	 */
	public function unInheritRole(Role $role):void {
		unset($this->parents[$role->getId()]);
		$role->removeChild($this);
		$this->refreshInheritedPermissions();
		$this->recalculateCombinedPermissions();
	}

	/**
	 * @internal Used internally for loading permissions from storage
	 *
	 * @param array $permissions
	 */
	public function loadPermissions(array $permissions):void {
		$pMgr = PermissionManager::getInstance();
		foreach($permissions ?? [] as $permission) {
			if($permission == "*") {
				$this->hasAllPermissions = true;
				foreach($pMgr->getPermissions() as $perm){
					$this->permissions[$perm->getName()] = true;
				}
				continue;
			}
			$value = true;
			if(substr($permission, 0, 1) === "-"){
				$value = false;
				$permission = substr($permission, 1);
			}
			$this->permissions[$permission] = $value;
		}
		$this->refreshInheritedPermissions();
		$this->recalculateCombinedPermissions();
	}

	public function updateChildrenPermissions(): void {
		foreach($this->children as $role) {
			$role->refreshInheritedPermissions();
		}
	}

	public function refreshInheritedPermissions():void {
		$this->extraPermissions = [];
		foreach($this->parents as $role){
			foreach($role->getPermissions() as $permission => $toggle){
				$this->extraPermissions[$permission] = $toggle;
			}
		}
	}

	/**
	 * @param Role $child
	 * @internal Used for keeping references to roles inheriting its permissions
	 */
	public function addChild(Role $child):void {
		$this->children[$child->getId()] = $child;
	}

	/**
	 * @param Role $child
	 * @internal Used for removing references to roles inheriting its permissions
	 */
	public function removeChild(Role $child):void {
		unset($this->children[$child->getId()]);
	}

	/**
	 * @internal Used for getting roles inheriting its permissions
	 */
	public function getChildren(): array {
		return $this->children;
	}

	/**
	 * @return Role[]
	 */
	public function getParents(): array {
		return $this->parents;
	}

	/**
	 * @return bool[]
	 */
	public function getExtraPermissions(): array {
		return $this->extraPermissions;
	}

	private function recalculateCombinedPermissions(): void {
		$this->combinedPermissions = array_replace_recursive($this->getExtraPermissions(), $this->getPermissions());
	}

	/**
	 * @return bool[]
	 */
	public function getCombinedPermissions(): array {
		return $this->combinedPermissions;
	}

	/**
	 * @return bool
	 */
	public function hasAllPermissions(): bool {
		return $this->hasAllPermissions;
	}
}