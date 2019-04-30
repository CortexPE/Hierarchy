<?php


namespace CortexPE\Hierarchy\event;


use CortexPE\Hierarchy\member\BaseMember;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;

class MemberRoleUpdateEvent extends Event implements Cancellable {
	/** @var BaseMember */
	protected $member;

	public function __construct(BaseMember $member) {
		$this->member = $member;
	}

	/**
	 * @return BaseMember
	 */
	public function getMember(): BaseMember {
		return $this->member;
	}
}