<?php


namespace CortexPE\Hierarchy\cmd\subcommand;


use CortexPE\Hierarchy\cmd\SubCommand;
use CortexPE\Hierarchy\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class UserInfoCommand extends SubCommand {
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage) {
		parent::__construct($name, $aliases, $usageMessage, $descriptionMessage);
		$this->setPermission("hierarchy.user_info");
	}

	public function execute(CommandSender $sender, array $args): void {
		$target = $sender->getServer()->getPlayer($args[0]);
		if($target instanceof Player){
			$member = Loader::getInstance()->getMemberFactory()->getMember($target);
			$roles = $member->getRoles();
			$permissions = $member->getPermissions();
			$sender->sendMessage(TextFormat::GOLD . $member->getName() . "'s Role(s) and Permissions:");
			if(!empty($roles)){
				$sender->sendMessage("Role(s):");
				foreach($roles as $role){
					$sender->sendMessage(" - " . $role->getName());
				}
			}
			if(!empty($permissions)){
				$sender->sendMessage("Permission(s):");
				foreach($permissions as $permission => $allowed){
					$sender->sendMessage(" - " . ($allowed ? TextFormat::GREEN : TextFormat::RED) . $permission);
				}
			}
		} else {
			$sender->sendMessage(TextFormat::RED . "Player is offline.");
		}
	}
}