<?php


namespace CortexPE\Hierarchy\cmd\subcommand;


use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\role\Role;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class GiveRoleCommand extends SubCommand {
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
		parent::__construct($name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.give");
	}

	public function execute(CommandSender $sender, array $args): void {
		if(count($args) == 2) {
			$target = $sender->getServer()->getPlayer($args[0]);
			if($target instanceof Player) {
				$role = Loader::getInstance()->getRoleManager()->getRole((int)$args[1]);
				if($role instanceof Role) {
					$member = Loader::getInstance()->getMemberFactory()->getMember($target);
					if(!$member->hasRole($role)) {
						$member->addRole($role);
					} else {
						$sender->sendMessage("Member already has the role " . $role->getName());
					}
				} else {
					$sender->sendMessage("Role not found. For a complete list of roles, please use '/role list'");
				}
			} else {
				$sender->sendMessage(TextFormat::RED . "Player is offline.");
			}
		} else {
			$sender->sendMessage("Usage: " . $this->getUsage());
		}
	}
}