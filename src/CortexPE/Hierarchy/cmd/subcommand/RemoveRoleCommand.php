<?php


namespace CortexPE\Hierarchy\cmd\subcommand;


use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveRoleCommand extends SubCommand {
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
		parent::__construct($name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.role.remove");
	}

	public function execute(CommandSender $sender, array $args): void {
		if(count($args) == 2) {
			$role = Loader::getInstance()->getRoleManager()->getRole((int)$args[1]);
			if($role instanceof Role) {
				$target = $args[0];
				$tmp = $sender->getServer()->getPlayer($target);
				if($tmp instanceof Player) {
					$target = $tmp;
				}

				Loader::getInstance()
					  ->getMemberFactory()
					  ->getMember($target, true, function (BaseMember $member) use ($role, $sender) {
					  	if(!$role->isDefault()) {
							if($member->hasRole($role)) {
								$member->removeRole($role);
								$sender->sendMessage(TextFormat::YELLOW . "Removed '" . $role->getName() . "' role from member");
							} else {
								$sender->sendMessage(TextFormat::RED . "Member does not have the '" . $role->getName() . "' role");
							}
						} else {
							$sender->sendMessage(TextFormat::RED . "Cannot remove default role");
						}
					  });
			} else {
				$sender->sendMessage("Role not found. For a complete list of roles, please use '/role list'");
			}
		} else {
			$sender->sendMessage("Usage: " . $this->getUsage());
		}
	}
}