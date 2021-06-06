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
		"cmd.createrole.success" => "&aSuccessfully created role with name '{role}' with the ID: {role_id}",

		"cmd.deleterole.fail_role_default" => "&eYou cannot delete the default role.",
		"cmd.deleterole.success" => "&eSuccessfully deleted {role} ({role_id}) role",

		"cmd.denyperm.form.choose_type" => "Please choose the target type you'd like to deny permissions from, from the options below",
		"cmd.denyperm.form.type_role" => "Role",
		"cmd.denyperm.form.type_member" => "Member",
		"cmd.denyperm.role.success" => "&eSuccessfully denied the permission '{permission}' from Role {role}({role_id})",
		"cmd.denyperm.member.success" => "&eSuccessfully denied the permission '{permission}' from {member}",

		"cmd.flush.success" => "&aSuccessfully flushed role data to disk",

		"cmd.giverole.success" => "&aGiven '{role}' role to {member}",
		"cmd.giverole.has_role" => "&c{member} already has the role '{role}'",
		"cmd.giverole.default" => "&c{member} already has the default role",

		"cmd.grantperm.form.choose_type" => "Please choose the target type you'd like to grant permissions to from below",
		"cmd.grantperm.form.type_role" => "Role",
		"cmd.grantperm.form.type_member" => "Member",
		"cmd.grantperm.role.success" => "&aSuccessfully added the permission '{permission}' to Role {role}({role_id})",
		"cmd.grantperm.member.success" => "&aSuccessfully added the permission '{permission}' to {member}",

		"cmd.info.menu_form.description" => "Choose target:",
		"cmd.info.member_form.instruction" => "Enter member to get info from:",
		"cmd.info.member_form.opt_text" => "Member Name",
		"cmd.info.role_form.instruction" => "Select role to get info from:",
		"cmd.info.role_form.opt_text" => "Select Role",

		"cmd.info.member.header" => "&9 ----- Member Info for '&b{member}&9' ----- ",
		"cmd.info.member.roles_header" => "&e&lRoles:",
		"cmd.info.member.role_entry" => "&e - &6{role} ({role_id})",
		"cmd.info.member.m_perms_header" => "&e&lMember Permission Overrides:",
		"cmd.info.member.m_perm_entry" => "&e - {color}{permission}",
        "cmd.info.member.no_extra_perms" => "&e - &cNo extra member permissions",

		"cmd.info.role.header" => "&9 ----- Role Info for &b{role} ({role_id})&9 ----- ",
		"cmd.info.role.position" => "&e&lPosition: &r&6{position}",
		"cmd.info.role.default" => "&e&lDefault Role: &r{isDefault}",
		"cmd.info.role.perms_header" => "&e&lPermissions:",
		"cmd.info.role.perm_entry" => "&e - {color}{permission}",
		"cmd.info.role.members_header" => "&e&lOnline Members ({count}):",
		"cmd.info.role.member_entry" => "&e - {member}",
		"cmd.info.role.no_online_members" => "&e - &cNo online members",
		"cmd.info.role.offline_members_header" => "&e&lOffline Members ({count}):",
		"cmd.info.role.offline_member_entry" => "&e - {member}",
		"cmd.info.role.no_offline_members" => "&e - &cNo offline members",

		"cmd.info.role_list.header" => "&9&lAvailable Roles ({count}):",
		"cmd.info.role_list.entry" => "&e - {role} ({role_id})",

		"cmd.info.perm_list.header" => "&9&lAvailable Permissions ({count}):",
		"cmd.info.perm_list.entry" => "&e - {permission}",

		"cmd.revokeperm.form.choose_type" => "Please choose the target type you'd like to remove permissions from, from the options below",
		"cmd.revokeperm.form.type_role" => "Role",
		"cmd.revokeperm.form.type_member" => "Member",
		"cmd.revokeperm.role.success" => "&eSuccessfully removed the permission '{permission}' from Role {role}({role_id})",
		"cmd.revokeperm.member.success" => "&eSuccessfully removed the permission '{permission}' from {member}",

		"cmd.takerole.success" => "&eRemoved '{role}' role from {member}",
		"cmd.takerole.no_role" => "&c{member} does not have the '{role}' role",
		"cmd.takerole.default" => "&cCannot remove default role from {member}",

        "cmd.transfer_privileges.same_member" => "&cCannot transfer privileges to the same player.",
        "cmd.transfer_privileges.success" => "&aTransferred privileges from {source} to {target}",

		"err.target_higher_hrk" => "&cYou cannot use this command on '{target}' due to higher role hierarchy",
		"err.unknown_permission" => "&cUnknown permission node.",
		"err.unknown_role" => "&cRole not found. For a complete list of roles, please use '/hrk info role_list'",
		"err.no_permissions" => "&cThis role is not assigned any permissions",
		"err.no_roles" => "&cThere are no roles setup",
		"err.no_roles_for_action" => "&cThere are no roles available for this action",
		"err.player_only" => "&cThis command is player only",
	];

	public function __construct(string $filePath, int $type = Config::YAML, ?array $defaults = null) {
		if($defaults !== null) {
			static::$defaults = $defaults;
		}
		if(empty(static::$defaults)) {
			throw new RuntimeException("No defaults given to message store instance for " . get_class($this));
		}
		static::$config = new Config($filePath, $type, static::$defaults);
	}

	public static function getMessage(
		string $dataKey,
		array $args = [],
		string $prefix = "{",
		string $suffix = "}"
	): string {
		return TextFormat::colorize(
			self::substituteString(static::getMessageRaw($dataKey), $args, $prefix, $suffix),
			"&"
		);
	}

	protected static function substituteString(string $str, array $args, string $prefix, string $suffix): string {
		foreach($args as $item => $value) {
			$str = str_ireplace($prefix . $item . $suffix, (string)$value, $str);
		}

		return $str;
	}

	public static function getMessageRaw(string $dataKey): string {
		return (string)static::$config->get($dataKey, static::$defaults[$dataKey]);
	}
}