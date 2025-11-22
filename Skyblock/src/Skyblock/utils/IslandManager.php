<?php

namespace Skyblock\utils;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\console\ConsoleCommandSender;

class IslandManager {

    public static function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        // Eğer dünya yoksa MultiWorld komutuyla void olarak oluştur
        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "mw create $worldName 0 void");
        }

        // Dünyayı yükle
        if(!$wm->isWorldLoaded($worldName)){
            $wm->loadWorld($worldName);
        }

        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: $worldName");
            return;
        }

        // Ada başlangıç koordinatı
        $baseX = 0; $baseY = 100; $baseZ = 0;

        // EasyEdit schematic dosyasını yapıştır
        $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
        Server::getInstance()->dispatchCommand($console, "easyedit paste myisland $baseX $baseY $baseZ $worldName");

        // Schematic boyutları: 6x6x5
        $spawnX = $baseX + 3;
        $spawnZ = $baseZ + 3;
        $spawnY = $baseY + 5;

        // Oyuncuyu ortasına ışınla
        $player->teleport(new Position($spawnX, $spawnY, $spawnZ, $world));
        $player->sendMessage("§aKendi Skyblock adan hazır, ortasında doğdun!");
    }
}
