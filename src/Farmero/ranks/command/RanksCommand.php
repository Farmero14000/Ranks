<?php

declare(strict_types=1);

namespace Farmero\ranks\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

use Farmero\ranks\Ranks;

class RanksCommand extends Command {

    private $plugin;

    public function __construct(Ranks $plugin) {
        parent::__construct("rank");
        $this->setLabel("rank");
        $this->setDescription("Set or Remove a players rank");
        $this->setAliases(["r", "ranks"]);
        $this->setPermission("ranks.cmd");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (count($args) < 2) {
            $sender->sendMessage("Usage: /rank <set|remove|get> <player> [rank]");
            return false;
        }

        $subCommand = strtolower($args[0]);
        $player = $this->plugin->getServer()->getPlayerByPrefix($args[1]);

        if (!$player instanceof Player) {
            $sender->sendMessage("Player not found... Try again!");
            return false;
        }

        switch ($subCommand) {
            case "set":
                if (count($args) < 3) {
                    $sender->sendMessage("Usage: /rank set <player> <rank>");
                    return false;
                }
                $rank = $args[2];
                $this->plugin->getRanksManager()->setRank($player, $rank);
                $sender->sendMessage("Rank $rank set for player " . $player->getName());
                break;

            case "remove":
                $this->plugin->getRanksManager()->removeRank($player);
                $sender->sendMessage("Rank removed from player " . $player->getName());
                break;

            case "get":
                $rank = $this->plugin->getRanksManager()->getRank($player);
                $sender->sendMessage("The player " . $player->getName() . " has rank the $rank");
                break;

            default:
                $sender->sendMessage("Usage: /rank <set|remove|get> <player> [rank]");
                return false;
        }

        return true;
    }
}