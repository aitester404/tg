<?php

namespace eSkyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use eSkyblock\Menu;
use eSkyblock\IslandManager;
use eSkyblock\LevelManager;

class Main extends PluginBase {

    private static Main $instance;
    private IslandManager $islandManager;
    private LevelManager $levelManager;

    public function onEnable(): void {
        self::$instance = $this;
        @mkdir($this->getDataFolder() . "players/");
        $this->saveResource("config.yml");
        $this->saveResource("rank.yml");

        $this->islandManager = new IslandManager($this);
        $this->levelManager = new LevelManager($this);
    }

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function getIslandManager(): IslandManager {
        return $this->islandManager;
    }

    public function getLevelManager(): LevelManager {
        return $this->levelManager;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() === "ada"){
            (new Menu($this))->openMenu($sender);
            return true;
        }
        return false;
    }
}
