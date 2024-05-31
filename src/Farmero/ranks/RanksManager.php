<?php

declare(strict_types=1);

namespace Farmero\ranks;

use pocketmine\player\Player;
use pocketmine\utils\Config;

use Farmero\ranks\Ranks;

class RanksManager {

    private $ranksData;
    private $ranksConfig;
    private $defaultRank;

    public function __construct() {
        $this->loadRanks();
        $this->loadRanksConfig();
    }

    private function loadRanks(): void {
        $this->ranksData = (new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON))->getAll();
    }

    private function saveRanks(): void {
        $config = new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON);
        $config->setAll($this->ranksData);
        $config->save();
    }

    private function loadRanksConfig(): void {
        $this->ranksConfig = (new Config(Ranks::getInstance()->getDataFolder() . "ranks.yml", Config::YAML))->getAll();
        $this->defaultRank = $this->ranksConfig['default_rank'] ?? null;
    }

    public function setRank(Player $player, string $rank): void {
        if ($this->rankExists($rank)) {
            $this->ranksData[$player->getName()] = $rank;
            $this->saveRanks();
            $this->updatePlayerDisplayName($player);
        } else {
            $player->sendMessage("The rank $rank does not exist.");
        }
    }

    public function getRank(Player $player): ?string {
        return $this->ranksData[$player->getName()] ?? $this->defaultRank;
    }

    public function removeRank(Player $player): void {
        if (isset($this->ranksData[$player->getName()])) {
            unset($this->ranksData[$player->getName()]);
            $this->saveRanks();
            $this->updatePlayerDisplayName($player);
        }
    }

    public function rankExists(string $rank): bool {
        return isset($this->ranksConfig['ranks'][$rank]);
    }

    public function rankHierarchy(): array {
        return $this->ranksConfig['hierarchy'] ?? [];
    }

    public function getRankPermissions(string $rank): ?array {
        return $this->ranksConfig['ranks'][$rank]['permissions'] ?? null;
    }

    public function getDefaultRank(): ?string {
        return $this->defaultRank;
    }

    public function getRankDisplay(string $rank): ?string {
        return $this->ranksConfig['ranks'][$rank]['rank_display'] ?? $rank;
    }

    public function createPlayerProfile(Player $player): void {
        if (!isset($this->ranksData[$player->getName()])) {
            $this->ranksData[$player->getName()] = $this->defaultRank;
            $this->saveRanks();
            $this->updatePlayerDisplayName($player);
        }
    }

    public function updatePlayerDisplayName(Player $player): void {
        $rank = $this->getRank($player);
        $rankDisplay = $this->getRankDisplay($rank);
        $player->setDisplayName("[" . $rankDisplay . "] " . $player->getName());
    }
}
