<?php

namespace Skyblock\utils;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;
use pocketmine\world\Position;

class IslandManager {

    public static function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());

        $wm = Server::getInstance()->getWorldManager();

        // Dünya yoksa oluştur
        if(!$wm->isWorldGenerated($worldName)){
            $wm->generateWorld($worldName, new WorldCreationOptions());
        }

        // Dünyayı yükle
        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $wm->loadWorld($worldName);
            $world = $wm->getWorldByName($worldName);
        }

        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: $worldName");
            return;
        }

        // Ada koordinatı
        $x = 0; $y = 100; $z = 0;

        // EasyEdit komutunu oyuncu üzerinden çalıştır
        Server::getInstance()->dispatchCommand($player, "easyedit paste myisland $x $y $z $worldName");

        // Oyuncuyu ışınla
        $player->teleport(new Position($x, $y, $z, $world));
        $player->sendMessage("§aKendi Skyblock adan hazır!");
    }
}
