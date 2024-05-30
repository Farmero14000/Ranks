<?php

declare(strict_types=1);

namespace Farmero\ranks;

use pocketmine\player\Player;
use pocketmine\utils\Config;

use Farmero\ranks\Ranks;

class RanksManager {

    private $plugin;
    private $ranks;
    private $playerRanks;

    public function __construct(Ranks $plugin) {
        $this->plugin = $plugin;
        $this->loadRanks();
        $this->playerRanks = new Config($this->plugin->getDataFolder() . "player_ranks.json", Config::JSON, []);
    }

    public function loadRanks(): void {
        $this->ranks = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML);
    }

    public function loadPlayerRanks(): void {
        $this->playerRanks = new Config($this->plugin->getDataFolder() . "player_ranks.json", Config::JSON, []);
    }

    public function createPlayerProfile(Player $player): void {
        $playerName = $player->getName();
        if (!$this->playerRanks->exists($playerName)) {
            $defaultRank = $this->ranks->get("default_rank");
            $this->playerRanks->set($playerName, $defaultRank);
            $this->playerRanks->save();
            $this->updatePlayerPermissions($player, $defaultRank);
        }
    }

    public function getRank(Player $player): ?string {
        return $this->playerRanks->get($player->getName(), $this->ranks->get("default_rank"));
    }

    public function setRank(Player $player, string $rank): void {
        if ($this->ranks->exists($rank)) {
            $this->playerRanks->set($player->getName(), $rank);
            $this->playerRanks->save();
            $this->updatePlayerPermissions($player, $rank);
        }
    }

    public function removeRank(Player $player): void {
        $this->playerRanks->remove($player->getName());
        $this->playerRanks->save();
        $this->updatePlayerPermissions($player, $this->ranks->get("default_rank"));
    }

    public function getRanks(): array {
        return $this->ranks->getAll();
    }

    public function getRankPermissions(string $rank): array {
        $permissions = [];
        $hierarchy = $this->ranks->get("hierarchy", []);
    
        foreach ($hierarchy as $hierarchyRank) {
            if ($hierarchyRank === $rank || $this->ranks->exists("$hierarchyRank.permissions")) {
                $permissions = array_merge($permissions, $this->ranks->getNested("$hierarchyRank.permissions", []));
            }
            if ($hierarchyRank === $rank) {
                break;
            }
        }
    
        return $permissions;
    }

    private function updatePlayerPermissions(Player $player, string $rank): void {
        $player->removeAttachment($player->addAttachment($this->plugin));
        $permissions = $this->getRankPermissions($rank);
        foreach ($permissions as $permission) {
            $player->addAttachment($this->plugin, $permission, true);
        }
    }
}
