<?php


namespace CortexPE\Hierarchy\cmd\subcommand;


use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Loader;
use pocketmine\command\CommandSender;

class ListCommand extends SubCommand {
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
		parent::__construct($name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.list_roles");
	}

	public function execute(CommandSender $sender, array $args): void {
		$sender->sendMessage("Roles:");
		$roles = Loader::getInstance()->getRoleManager()->getRoles();
		foreach($roles as $roleID => $role){
			$sender->sendMessage(" - {$role->getName()} (ID: {$roleID})");
		}
	}
}