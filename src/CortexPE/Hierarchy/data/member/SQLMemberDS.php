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


use CortexPE\Hierarchy\Hierarchy;
use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use Generator;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;
use Throwable;

abstract class SQLMemberDS extends MemberDataSource {
	protected const STMTS_FILE = null;
	protected const DIALECT = null;
	/** @var DataConnector */
	protected $db;

	public function __construct(Hierarchy $plugin, array $config) {
		parent::__construct($plugin);

		$this->db = libasynql::create($plugin, [
			"type" => static::DIALECT,
			static::DIALECT => $this->getExtraDBSettings($plugin, $config),
			"worker-limit" => $config["workerLimit"]
		], [
			static::DIALECT => static::STMTS_FILE
		]);
	}

	public function initialize(): void {
		Await::f2c(function () {
			foreach(
				[
					"hierarchy.init.memberRolesTable",
					"hierarchy.init.memberPermissionsTable",
				] as $tableSchema
			) {
				yield $this->asyncGenericQuery($tableSchema);
			}
			foreach(
				[
					"hierarchy.check.memberRoles_check1" => "hierarchy.migrate.memberRoles_patch1",
					"hierarchy.check.memberPermissions_check1" => "hierarchy.migrate.memberPermissions_patch1",
				] as $checkQuery => $failedQuery
			) {
				if(!(bool)(yield $this->asyncSelect($checkQuery))[0]["result"]){
					yield $this->asyncGenericQuery($failedQuery);
				}
			}
		}, function () {
		}, function (Throwable $err) {
			$this->getPlugin()->getLogger()->logException($err);
		});
		$this->db->waitAll();
	}

	abstract function getExtraDBSettings(Hierarchy $plugin, array $config): array;

	protected function asyncGenericQuery(string $query, array $args = []): Generator {
		$this->db->executeGeneric($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	protected function asyncSelect(string $query, array $args = []): Generator {
		$this->db->executeSelect($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	protected function asyncInsert(string $query, array $args = []): Generator {
		$this->db->executeInsert($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	public function loadMemberData(BaseMember $member): void {
		Await::f2c(function () use ($member) {
			$data = [
				"roles" => [
					$this->plugin->getRoleManager()->getDefaultRole()->getId() => null
				]
			];
			$rows = yield $this->asyncSelect("hierarchy.member.roles.get", [
				"username" => $member->getName()
			]);
			foreach($rows as $row) {
				$data["roles"][$row["RoleID"]] = $row["AdditionalData"] !== null ? json_decode($row["AdditionalData"], true) : null;
			}
			$rows = yield $this->asyncSelect("hierarchy.member.permissions.get", [
				"username" => $member->getName()
			]);
			foreach($rows as $row) {
				$data["permissions"][$row["Permission"]] = $row["AdditionalData"] !== null ? json_decode($row["AdditionalData"], true) : null;
			}
			$member->loadData($data);
		}, null, function (Throwable $err) {
			$this->getPlugin()->getLogger()->logException($err);
		});
	}

	public function updateMemberData(BaseMember $member, string $action, $data): void {
		switch($action) {
			case self::ACTION_MEMBER_ROLE_ADD:
				$this->db->executeChange("hierarchy.member.roles.add", [
					"username" => $member->getName(),
					"role_id" => (int)$data
				]);
				break;
			case self::ACTION_MEMBER_ROLE_REMOVE:
				$this->db->executeChange("hierarchy.member.roles.remove", [
					"username" => $member->getName(),
					"role_id" => (int)$data
				]);
				break;
			case self::ACTION_MEMBER_PERMS_ADD:
				$this->db->executeChange("hierarchy.member.permissions.add", [
					"username" => $member->getName(),
					"permission" => $data
				]);
				break;
			case self::ACTION_MEMBER_PERMS_REMOVE:
				$this->db->executeChange("hierarchy.member.permissions.remove", [
					"username" => $member->getName(),
					"permission" => $data
				]);
				break;
			case self::ACTION_MEMBER_UPDATE_ROLE_ETC:
				$this->db->executeChange("hierarchy.member.etc.update.role", [
					"username" => $member->getName(),
					"role_id" => $data[0],
					"additional_data" => json_encode($data[1])
				]);
				break;
			case self::ACTION_MEMBER_UPDATE_PERMISSION_ETC:
				$this->db->executeChange("hierarchy.member.etc.update.permission", [
					"username" => $member->getName(),
					"permission" => $data[0],
					"additional_data" => json_encode($data[1])
				]);
				break;
		}
	}

	public function getMemberNamesOf(Role $role): \Generator {
		return $this->asyncSelect("hierarchy.role.members", [
			"role_id" => $role->getId()
		]);
	}

	public function shutdown(): void {
		if($this->db instanceof DataConnector) {
			$this->db->waitAll();
			$this->db->close();
		}
	}

	public function flush(): void {
		// noop
	}

	/**
	 * @return DataConnector
	 */
	public function getDB(): DataConnector {
		return $this->db;
	}
}