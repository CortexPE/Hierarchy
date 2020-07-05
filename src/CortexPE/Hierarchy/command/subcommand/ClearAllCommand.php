<?php


namespace CortexPE\Hierarchy\command\subcommand;


use CortexPE\Commando\BaseCommand;
use CortexPE\Hierarchy\command\args\MemberArgument;
use CortexPE\Hierarchy\command\HierarchySubCommand;
use CortexPE\Hierarchy\data\member\MemberDataSource;
use CortexPE\Hierarchy\member\BaseMember;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ClearAllCommand extends HierarchySubCommand {

	protected function prepare(): void {
		$this->registerArgument(0, new MemberArgument("targetMember", false));
		$this->setPermission(implode(";", [
			"hierarchy",
			"hierarchy.member",
			"hierarchy.member.clear_all"
		]));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if($this->isSenderInGameNoArguments($args)) {
			$this->sendForm();

			return;
		} elseif(count($args) < 1) {
			$this->sendError(BaseCommand::ERR_INSUFFICIENT_ARGUMENTS);

			return;
		}
		/** @var BaseMember $target */
		$target = $args["targetMember"];

		if(!$this->doHierarchyPositionCheck($target)) {
			return;
		}

		foreach($target->getRoles() as $role) {
			if($role->isDefault())continue;
			$target->removeRole($role, true, false);
		}
		foreach($target->getMemberPermissions() as $permission => $value){
			$target->removeMemberPermission($permission, true, false);
		}
		$this->plugin->getMemberDataSource()->updateMemberData($target, MemberDataSource::ACTION_MEMBER_ROLE_REMOVE_ALL);
		$this->plugin->getMemberDataSource()->updateMemberData($target, MemberDataSource::ACTION_MEMBER_PERMS_REMOVE_ALL);
		$this->sendFormattedMessage("cmd.clear_all.success", [
			"target" => $target->getName()
		]);
	}

	public function sendForm(): void {
		if($this->currentSender instanceof Player) {
			$this->currentSender->sendForm(new CustomForm($this->plugin->getName(), [
				new Label("description", $this->getDescription()),
				new Input("targetMember", "Target Member", "Target Member Name")
			],
				function(Player $player, CustomFormResponse $response): void {
					$this->setCurrentSender($player);
					$this->onRun($player, $this->getName(), [
						"targetMember" => $this->memberFactory->getMember($response->getString("targetMember"))
					]);
				}
			));
		}
	}
}