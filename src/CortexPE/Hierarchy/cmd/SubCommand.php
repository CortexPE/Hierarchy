<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 2/16/2019
 * Time: 12:42 AM
 */

namespace CortexPE\Hierarchy\cmd;


use pocketmine\command\CommandSender;

abstract class SubCommand {

	/** @var string */
	private $name;

	/** @var array */
	private $aliases = [];

	/** @var string */
	private $usageMessage;

	/** @var string */
	private $descriptionMessage;

	/** @var string */
	private $permission = null;

	/**
	 * SubCommand constructor.
	 * @param string $name
	 * @param array $aliases
	 * @param string $usageMessage
	 * @param string $descriptionMessage
	 */
	public function __construct(string $name, array $aliases, string $usageMessage, string $descriptionMessage){
		$this->aliases = array_map("strtolower", $aliases);
		$this->name = strtolower($name);
		$this->usageMessage = $usageMessage;
		$this->descriptionMessage = $descriptionMessage;
	}

	/**
	 * @return string
	 */
	public function getName(): string{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getAliases(): array{
		return $this->aliases;
	}

	/**
	 * @return string
	 */
	public function getUsage(): string{
		return $this->usageMessage;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string{
		return $this->descriptionMessage;
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 */
	abstract public function execute(CommandSender $sender, array $args): void;

	/**
	 * @return string
	 */
	public function getPermission(): ?string{
		return $this->permission;
	}

	/**
	 * @param string $permission
	 */
	public function setPermission(string $permission): void{
		$this->permission = $permission;
	}

	public function sendUsage(CommandSender $sender):void{
		$sender->sendMessage("Usage: " . $this->getUsage());
	}
}