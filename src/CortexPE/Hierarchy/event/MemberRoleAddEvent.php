<?php


namespace CortexPE\Hierarchy\event;


use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;

class MemberRoleAddEvent extends MemberRoleUpdateEvent {
	/** @var Role */
	protected $added;

	public function __construct(BaseMember $member, Role $added) {
		parent::__construct($member);
		$this->added = $added;
	}

	/**
	 * @return Role
	 */
	public function getRoleAdded(): Role {
		return $this->added;
	}
}