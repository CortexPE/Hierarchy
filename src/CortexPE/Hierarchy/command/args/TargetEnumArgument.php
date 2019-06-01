<?php


namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class TargetEnumArgument extends StringEnumArgument {
	public const TARGET_MEMBER = "member";
	public const TARGET_ROLE = "role";
	protected const VALUES = [
		"member" => self::TARGET_MEMBER,
		"role" => self::TARGET_ROLE,
	];

	public function parse(string $argument, CommandSender $sender) {
		return (string)$this->getValue($argument);
	}

	public function getTypeName(): string {
		return "target";
	}
}