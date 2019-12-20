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

namespace CortexPE\Hierarchy\data\member;


use CortexPE\Hierarchy\data\traits\IndexedDataUtilities;
use CortexPE\Hierarchy\member\BaseMember;
use function array_merge;
use function array_search;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function ltrim;
use function mkdir;
use function strtolower;

abstract class IndexedMemberDS extends MemberDataSource {
	use IndexedDataUtilities;

	/** @var string */
	protected const FILE_EXTENSION = null;

	/** @var string */
	protected $membersDir;

	public function initialize(): void {
		@mkdir(($this->membersDir = $this->plugin->getDataFolder() . "members/"));
	}

	abstract function decode(string $string): array;

	abstract function encode(array $data): string;

	public function loadMemberData(BaseMember $member, ?callable $onLoad = null): void {
		$member->loadData($this->readMemberData($member));
		if($onLoad !== null) {
			$onLoad();
		}
	}

	public function updateMemberData(BaseMember $member, string $action, $data): void {
		$existingData = $this->readMemberData($member);

		switch($action) {
			case self::ACTION_MEMBER_ROLE_ADD:
				$existingData["roles"][] = (int)$data;
				break;
			case self::ACTION_MEMBER_ROLE_REMOVE:
				$i = array_search(($data = (int)$data), $existingData["roles"]);
				if($i !== false) {
					unset($existingData["roles"][$i]);
				}
				break;
			case self::ACTION_MEMBER_PERMS_ADD:
				$perms = $existingData["permissions"] ?? [];
				self::removePermissionFromArray(ltrim($data, "-"), $perms);
				$perms[] = $data;
				$existingData["permissions"] = $perms;
				break;
			case self::ACTION_MEMBER_PERMS_REMOVE:
				$perms = $existingData["permissions"] ?? [];
				self::removePermissionFromArray(ltrim($data, "-"), $perms);
				$existingData["permissions"] = $perms;
				break;
		}
		foreach(["roles", "permissions"] as $i) {
			if(isset($existingData[$i])) {
				self::reIndex($existingData[$i]);
			}
		}

		file_put_contents($this->getFileName($member), $this->encode($existingData));
	}

	protected function readMemberData(BaseMember $member): array {
		$data = [
			"roles" => [
				$this->plugin->getRoleManager()->getDefaultRole()->getId()
			]
		];
		if(file_exists(($fp = $this->getFileName($member)))) {
			$dat = $this->decode(file_get_contents($fp));
			if(isset($dat["roles"])) {
				$data["roles"] = array_merge($data["roles"], $dat["roles"]);
			}
			$data["permissions"] = $dat["permissions"] ?? [];
		}

		return $data;
	}

	protected function getFileName(BaseMember $member): string {
		return $this->membersDir . strtolower($member->getName()) . "." . static::FILE_EXTENSION;
	}

	public function flush(): void {
		// noop
	}

	public function shutdown(): void {
		// noop
	}
}