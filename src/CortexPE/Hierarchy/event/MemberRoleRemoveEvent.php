<?php


namespace CortexPE\Hierarchy\event;


use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;

class MemberRoleRemoveEvent extends MemberRoleUpdateEvent {
	/** @var Role */
	protected $removed;

	public function __construct(BaseMember $member, Role $removed) {
		parent::__construct($member);
		$this->removed = $removed;
	}

	/**
	 * @return Role
	 */
	public function getRoleRemoved(): Role {
		return $this->removed;
	}
}