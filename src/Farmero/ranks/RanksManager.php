<?php

declare(strict_types=1);

namespace Farmero\ranks;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

use Farmero\ranks\Ranks;

use Farmero\ranks\task\TempRankTask;

class RanksManager {

    private $ranksData;
    private $ranksConfig;
    private $defaultRank;

    public function __construct() {
        $this->loadRanks();
        $this->loadTempRanks();
        $this->loadRanksConfig();
    }

    private function loadRanks(): void {
        $this->ranksData = (new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON))->getAll();
    }

    private function loadTempRanks(): void {
        $this->tempRanksData = [];
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
            $this->removePermissions($player);
            $this->ranksData[$player->getName()] = $rank;
            $this->saveRanks();
            $this->assignPermissions($player);
            $this->updatePlayerDisplayName($player);
        } else {
            $player->sendMessage("The rank $rank does not exist.");
        }
    }

    public function getRank(Player $player): ?string {
        return $this->ranksData[$player->getName()] ?? $this->defaultRank;
    }

    public function getAllRanks(): array {
        $config = (new Config(Ranks::getInstance()->getDataFolder() . "ranks.yml", Config::YAML))->getAll();
        $ranks = [];
        foreach ($config['ranks'] as $rankName => $rankData) {
            $ranks[$rankName] = $rankData['rank_display'];
        }
        return $ranks;
    }

    public function removeRank(Player $player): void {
        if (isset($this->ranksData[$player->getName()])) {
            $this->removePermissions($player);
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
            $this->assignPermissions($player);
            $this->updatePlayerDisplayName($player);
        }
    }

    public function updatePlayerDisplayName(Player $player): void {
        $rank = $this->getRank($player);
        $rankDisplay = $this->getRankDisplay($rank);
        $player->setDisplayName("[" . $rankDisplay . "] " . $player->getName());
    }

    private function assignPermissions(Player $player): void {
        $rank = $this->getRank($player);
        $permissions = $this->getRankPermissions($rank);
        if ($permissions !== null) {
            foreach ($permissions as $permission) {
                $player->addAttachment(Ranks::getInstance(), $permission, true);
            }
        }
    }

    private function removePermissions(Player $player): void {
        $rank = $this->getRank($player);
        $permissions = $this->getRankPermissions($rank);
        if ($permissions !== null) {
            foreach ($permissions as $permission) {
                $player->addAttachment(Ranks::getInstance(), $permission, false);
            }
        }
    }

    public function setTempRank(Player $player, string $rank, string $time): void {
        $this->tempRanksData[$player->getName()] = [
            'rank' => $rank,
            'expiry' => strtotime("+" . $time)
        ];
        Ranks::getInstance()->getScheduler()->scheduleRepeatingTask(new TempRankTask($player, $rank, $this->tempRanksData[$player->getName()]['expiry']), 20);
        $this->saveTempRanks();
    }

    private function saveTempRanks(): void {
        $config = new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON);
        $config->set("temp_ranks", $this->tempRanksData);
        $config->save();
    }

    public function updateTempRankDisplay(Player $player, string $rank, int $timeLeft): void {
        $rankDisplay = $this->getRankDisplay($rank);
        $player->setDisplayName("[" . $rankDisplay . "] " . $player->getName() . " (TempRank: " . $this->formatTime($timeLeft) . ")");
    }

    public function removeTempRank(Player $player): void {
        if (isset($this->tempRanksData[$player->getName()])) {
            unset($this->tempRanksData[$player->getName()]);
            $this->updatePlayerDisplayName($player);
        }
    }

    private function formatTime(int $seconds): string {
        $days = floor($seconds / (3600 * 24));
        $hours = floor(($seconds % (3600 * 24)) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        $timeString = "";
        if ($days > 0) {
            $timeString .= $days . "d ";
        }
        if ($hours > 0) {
            $timeString .= $hours . "h ";
        }
        if ($minutes > 0) {
            $timeString .= $minutes . "m ";
        }
        if ($seconds > 0) {
            $timeString .= $seconds . "s";
        }

        return trim($timeString);
    }
}
