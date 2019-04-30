<?php


namespace CortexPE\Hierarchy\data;


use CortexPE\Hierarchy\Loader;
use CortexPE\Hierarchy\member\Member;
use pocketmine\Player;
use Throwable;
use Generator;
use pocketmine\permission\PermissionManager;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;

abstract class SQLDataSource extends DataSource {
	/** @var DataConnector */
	protected $db;

	protected const STMTS_FILE = null;
	protected const DIALECT = null;

	public function __construct(Loader $plugin, array $config) {
		parent::__construct($plugin);

		$this->db = libasynql::create(Loader::getInstance(), [
			"type" => static::DIALECT,
			"sqlite" => [
				"file" => Loader::getInstance()->getDataFolder() . $config["dbPath"]
			],
			"worker-limit" => $config["workerLimit"]
		], [
			"sqlite" => static::STMTS_FILE
		]);

		Await::f2c(function () {
			foreach(
				[
					"hierarchy.init.rolesTable",
					"hierarchy.init.rolePermissionTable",
					"hierarchy.init.memberRolesTable"
				] as $tableSchema
			) {
				yield $this->asyncGenericQuery($tableSchema);
			}

			$roles = yield $this->asyncSelect("hierarchy.role.list");
			if(empty($roles)) {
				yield $this->asyncInsert("hierarchy.role.createDefault", [
					"name" => "Member"
				]);
				$roles = yield $this->asyncSelect("hierarchy.role.list");
				foreach($roles as $role){
					$defaults = PermissionManager::getInstance()->getDefaultPermissions(false);
					foreach($defaults as $permission){
						yield $this->asyncInsert("hierarchy.role.permissions.add", [
							"role_id" => $role["ID"],
							"permission" => $permission->getName()
						]);
					}
				}
			}
			foreach($roles as $k => $role) {
				$permissions = yield $this->asyncSelect("hierarchy.role.permissions.get", [
					"role_id" => $role["ID"]
				]);
				foreach($permissions as $permission_row) {
					$roles[$k]["Permissions"][] = $permission_row["Permission"];
				}
			}
			$this->getPlugin()->getRoleManager()->loadRoles($roles);
		}, function(){}, function (Throwable $err){
			$this->getPlugin()->getLogger()->logException($err);
		});
	}

	public function loadMemberData(Member $member): void {
		Await::f2c(function () use ($member) {
			$data = [];
			$p = $member->getPlayer();
			$rows = yield $this->asyncSelect("hierarchy.member.roles.get", [
				"username" => $member->getName()
			]);
			foreach($rows as $row){
				$data["roles"][] = $row["RoleID"];
			}
			if($p instanceof Player && $p->isOnline()){
				$this->plugin->getMemberFactory()->getMember($p)->loadData($data);
			}
		}, function(){}, function (Throwable $err){
			$this->getPlugin()->getLogger()->logException($err);
		});
	}

	public function updateMemberData(Member $member, string $action, $data): void {
		switch($action){
			case self::ACTION_ROLE_ADD:
				$this->db->executeChange("hierarchy.member.roles.add", [
					"username" => $member->getName(),
					"role_id" => (int)$data
				]);
				break;
			case self::ACTION_ROLE_REMOVE:
				$this->db->executeChange("hierarchy.member.roles.remove", [
					"username" => $member->getName(),
					"role_id" => (int)$data
				]);
				break;
		}
	}

	public function shutdown(): void {
		if($this->db instanceof DataConnector) {
			$this->db->close();
		}
	}

	protected function asyncSelect(string $query, array $args = []): Generator {
		$this->db->executeSelect($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	protected function asyncInsert(string $query, array $args = []): Generator {
		$this->db->executeInsert($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}

	protected function asyncGenericQuery(string $query, array $args = []): Generator {
		$this->db->executeGeneric($query, $args, yield, yield Await::REJECT);

		return yield Await::ONCE;
	}
}
