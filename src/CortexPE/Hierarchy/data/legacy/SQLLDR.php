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

namespace CortexPE\Hierarchy\data\legacy;


use CortexPE\Hierarchy\Hierarchy;
use Generator;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_map;
use function array_values;

abstract class SQLLDR extends LegacyDataReader {
	protected const STMTS_FILE = null;
	protected const DIALECT = null;

	/** @var DataConnector */
	protected $db;

	/** @var array */
	protected $roles = [];
	/** @var array */
	private $memberData = []; // I can't think of any way to yield back data from inside an anonymous function

	public function __construct(Hierarchy $plugin, array $config) {
		parent::__construct($plugin);

		$this->db = libasynql::create($plugin, [
			"type" => static::DIALECT,
			static::DIALECT => $this->getExtraDBSettings($plugin, $config),
			"worker-limit" => $config["workerLimit"]
		], [
			static::DIALECT => static::STMTS_FILE
		]);

		Await::f2c(function () {
			$this->roles = yield $this->asyncSelect("hierarchy.role.list");
			if(empty($this->roles)) {
				$pMgr = PermissionManager::getInstance();
				$this->roles[] = [
					"ID" => 1,
					"Position" => 0,
					"Name" => "Member",
					"isDefault" => 1,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_USER)->getChildren())
				];
				$this->roles[] = [
					"ID" => 2,
					"Position" => 1,
					"Name" => "Operator",
					"isDefault" => 0,
					"Permissions" => array_keys($pMgr->getPermission(DefaultPermissions::ROOT_OPERATOR)->getChildren())
				];
			} else {
				foreach($this->roles as $k => $role) {
					$permissions = yield $this->asyncSelect("hierarchy.role.permissions.get", [
						"role_id" => $role["ID"]
					]);
					foreach($permissions as $permission_row) {
						$this->roles[$k]["Permissions"][] = $permission_row["Permission"];
					}
				}
			}
			$memberNames = yield $this->asyncSelect("hierarchy.member.list");
			foreach($memberNames as $name){
				$name = $name["Player"];
				$this->memberData[$name] = [
					"name" => $name,
					"roles" => [$this->getDefaultRoleID()]
				];
				$rows = yield $this->asyncSelect("hierarchy.member.roles.get", [
					"username" => $name
				]);
				foreach($rows as $row) {
					$this->memberData[$name]["roles"][] = $row["RoleID"];
				}
				$rows = yield $this->asyncSelect("hierarchy.member.permissions.get", [
					"username" => $name
				]);
				foreach($rows as $row) {
					$this->memberData[$name]["permissions"][] = $row["Permission"];
				}
			}
		}, function () {
		}, function (Throwable $err) {
			$this->plugin->getLogger()->logException($err);
		});
		$this->db->waitAll();
	}

	/**
	 * @return array
	 */
	public function getRoles(): array {
		return $this->roles;
	}

	abstract function getExtraDBSettings(Hierarchy $plugin, array $config): array;

	protected function asyncSelect(string $query, array $args = []): Generator {
		$this->db->executeSelect($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	public function getMemberDatum(): Generator {
		foreach($this->memberData as $datum){
			yield $datum;
		}
	}

	public function shutdown(): void {
		if($this->db instanceof DataConnector) {
			$this->db->waitAll();
			$this->db->close();
		}
	}

	/**
	 * @return DataConnector
	 */
	public function getDB(): DataConnector {
		return $this->db;
	}
}