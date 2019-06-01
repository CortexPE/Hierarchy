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