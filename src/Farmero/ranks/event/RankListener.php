<?php

declare(strict_types=1);

namespace Farmero\ranks\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\utils\TextFormat as TF;

use Farmero\ranks\Ranks;

class RankListener implements Listener {

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        Ranks::getInstance()->getRanksManager()->createPlayerProfile($player);
        Ranks::getInstance()->getRanksManager()->updatePlayerDisplayName($player);
        Ranks::getInstance()->getRanksManager()->updateTempRankDisplay($player);
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $ranksManager = Ranks::getInstance()->getRanksManager();
        $rank = $ranksManager->getRank($player);
        $rankDisplay = $ranksManager->getRankDisplay($rank);
        $tempRankData = $ranksManager->getTempRankData($player);
        if ($tempRankData !== null) {
            $tempRankDisplay = $ranksManager->getRankDisplay($tempRankData['rank']);
            $timeLeft = $tempRankData['expiry'];
            $formattedTime = $ranksManager->formatTime($timeLeft);
            $rankDisplay .= " (TempRank: $tempRankDisplay $formattedTime)";
        }
        $event->setFormatter(new LegacyRawChatFormatter(TF::GREEN . "[" . $rankDisplay . "] " . $player->getName() . ": " . $event->getMessage()));
    }
}
