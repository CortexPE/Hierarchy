<?php


namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\permission\PermissionManager;
use function preg_match;

class PermissionArgument extends RawStringArgument {
	/** @var PermissionManager */
	protected $pMgr;

	public function __construct(string $name, bool $optional) {
		parent::__construct($name, $optional);
		$this->pMgr = PermissionManager::getInstance();
	}

	public function getNetworkType(): int {
		return AvailableCommandsPacket::ARG_TYPE_STRING;
	}

	public function canParse(string $testString, CommandSender $sender): bool {
		return (bool)preg_match("/^(?:\w+|\.\w+)+$/", $testString);
	}

	public function parse(string $argument, CommandSender $sender) {
		return $this->pMgr->getPermission($argument);
	}

	public function getTypeName(): string {
		return "permission";
	}
}