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

namespace CortexPE\Hierarchy\lang;


use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;

class MessageStore {
	/** @var Config */
	protected static $config;
	/** @var array */
	protected static $defaults = [
		"cmd.help_header" => "&6Available Hierarchy Role Commands:",
		"cmd.help_format" => "&b{usage} &7- &a{description}",
		"cmd.give.success" => "&aGiven '{role}' role to member",
		"cmd.give.has_role" => "&cMember already has the role '{role}'",
		"cmd.give.default" => "&cMember already has the default role",
		"cmd.list.role_header" => "&6Roles:",
		"cmd.list.role_format" => " - {role} (ID: {role_id})",
		"cmd.remove.success" => "&eRemoved '{role}' role from member",
		"cmd.remove.no_role" => "&cMember does not have the '{role}' role",
		"cmd.remove.default" => "&cCannot remove default role",
		"cmd.usr_info.header" => "&6{member}'s Role(s) and Permissions:",
		"cmd.usr_info.role_header" => "Role(s):",
		"cmd.usr_info.role_format" => " - {role}",
		"cmd.usr_info.perm_header" => "Permission(s):",
		"cmd.usr_info.perm_format" => " - {permission}",
        "cmd.permission.header" => "Permission(s):",
        "cmd.permission.true" => "&a{permission}",
        "cmd.permission.false" => "&c{permission}",

        "form.title" => "Hierarchy",
        "form.player" => "{player}",

		"err.target_higher_hrk" => "&cYou cannot use this command on {target} due to higher role hierarchy",
		"err.insufficient_permissions" => "&cYou do not have enough permissions to use this command.",
		"err.unknown_role" => "&cRole not found. For a complete list of roles, please use '/role list'",
        "err.no_players" => "&cNo one is assigned this role",
        "err.no_permissions" => "&cThis role is not assigned any permissions",
        "err.no_roles" => "&cThere are no roles setup",
        "err.console_only" => "&cThis command is console only",
        "err.player_only" => "&cThis command is player only",
	];

	public function __construct(string $filePath, int $type = Config::YAML, ?array $defaults = null) {
		if($defaults !== null) {
			static::$defaults = $defaults;
		}
		if(empty(static::$defaults)){
			throw new RuntimeException("No defaults given to message store instance for " . get_class($this));
		}
		static::$config = new Config($filePath, $type, static::$defaults);
	}

	public static function getMessage(string $dataKey, array $args = [], string $prefix = "{", string $suffix = "}"): string{
		return TextFormat::colorize(
			self::substituteString(static::getMessageRaw($dataKey), $args, $prefix, $suffix),
			"&"
		);
	}

	protected static function substituteString(string $str, array $args, string $prefix, string $suffix): string{
		foreach($args as $item => $value){
			$str = str_ireplace($prefix . $item . $suffix, $value, $str);
		}

		return $str;
	}

	public static function getMessageRaw(string $dataKey): string{
		return (string)static::$config->get($dataKey, static::$defaults[$dataKey]);
	}
}