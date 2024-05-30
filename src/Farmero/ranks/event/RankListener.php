<?php

declare(strict_types=1);

namespace Farmero\ranks\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\utils\TextFormat as TF;

use Farmero\ranks\Ranks;

class RankListener implements Listener {

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        Ranks::getInstance()->getRanksManager()->createPlayerProfile($player);
        $this->updatePlayerDisplayName($player);
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $rank = Ranks::getInstance()->getRanksManager()->getRank($player);
        $rankDisplay = $rank ? Ranks::getInstance()->getRanksManager()->getRankDisplay($player) : "";
        $event->setFormatter(new LegacyRawChatFormatter(TF::GREEN . "[" . $rankDisplay . "] " . $player->getName() . ": " . $event->getMessage()));
    }

    public function updatePlayerDisplayName(Player $player): void {
        $rank = Ranks::getInstance()->getRanksManager()->getRank($player);
        $rankDisplay = $rank ? Ranks::getInstance()->getRanksManager()->getRankDisplay($player) : "";
        $player->setDisplayName(TF::GREEN . "[" . $rankDisplay . "] " . $player->getName());
    }
}
