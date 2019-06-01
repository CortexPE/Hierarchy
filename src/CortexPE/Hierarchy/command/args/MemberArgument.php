<?php


namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\BaseArgument;
use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\MemberFactory;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\Server;
use function preg_match;

class MemberArgument extends BaseArgument {
	/** @var MemberFactory */
	protected $mFac;

	public function __construct(string $name, bool $optional) {
		parent::__construct($name, $optional);
		/** @var Hierarchy $hrk */
		$hrk = Server::getInstance()->getPluginManager()->getPlugin("Hierarchy");
		$this->mFac = $hrk->getMemberFactory();
	}

	public function getNetworkType(): int {
		return AvailableCommandsPacket::ARG_TYPE_TARGET;
	}

	public function canParse(string $testString, CommandSender $sender): bool {
		// PM player username validity regex
		return (bool)preg_match("/^(?!rcon|console)[a-zA-Z0-9_ ]{1,16}$/i", $testString);
	}

	public function parse(string $argument, CommandSender $sender) {
		return $this->mFac->getMember($argument);
	}

	public function getTypeName(): string {
		return "member";
	}
}