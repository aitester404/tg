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

        // Eğer dünya yoksa EasyEdit ile void olarak oluştur
        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "easyedit createworld $worldName void");
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

        // Ada başlangıç koordinatı
        $baseX = 0; $baseY = 100; $baseZ = 0;

        // EasyEdit schematic dosyasını yapıştır
        $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
        Server::getInstance()->dispatchCommand($console, "easyedit paste myisland $baseX $baseY $baseZ $worldName");

        // Schematic boyutları: 6x6x5
        $schemWidth = 6;
        $schemLength = 6;
        $schemHeight = 5;

        // Ortasını hesapla
        $spawnX = $baseX + ($schemWidth / 2);
        $spawnZ = $baseZ + ($schemLength / 2);
        $spawnY = $baseY + $schemHeight;

        // Oyuncuyu ortasına ışınla
        $player->teleport(new Position($spawnX, $spawnY, $spawnZ, $world));
        $player->sendMessage("§aKendi Skyblock adan hazır, ortasında doğdun!");
    }
}
