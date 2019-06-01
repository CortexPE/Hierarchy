<?php


namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\role\RoleManager;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class RoleArgument extends RawStringArgument {
	/** @var RoleManager */
	protected $roleMgr;

	public function __construct(string $name, bool $optional) {
		parent::__construct($name, $optional);
		/** @var Hierarchy $hrk */
		$hrk = Server::getInstance()->getPluginManager()->getPlugin("Hierarchy");
		$this->roleMgr = $hrk->getRoleManager();
	}

	public function parse(string $argument, CommandSender $sender) {
		return $this->roleMgr->getRoleByName($argument);
	}

	public function getTypeName(): string {
		return "role";
	}
}