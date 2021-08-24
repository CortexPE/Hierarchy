<?php


namespace CortexPE\Hierarchy\utils;


use CortexPE\Hierarchy\member\BaseMember;
use CortexPE\Hierarchy\role\Role;
use pocketmine\permission\Permission;

final class PermissionUtils {
	/**
	 * @param BaseMember $source
	 * @param BaseMember|Role $target The member or role to compare against
	 * @param Permission|string|null $permission The permission to compare
	 * @return bool Returns true if the source wins the Hiararchy position, false if the target wins
	 */
	public static function checkHierarchy(BaseMember $source, BaseMember|Role $target, Permission|string|null $permission = null): bool {
		if($permission === null) {
			if($target instanceof BaseMember) {
				$targetPos = $target->getTopRole()->getPosition();
			} elseif($target instanceof Role) {
				$targetPos = $target->getPosition();
			} else {
				throw new \InvalidArgumentException("Passed argument is neither a Role nor a Member");
			}
			$senderPos = $source->getTopRole()->getPosition();
		} else {
			if($target instanceof BaseMember) {
				$targetPos = 0;
				$topRole = $target->getTopRoleWithPermission($permission);
				if($topRole !== null) {
					$targetPos = $topRole->getPosition();
				}
			} else {
				throw new \InvalidArgumentException("Passed argument is not a Member");
			}
			$senderPos = 0;
			$topRole = $source->getTopRoleWithPermission($permission);
			if($topRole !== null) {
				$senderPos = $topRole->getPosition();
			}
		}

		return $senderPos > $targetPos;
	}
}