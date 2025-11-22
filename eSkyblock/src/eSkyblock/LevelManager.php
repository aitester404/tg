<?php

namespace eSkyblock;

use pocketmine\block\Block;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class LevelManager implements Listener {

    private Main $plugin;
    private array $xpBlocks;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
        $this->xpBlocks = $plugin->getConfig()->get("xpBlocks", []);

        // Eventleri dinle
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    private function getPlayerData(Player $player): Config {
        return new Config($this->plugin->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml", Config::YAML);
    }

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        $xp = $this->getXpForBlock($block);
        if($xp > 0){
            $this->addXp($player, $xp);
        }
    }

    public function getXpForBlock(Block $block): int {
        $id = strtolower($block->getName());
        return $this->xpBlocks[$id] ?? 0;
    }

    public function addXp(Player $player, int $xp): void {
        $data = $this->getPlayerData($player);
        $island = $data->get("island", null);

        if($island === null){
            return; // Ada yoksa XP eklenmez
        }

        $currentXp = $island["xp"] ?? 0;
        $currentLevel = $island["level"] ?? 0;

        $newXp = $currentXp + $xp;

        // Level için gereken XP: (level+1) * 250
        $neededXp = ($currentLevel + 1) * 250;

        if($newXp >= $neededXp){
            $currentLevel++;
            $newXp -= $neededXp;
            $player->sendMessage("§aTebrikler! Ada seviyen §e" . $currentLevel . " §aoldu!");
        }

        $island["xp"] = $newXp;
        $island["level"] = $currentLevel;
        $data->set("island", $island);
        $data->save();

        // Sohbet prefix güncelleme
        $player->setNameTag("§7[Lv." . $currentLevel . "] §f" . $player->getName());
    }
}
