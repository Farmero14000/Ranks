<?php

declare(strict_types=1);

namespace Farmero\ranks\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use Farmero\ranks\Ranks;

class TempRankCommand extends Command {

    public function __construct() {
        parent::__construct("temprank");
        $this->setLabel("temprank");
        $this->setDescription("Temporarily set a player's rank");
        $this->setAliases(["tr", "temp"]);
        $this->setPermission("ranks.temprank");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game!");
            return false;
        }

        if (count($args) < 3) {
            $sender->sendMessage("Usage: /temprank <player> <rank> <time>");
            return false;
        }

        $targetName = array_shift($args);
        $rank = array_shift($args);
        $time = implode(" ", $args);
        $ranksManager = Ranks::getInstance()->getRanksManager();
        $target = Ranks::getInstance()->getServer()->getPlayerByPrefix($targetName);

        if ($target === null) {
            $sender->sendMessage("Player not found...");
            return false;
        }
        $ranksManager->setTempRank($target, $rank, $time);
        $sender->sendMessage("Temporary rank set for " . $target->getName() . "!");
        return true;
    }
}
