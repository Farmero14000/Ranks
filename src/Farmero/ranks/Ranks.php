<?php

declare(strict_types=1);

namespace Farmero\ranks;

use pocketmine\plugin\PluginBase;

use Farmero\ranks\RanksManager;

use Farmero\ranks\command\RanksCommand;

use Farmero\ranks\event\RankListener;

class Ranks extends PluginBase {

    private $ranksManager;
    public static $instance;

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        $this->saveResource("ranks.yml");
        $this->ranksManager = new RanksManager($this);
        $this->getServer()->getCommandMap()->register("Ranks", new RanksCommand());
        $this->getServer()->getPluginManager()->registerEvents(new RankListener(), $this);
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    public function getRanksManager(): RanksManager {
        return $this->ranksManager;
    }
}
