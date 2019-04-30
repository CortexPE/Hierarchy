<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 2/16/2019
 * Time: 12:38 AM
 */

namespace CortexPE\Hierarchy\cmd;


use CortexPE\Hierarchy\cmd\subcommand\GiveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\ListCommand;
use CortexPE\Hierarchy\cmd\subcommand\RemoveRoleCommand;
use CortexPE\Hierarchy\cmd\subcommand\UserInfoCommand;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RoleCommand extends Command {

	/** @var SubCommand[] */
	private $subCommands = [];

	public function __construct(string $name, string $description) {
		parent::__construct($name, $description);

		$this->registerCommand(new GiveRoleCommand("give", ["add"], "/role give <player> <roleID>", "Give role to player"));
		$this->registerCommand(new UserInfoCommand("who", [], "/role who <player>", "Check user info"));
		$this->registerCommand(new ListCommand("list", [], "/role list", "Lists all roles"));
		$this->registerCommand(new RemoveRoleCommand("remove", [], "/role remove <player> <roleID>", "Remove role from player"));
	}

	/**
	 * @return SubCommand[]
	 */
	public function getCommands(): array {
		return $this->subCommands;
	}

	/**
	 * @param SubCommand $command
	 */
	public function registerCommand(SubCommand $command) {
		$this->subCommands[] = $command;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 */
	final public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if(isset($args[0]) && $this->getCommand($args[0]) != null) {
			$cmd = $this->getCommand(array_shift($args));
			if(($perm = $cmd->getPermission()) !== null && !$sender->hasPermission($perm)) {
				$sender->sendMessage(TextFormat::RED . "You do not have permissions to use this command.");

				return;
			}
			$cmd->execute($sender, $args);
		} else {
			$sender->sendMessage(TextFormat::GOLD . "Available Hierarchy Role Commands:");
			foreach($this->subCommands as $subCommand) {
				$sender->sendMessage(TextFormat::AQUA . $subCommand->getUsage() . TextFormat::GRAY . " - " . TextFormat::GREEN . $subCommand->getDescription());
			}
		}
	}

	/**
	 * @param string $alias
	 *
	 * @return null|SubCommand
	 */
	public function getCommand(string $alias): ?SubCommand {
		foreach($this->subCommands as $key => $command) {
			if(in_array(strtolower($alias), $command->getAliases()) or $alias == $command->getName()) {
				return $command;
			}
		}

		return null;
	}
}