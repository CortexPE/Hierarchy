<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 3/31/2019
 * Time: 6:44 AM
 */

namespace CortexPE\Hierarchy\role;


use CortexPE\Hierarchy\exception\RoleCollissionError;
use CortexPE\Hierarchy\exception\UnknownPermissionNode;
use CortexPE\Hierarchy\Loader;
use pocketmine\scheduler\ClosureTask;
use RuntimeException;

class RoleManager {
	/** @var Loader */
	protected $plugin;
	/** @var Role[] */
	protected $roles = [];
	/** @var Role */
	protected $defaultRole = null;

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @internal Used to load role data from a data source
	 *
	 * @param array $roles
	 * @throws RoleCollissionError
	 * @throws UnknownPermissionNode
	 */
	public function loadRoles(array $roles) {
		foreach($roles as $roleData){
			$role = new Role($roleData["ID"], $roleData["Name"], [
				"chatFormat" => $roleData["ChatFormat"],
				"nameTagFormat" => $roleData["NameTagFormat"],
				"permissions" => $roleData["Permissions"] ?? [], // permissions can be empty
				"position" => $roleData["Position"],
				"isDefault" => $roleData["isDefault"]
			]);
			if(!isset($this->roles[$roleData["ID"]])){
				$this->roles[$roleData["ID"]] = $role;
			} else {
				throw new RoleCollissionError("Role '{$role->getName()}'({$role->getId()}) has a colliding ID");
			}
			if($roleData["isDefault"]) {
				if($this->defaultRole === null) {
					$this->defaultRole = $role;
				} else {
					throw new RoleCollissionError("There can only be one default role");
				}
			}
		}
		if(!($this->defaultRole instanceof Role)){
			throw new RuntimeException("No default role is set");
		}
		$this->plugin->getLogger()->info("Loaded " . count($this->roles) . " roles");
	}

	public function getRole(int $id):?Role{
		return $this->roles[$id] ?? null;
	}

	/**
	 * @return Role
	 */
	public function getDefaultRole(): Role {
		return $this->defaultRole;
	}

	/**
	 * @return Role[]
	 */
	public function getRoles(): array {
		return $this->roles;
	}
}