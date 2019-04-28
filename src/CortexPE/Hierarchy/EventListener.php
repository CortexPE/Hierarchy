<?php
/**
 * Created by PhpStorm.
 * User: CortexPE
 * Date: 4/1/2019
 * Time: 4:46 AM
 */

namespace CortexPE\Hierarchy;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {
	/** @var Loader */
	protected $plugin;

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerJoinEvent $ev
	 *
	 * @priority LOWEST
	 */
	public function onJoin(PlayerJoinEvent $ev){
		$this->plugin->getMemberFactory()->createSession($ev->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $ev
	 *
	 * @priority LOWEST
	 */
	public function onLeave(PlayerQuitEvent $ev){
		$this->plugin->getMemberFactory()->destroySession($ev->getPlayer());
	}
}