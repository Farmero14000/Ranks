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
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $rankDisplay = Ranks::getInstance()->getRanksManager()->getRankDisplay(Ranks::getInstance()->getRanksManager()->getRank($player));
        $event->setFormatter(new LegacyRawChatFormatter(TF::GREEN . "[" . $rankDisplay . "] " . $player->getName() . ": " . $event->getMessage()));
    }
}
