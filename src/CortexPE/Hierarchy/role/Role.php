<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 5:54 AM
 */

namespace CortexPE\Hierarchy\role;


use CortexPE\Hierarchy\exception\UnknownPermissionNode;
use CortexPE\Hierarchy\member\BaseMember;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

class Role {
	public const PERM_TYPE_ADD = 0;
	public const PERM_TYPE_REMOVE = 1;

	/** @var int */
	protected $id;
	/** @var string */
	protected $name;

	/** @var int */
	protected $position;
	/** @var bool[] */
	protected $permissions = [];
	/** @var bool */
	protected $isDefault = false;

	/** @var BaseMember[] */
	protected $members = [];

	public function __construct(int $id, string $name, array $roleData){
		$this->id = $id;
		$this->name = $name;

		$this->position = $roleData["position"];
		$this->isDefault = $roleData["isDefault"];
		$pMgr = PermissionManager::getInstance();
		foreach($roleData["permissions"] ?? [] as $permission){
			if($permission == "*"){
				foreach($pMgr->getPermissions() as $perm)
				{
					$this->permissions[$perm->getName()] = true;
				}
				continue;
			}

			$invert = ($permission{0} == "-");
			$perm = $pMgr->getPermission(!$invert ? $permission : substr($permission, 1));
			if($perm instanceof Permission){
				$this->permissions[$perm->getName()] = !$invert;
			} else {
				throw new UnknownPermissionNode("Unknown permission node '" . $permission . "' on " . $name . " role");
			}
		}
	}

	/**
	 * @return int
	 */
	public function getId(): int{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string{
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
	public function getPermissions(): array{
		return $this->permissions;
	}

	public function bind(BaseMember $member):void{
		$this->members[$member->getName()] = &$member;
	}

	public function unbind(BaseMember $member):void{
		unset($this->members[$member->getName()]);
	}

	/**
	 * @return BaseMember[]
	 */
	public function getMembers(): array{
		return $this->members;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool {
		return $this->isDefault;
	}
}