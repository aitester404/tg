<?php

namespace Skyblock;

use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\plugin\Plugin;

final class IslandService {

    public function __construct(private Plugin $plugin){}

    public function createFromTemplate(string $worldName) : bool {
        $wm = Server::getInstance()->getWorldManager();

        if($wm->isWorldGenerated($worldName)){
            if(!$wm->isWorldLoaded($worldName)){
                $wm->loadWorld($worldName);
            }
            return true;
        }

        $template = $this->plugin->getDataFolder() . "template.mcworld";
        if(!is_file($template)){
            return false;
        }

        $tmpDir = $this->plugin->getDataFolder() . "tmp_" . $worldName;
        @mkdir($tmpDir);

        if(!WorldCloner::unzipMcWorld($template, $tmpDir)){
            WorldCloner::deleteDirectory($tmpDir);
            return false;
        }

        $worldSourceDir = WorldCloner::detectWorldRoot($tmpDir);
        if($worldSourceDir === null){
            WorldCloner::deleteDirectory($tmpDir);
            return false;
        }

        $target = Server::getInstance()->getDataPath() . "worlds/" . $worldName;
        if(is_dir($target)){
            WorldCloner::deleteDirectory($target);
        }
        @mkdir(dirname($target), 0777, true);
        WorldCloner::copyDirectory($worldSourceDir, $target);
        WorldCloner::deleteDirectory($tmpDir);

        $wm->loadWorld($worldName);
        return true;
    }

    public function getIslandSpawn(string $worldName) : ?Position {
        $wm = Server::getInstance()->getWorldManager();
        if(!$wm->isWorldLoaded($worldName)){
            if(!$wm->isWorldGenerated($worldName)){
                return null;
            }
            $wm->loadWorld($worldName);
        }
        $world = $wm->getWorldByName($worldName);
        if(!$world instanceof World){
            return null;
        }

        // Senin mcworld’deki spawn koordinatı (0, -52, 0)
        $world->loadChunk(0 >> 4, 0 >> 4, true);
        return new Position(0.5, -52, 0.5, $world);
    }

    public function deleteIsland(string $worldName) : bool {
        $wm = Server::getInstance()->getWorldManager();
        if($wm->isWorldLoaded($worldName)){
            $world = $wm->getWorldByName($worldName);
            if($world instanceof World){
                $wm->unloadWorld($world);
            }
        }

        $path = Server::getInstance()->getDataPath() . "worlds/" . $worldName;
        if(!is_dir($path)){
            return false;
        }
        WorldCloner::deleteDirectory($path);
        return !is_dir($path);
    }
}
