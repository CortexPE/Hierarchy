<?php

/***
 *        __  ___                           __
 *       / / / (_)__  _________ ___________/ /_  __  __
 *      / /_/ / / _ \/ ___/ __ `/ ___/ ___/ __ \/ / / /
 *     / __  / /  __/ /  / /_/ / /  / /__/ / / / /_/ /
 *    /_/ /_/_/\___/_/   \__,_/_/   \___/_/ /_/\__, /
 *                                            /____/
 *
 * Hierarchy - Role-based permission management system
 * Copyright (C) 2019-Present CortexPE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace CortexPE\Hierarchy\command\args;


use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\permission\PermissionManager;
use function preg_match;

class PermissionArgument extends RawStringArgument {
	/** @var PermissionManager */
	protected $pMgr;

	public function __construct(string $name, bool $optional) {
		parent::__construct($name, $optional);
		$this->pMgr = PermissionManager::getInstance();
	}

	public function getNetworkType(): int {
		return AvailableCommandsPacket::ARG_TYPE_STRING;
	}

	public function canParse(string $testString, CommandSender $sender): bool {
		return (bool)preg_match("/^(?:\w+|\.\w+)+$/", $testString);
	}

	public function parse(string $argument, CommandSender $sender) {
		return $this->pMgr->getPermission($argument);
	}

	public function getTypeName(): string {
		return "permission";
	}
}