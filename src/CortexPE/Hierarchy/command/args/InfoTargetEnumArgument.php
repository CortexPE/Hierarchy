<?php


namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;

class InfoTargetEnumArgument extends StringEnumArgument {
	public const TARGET_MEMBER = "member";
	public const TARGET_ROLE = "role";
	public const TARGET_ROLE_LIST = "role_list";
	protected const VALUES = [
		"member" => self::TARGET_MEMBER,
		"role" => self::TARGET_ROLE,
		"role_list" => self::TARGET_ROLE_LIST,
	];

	public function parse(string $argument, CommandSender $sender) {
		return (string)$this->getValue($argument);
	}

	public function getTypeName(): string {
		return "infoTarget";
	}
}