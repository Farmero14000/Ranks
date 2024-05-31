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
    private $tempRanksData;

    public function __construct() {
        $this->loadRanks();
        $this->loadTempRanks();
        $this->loadRanksConfig();
    }

    public function loadRanks(): void {
        $this->ranksData = (new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON))->getAll();
    }

    public function loadTempRanks(): void {
        $this->tempRanksData = [];
    }

    public function saveRanks(): void {
        $config = new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON);
        $config->setAll($this->ranksData);
        $config->save();
    }

    public function loadRanksConfig(): void {
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

    public function assignPermissions(Player $player): void {
        $rank = $this->getRank($player);
        $permissions = $this->getRankPermissions($rank);
        if ($permissions !== null) {
            foreach ($permissions as $permission) {
                $player->addAttachment(Ranks::getInstance(), $permission, true);
            }
        }
    }

    public function removePermissions(Player $player): void {
        $rank = $this->getRank($player);
        $permissions = $this->getRankPermissions($rank);
        if ($permissions !== null) {
            foreach ($permissions as $permission) {
                $player->addAttachment(Ranks::getInstance(), $permission, false);
            }
        }
    }

    public function setTempRank(Player $player, string $rank, string $time): void {
        $expiryTime = $this->parseTimeToSeconds($time);
        if ($expiryTime !== false) {
            $this->tempRanksData[$player->getName()] = [
                'rank' => $rank,
                'expiry' => $expiryTime
            ];
            Ranks::getInstance()->getScheduler()->scheduleRepeatingTask(new TempRankTask($player, $rank, $expiryTime), 20);
            $this->saveTempRanks();
        } else {
            $player->sendMessage("Invalid time format. Use a format like '1d3h30m' for 1 day, 3 hours, and 30 minutes.");
        }
    }

    public function parseTimeToSeconds(string $time): int|false {
        $regex = '/^(\d+d)?(\d+h)?(\d+m)?(\d+s)?$/';
        if (preg_match($regex, $time, $matches)) {
            $days = isset($matches[1]) ? (int)substr($matches[1], 0, -1) * 86400 : 0;
            $hours = isset($matches[2]) ? (int)substr($matches[2], 0, -1) * 3600 : 0;
            $minutes = isset($matches[3]) ? (int)substr($matches[3], 0, -1) * 60 : 0;
            $seconds = isset($matches[4]) ? (int)substr($matches[4], 0, -1) : 0;
            return $days + $hours + $minutes + $seconds;
        }
        return false;
    }

    public function saveTempRanks(): void {
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

    public function getTempRankData(Player $player): ?array {
        return $this->tempRanksData[$player->getName()] ?? null;
    }

    public function formatTime(int $seconds): string {
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
