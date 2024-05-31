<?php

declare(strict_types=1);

namespace Farmero\ranks\task;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;

use Farmero\ranks\Ranks;

class TempRankTask extends Task {

    private $player;
    private $rank;
    private $timeLeft;

    public function __construct(Player $player, string $rank, int $time) {
        $this->player = $player;
        $this->rank = $rank;
        $this->timeLeft = $time;
    }

    public function onRun(): void {
        $ranksManager = Ranks::getInstance()->getRanksManager();

        if ($this->player->isOnline()) {
            if ($this->timeLeft > 0) {
                $this->timeLeft--;
                $ranksManager->updateTempRankDisplay($this->player, $this->rank, $this->timeLeft);
                Ranks::getInstance()->getScheduler()->scheduleDelayedTask(new self($this->player, $this->rank, $this->timeLeft), 20);
            } else {
                $ranksManager->removeTempRank($this->player);
            }
        }
    }
}
