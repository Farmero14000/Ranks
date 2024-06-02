<?php

declare(strict_types=1);

namespace Farmero\ranks\event;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;

use Farmero\ranks\Ranks;

class RankListener implements Listener {

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $ranksManager = Ranks::getInstance()->getRanksManager();
        $ranksManager->createPlayerProfile($player);
        $ranksManager->updatePlayerDisplayName($player);
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $rank = Ranks::getInstance()->getRanksManager()->getRank($player);
        $rankChatFormat = $rank ? Ranks::getInstance()->getRanksManager()->getChatFormat($rank) : "{playerName}: {message}";
        $formattedMessage = str_replace(["{playerName}", "{message}"], [$player->getName(), $event->getMessage()], $rankChatFormat);
        $event->setFormatter(new LegacyRawChatFormatter($formattedMessage));
}
