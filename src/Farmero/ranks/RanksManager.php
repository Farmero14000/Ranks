<?php

declare(strict_types=1);

namespace Farmero\ranks;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\permission\PermissionAttachment;

use Farmero\ranks\Ranks;

class RanksManager {

    private $ranksData;
    private $ranksConfig;
    private $defaultRank;
    private $attachments = [];

    public function __construct() {
        $this->loadRanks();
        $this->loadRanksConfig();
    }

    public function loadRanks(): void {
        $this->ranksData = (new Config(Ranks::getInstance()->getDataFolder() . "player_ranks.json", Config::JSON))->getAll();
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
            $this->assignPermissions($player); // Reassign default rank permissions
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

    public function getRankTag(string $rank): ?string {
        return $this->ranksConfig['ranks'][$rank]['rank_player_tag'] ?? null;
    }

    public function getChatFormat(string $rank): ?string {
        return $this->ranksConfig['ranks'][$rank]['rank_chat_format'] ?? null;
    }

    public function setRankTag(string $rank, string $tag): void {
        $this->ranksConfig['ranks'][$rank]['rank_player_tag'] = $tag;
    }

    public function setChatFormat(string $rank, string $format): void {
        $this->ranksConfig['ranks'][$rank]['rank_chat_format'] = $format;
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
        $rankTag = $rank ? $this->getRankTag($rank) : "{playerName}";
        $displayName = str_replace("{playerName}", $player->getName(), $rankTag);
        $player->setDisplayName($displayName);
    }

    public function assignPermissions(Player $player): void {
        $rank = $this->getRank($player);
        $permissions = $this->getRankPermissions($rank);
        if ($permissions !== null) {
            if (!isset($this->attachments[$player->getName()])) {
                $this->attachments[$player->getName()] = $player->addAttachment(Ranks::getInstance());
            }
            $attachment = $this->attachments[$player->getName()];
            foreach ($permissions as $permission) {
                $attachment->setPermission($permission, true);
            }
        }
    }

    public function removePermissions(Player $player): void {
        if (isset($this->attachments[$player->getName()])) {
            $attachment = $this->attachments[$player->getName()];
            $rank = $this->getRank($player);
            $permissions = $this->getRankPermissions($rank);
            if ($permissions !== null) {
                foreach ($permissions as $permission) {
                    $attachment->unsetPermission($permission);
                }
            }
            $player->removeAttachment($attachment);
            unset($this->attachments[$player->getName()]);
        }
    }
}
