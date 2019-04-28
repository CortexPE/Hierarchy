<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 5:54 AM
 */

namespace CortexPE\Hierarchy\member;


use CortexPE\Hierarchy\data\DataSource;
use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\role\Role;
use Exception;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;

class Member {
	/** @var Player */
	protected $player;
	/** @var Role[] */
	protected $roles = [];
	/** @var PermissionAttachment */
	protected $attachment;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->attachment = $player->addAttachment(Loader::getInstance());
	}

	/**
	 * @return PermissionAttachment
	 */
	public function getAttachment(): PermissionAttachment {
		return $this->attachment;
	}

	public function loadData(array $memberData): void {
		foreach($memberData["roles"] ?? [] as $roleId) {
			$this->addRoleById($roleId, false);
		}
		$this->recalculatePermissions();
	}

	public function recalculatePermissions(): void {
		$this->attachment->clearPermissions();

		$perms = [PHP_INT_MAX => Loader::getInstance()->getRoleManager()->getDefaultRole()->getPermissions()]; // default
		foreach($this->roles as $role) {
			$perms[$role->getPosition()] = $role->getPermissions();
		}
		krsort($perms);
		$perms = array_replace_recursive(...$perms);
		$this->attachment->setPermissions($perms);
	}

	public function addRoleById(int $roleId, bool $recalculate = true): void {
		$role = Loader::getInstance()->getRoleManager()->getRole($roleId);
		$this->addRole($role, $recalculate);
	}

	public function addRole(Role $role, bool $recalculate = true): void {
		if(!$this->hasRole($role)) {
			Loader::getInstance()->getDataSource()->updateMemberData($this, DataSource::ACTION_ROLE_ADD, $role->getId());
			$this->roles[$role->getId()] = $role;
			$role->bind($this);
			if($recalculate) {
				$this->recalculatePermissions();
			}

			return;
		}
		throw new Exception("Member already has the role \"" . $role->getName() . "\"");
	}

	public function hasRole(Role $role): bool {
		return in_array($role, $this->roles, true);
	}

	public function removeRole(Role $role, bool $recalculate = true): void {
		if($this->hasRole($role)) {
			$role->unbind($this);
			unset($this->roles[$role->getId()]);
			if($recalculate) {
				$this->recalculatePermissions();
			}

			return;
		}
		throw new Exception("Member does not have the role \"" . $role->getName() . "\"");
	}

	public function clearRoles(): void {
		foreach($this->roles as $id => $role) {
			$role->unbind($this);
		}
		$this->roles = [];
		$this->recalculatePermissions();
	}

	/**
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	public function getNameTagFormat(): string {
		return $this->getHighestRole()->getNameTagFormat();
	}

	public function getHighestRole(): Role {
		return $this->roles[max(array_keys($this->roles))];
	}

	public function getChatFormat(): string {
		return $this->getHighestRole()->getChatFormat();
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player {
		return $this->player;
	}

	public function getName(): string {
		return $this->player->getName();
	}

	public function getPermissions():array {
		return $this->attachment->getPermissions();
	}
}