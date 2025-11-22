<?php

namespace Skyblock\utils;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldCreationOptions;

class IslandManager {

    public static function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());

        // Eğer dünya yoksa oluştur
        if(!Server::getInstance()->getWorldManager()->isWorldGenerated($worldName)){
            Server::getInstance()->getWorldManager()->generateWorld($worldName, new WorldCreationOptions());
        }

        // Dünyayı yükle
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if($world === null){
            Server::getInstance()->getWorldManager()->loadWorld($worldName);
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        }

        // Ada spawn koordinatı
        $x = 0; $y = 100; $z = 0;

        // EasyEdit schematic dosyasını bu dünyaya yapıştır
        Server::getInstance()->dispatchCommand(Server::getInstance()->getConsoleSender(),
            "easyedit paste myisland $x $y $z $worldName");

        // Oyuncuyu kendi adasına ışınla
        $player->teleport(new \pocketmine\world\Position($x, $y, $z, $world));
        $player->sendMessage("§aKendi Skyblock adan hazır!");
    }
}
