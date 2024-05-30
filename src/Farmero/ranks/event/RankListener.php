<?php

declare(strict_types=1);

namespace Farmero\ranks\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;
use pocketmine\player\chat\\LegacyRawChatFormatter;

use Farmero\ranks\Ranks;

class RankListener implements Listener {

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        Ranks::getInstance()->getRanksManager()->createPlayerProfile($player);
        $this->updateDisplayName($player);
    }

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $rankManager = Ranks::getInstance()->getRanksManager();
        $rank = $rankManager->getRank($player);
        $rankData = $rankManager->getRanks()[$rank] ?? null;
        if ($rankData !== null) {
            $rankDisplay = $rankData["rank_display"];
            $playerName = $player->getName();
            $message = $event->getMessage();
            $event->setFormatter(new LegacyRawChatFormatter("[{$rankDisplay}] {$playerName}: {$message}"));
        }
    }

    private function updateDisplayName(Player $player): void {
        $rankManager = Ranks::getInstance()->getRanksManager();
        $rank = $rankManager->getRank($player);
        $rankData = $rankManager->getRanks()[$rank] ?? null;
        if ($rankData !== null) {
            $rankDisplay = $rankData["rank_display"];
            $player->setDisplayName("[{$rankDisplay}] " . $player->getName());
        }
    }
}