<?php


namespace CortexPE\Hierarchy\member;


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\event\MemberRoleAddEvent;
use CortexPE\Hierarchy\event\MemberRoleRemoveEvent;
use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;

abstract class BaseMember {
	/** @var bool[] */
	protected $permissions = [];
	/** @var Role[] */
	protected $roles = [];

	/**
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	public function loadData(array $memberData): void {
		foreach($memberData["roles"] ?? [] as $roleId) {
			$this->addRoleById($roleId, false);
		}
		$this->recalculatePermissions();
	}

	public function addRoleById(int $roleId, bool $recalculate = true): void {
		$role = Loader::getInstance()->getRoleManager()->getRole($roleId);
		$this->addRole($role, $recalculate);
	}

	public function addRole(Role $role, bool $recalculate = true): void {
		if(!$this->hasRole($role)) {
			$ev = new MemberRoleAddEvent($this, $role);
			$ev->call();
			if(!$ev->isCancelled()) {
				Loader::getInstance()
					  ->getDataSource()
					  ->updateMemberData($this, DataSource::ACTION_ROLE_ADD, $role->getId());
				$this->roles[$role->getId()] = $role;
				$role->bind($this);
				if($recalculate) {
					$this->recalculatePermissions();
				}
			}

			return;
		}
	}

	public function hasRole(Role $role): bool {
		return isset($this->roles[$role->getId()]);
	}

	public function removeRole(Role $role, bool $recalculate = true): void {
		if($this->hasRole($role)) {
			$ev = new MemberRoleRemoveEvent($this, $role);
			$ev->call();
			if(!$ev->isCancelled()) {
				unset($this->roles[$role->getId()]);
				Loader::getInstance()
					  ->getDataSource()
					  ->updateMemberData($this, DataSource::ACTION_ROLE_REMOVE, $role->getId());
				$role->unbind($this);
				if($recalculate) {
					$this->recalculatePermissions();
				}
			}
		}
	}

	public function clearRoles(bool $recalculate = true): void {
		foreach($this->roles as $role) {
			$this->removeRole($role, false);
		}
		$this->roles = [];
		if($recalculate) {
			$this->recalculatePermissions();
		}
	}

	public function recalculatePermissions(): void {
		$this->permissions = [];
		$perms = [
			PHP_INT_MAX => Loader::getInstance()
								 ->getRoleManager()
								 ->getDefaultRole()
								 ->getPermissions()
		]; // default
		foreach($this->roles as $role) {
			$perms[$role->getPosition()] = $role->getPermissions();
		}
		krsort($perms);
		$this->permissions = array_replace_recursive(...$perms);
	}

	public function getTopRole(): Role {
		return $this->roles[max(array_keys($this->roles))];
	}

	/**
	 * @return bool[]
	 */
	public function getPermissions(): array {
		return $this->permissions;
	}

	/**
	 * @param Permission|string $permissionNode
	 *
	 * @return Role|null
	 */
	public function getTopRoleWithPermission($permissionNode): ?Role {
		if($permissionNode instanceof Permission) {
			$permissionNode = $permissionNode->getName();
		}
		$topRolePosition = PHP_INT_MIN;
		$topRoleWithPerm = null;
		foreach($this->roles as $role) {
			if(
				isset(($role->getPermissions())[$permissionNode]) &&
				$role->getPosition() > $topRolePosition
			) {
				$topRolePosition = $role->getPosition();
				$topRoleWithPerm = $role;
			}
		}

		return $topRoleWithPerm;
	}

	/**
	 * @param Permission|string $permissionNode
	 * @param BaseMember        $target
	 *
	 * @return bool
	 */
	public function hasHigherPermissionHierarchy($permissionNode, BaseMember $target): bool {
		if($permissionNode instanceof Permission) {
			$permissionNode = $permissionNode->getName();
		}
		$myTopRole = $this->getTopRoleWithPermission($permissionNode);
		if($myTopRole instanceof Role) {
			$targetTopRole = $target->getTopRoleWithPermission($permissionNode);
			if($targetTopRole instanceof Role) {
				return $myTopRole->getPosition() > $targetTopRole->getPosition();
			}

			return true;
		}

		return false;
	}

	abstract public function getAttachment(): ?PermissionAttachment;

	abstract public function getPlayer(): ?Player;

	abstract public function getName(): string;
}