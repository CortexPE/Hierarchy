<?php


namespace CortexPE\Hierarchy\data;


use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\member\BaseMember;

abstract class DataSource {
	public const ACTION_ROLE_ADD = "role.add";
	public const ACTION_ROLE_REMOVE = "role.remove";

	/** @var Loader */
	protected $plugin;

	public function __construct(Loader $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return Loader
	 */
	public function getPlugin(): Loader {
		return $this->plugin;
	}

	/**
	 * @internal Get member data from the data source then pass to member object
	 *
	 * @param BaseMember $member
	 * @param callable $onLoad
	 */
	abstract public function loadMemberData(BaseMember $member, ?callable $onLoad = null): void;

	/**
	 * @internal Update member data on data source
	 *
	 * @param BaseMember $member
	 * @param string $action
	 * @param mixed  $data
	 */
	abstract public function updateMemberData(BaseMember $member, string $action, $data): void;

	/**
	 * Gracefully shutdown the data source
	 */
	abstract public function shutdown(): void;
}