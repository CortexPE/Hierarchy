<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 5:54 AM
 */

namespace CortexPE\Hierarchy\role;


use CortexPE\Hierarchy\data\AsyncDataSource;
use CortexPE\Hierarchy\exception\UnknownPermissionNode;
use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\member\Member;
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
	/** @var string */
	protected $chatFormat;
	/** @var string */
	protected $nameTagFormat;
	/** @var bool[] */
	protected $permissions = [];
	/** @var bool */
	protected $isDefault = false;

	/** @var Member[] */
	protected $members = [];

	public function __construct(int $id, string $name, array $roleData){
		$this->id = $id;
		$this->name = $name;

		$this->position = $roleData["position"];
		$this->chatFormat = $roleData["chatFormat"];
		$this->nameTagFormat = $roleData["nameTagFormat"];
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

	/**
	 * @return string
	 */
	public function getNameTagFormat(): string{
		return $this->nameTagFormat;
	}

	/**
	 * @return string
	 */
	public function getChatFormat(): string{
		return $this->chatFormat;
	}

	public function bind(Member $member):void{
		$this->members[$member->getName()] = &$member;
	}

	public function unbind(Member $member):void{
		unset($this->members[$member->getName()]);
	}

	/**
	 * @return Member[]
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