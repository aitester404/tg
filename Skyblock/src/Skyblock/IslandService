<?php

namespace Skyblock;

use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\plugin\Plugin;

final class IslandService {

    public function __construct(private Plugin $plugin){}

    /**
     * Creates a per-player island by cloning plugin_data/template.mcworld to worlds/$worldName
     */
    public function createFromTemplate(string $worldName) : bool {
        $wm = Server::getInstance()->getWorldManager();

        // Already generated? Just ensure loaded.
        if($wm->isWorldGenerated($worldName)){
            if(!$wm->isWorldLoaded($worldName)){
                $wm->loadWorld($worldName);
            }
            return true;
        }

        $template = $this->plugin->getDataFolder() . "template.mcworld";
        if(!is_file($template)){
            $this->plugin->getLogger()->error("template.mcworld bulunamadı: " . $template);
            return false;
        }

        // Extract .mcworld (zip) to a temporary folder
        $tmpDir = $this->plugin->getDataFolder() . "tmp_" . $worldName;
        @mkdir($tmpDir);

        $ok = WorldCloner::unzipMcWorld($template, $tmpDir);
        if(!$ok){
            $this->plugin->getLogger()->error("template.mcworld açılamadı (ZipArchive hatası).");
            WorldCloner::deleteDirectory($tmpDir);
            return false;
        }

        // Find the actual world folder inside the extracted package
        // Some .mcworld zips contain files directly; others wrap in a folder.
        $worldSourceDir = WorldCloner::detectWorldRoot($tmpDir);
        if($worldSourceDir === null){
            $this->plugin->getLogger()->error(".mcworld içinde geçerli dünya yapısı bulunamadı (level.dat yok).");
            WorldCloner::deleteDirectory($tmpDir);
            return false;
        }

        // Copy to server worlds/$worldName
        $target = Server::getInstance()->getDataPath() . "worlds/" . $worldName;
        if(is_dir($target)){
            WorldCloner::deleteDirectory($target);
        }
        @mkdir(dirname($target), 0777, true);
        $copyOk = WorldCloner::copyDirectory($worldSourceDir, $target);
        // Cleanup temp
        WorldCloner::deleteDirectory($tmpDir);

        if(!$copyOk){
            $this->plugin->getLogger()->error("Dünya klasörü kopyalanamadı.");
            return false;
        }

        // Load the cloned world
        if(!$wm->isWorldGenerated($worldName)){
            // PM5 treats presence of level.dat as "generated"
            // If not recognized, try loadWorld anyway.
        }
        $wm->loadWorld($worldName);

        return true;
    }

    /**
     * Returns a safe spawn position in the island world (loads world if needed).
     */
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
        $spawn = $world->getSafeSpawn();
        return new Position($spawn->getX() + 0.5, $spawn->getY(), $spawn->getZ() + 0.5, $world);
    }

    /**
     * Unload and delete the island world directory.
     */
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
