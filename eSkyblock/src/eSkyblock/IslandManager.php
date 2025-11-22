<?php

namespace eSkyblock;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\entity\Location;

class IslandManager {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function createIsland(Player $player): void {
        $name = strtolower($player->getName());
        $worldName = "skyblock_" . $name;

        $wm = Server::getInstance()->getWorldManager();

        // Dünya yoksa oluştur
        if(!$wm->isWorldGenerated($worldName)){
            $wm->generateWorld($worldName); // MultiWorld void generator kullanılır
        }

        // Dünya yüklenmemişse yükle
        if(!$wm->isWorldLoaded($worldName)){
            $wm->loadWorld($worldName);
        }

        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: $worldName");
            return;
        }

        $baseY = 64;
        $center = new Vector3(0, $baseY, 0);

        // 5x5 platform: üstte çimen, altta toprak
        for($x = -2; $x <= 2; $x++){
            for($z = -2; $z <= 2; $z++){
                $world->setBlock($center->add($x, 0, $z), VanillaBlocks::GRASS());
                $world->setBlock($center->add($x, -1, $z), VanillaBlocks::DIRT());
            }
        }

        // Ortadaki sandık
        $chestPos = $center->add(0, 1, 0);
        $world->setBlock($chestPos, VanillaBlocks::CHEST());

        $tile = $world->getTile($chestPos);
        if($tile instanceof \pocketmine\block\tile\Chest){
            $inv = $tile->getInventory();
            $inv->addItem(VanillaBlocks::OAK_SAPLING()->asItem());
            $inv->addItem(VanillaBlocks::DIRT()->asItem()->setCount(5));
            $inv->addItem(VanillaBlocks::ICE()->asItem());
            $inv->addItem(VanillaBlocks::LAVA()->asItem());
            $inv->addItem(VanillaItems::WOODEN_PICKAXE());
        }

        // Büyümüş ağaç
        $treeBase = $center->add(1, 1, 1);
        $world->setBlock($treeBase, VanillaBlocks::DIRT());
        for($y = 1; $y <= 4; $y++){
            $world->setBlock($treeBase->add(0, $y, 0), VanillaBlocks::OAK_LOG());
        }
        for($x = -2; $x <= 2; $x++){
            for($z = -2; $z <= 2; $z++){
                for($y = 3; $y <= 5; $y++){
                    if(abs($x) + abs($z) < 4){
                        $world->setBlock($treeBase->add($x, $y, $z), VanillaBlocks::OAK_LEAVES());
                    }
                }
            }
        }

        // Oyuncuyu kendi dünyasına ışınla
        $loc = new Location($center->x + 0.5, $center->y + 2, $center->z + 0.5, $world, 0.0, 0.0);
        $player->teleport($loc);
        $player->sendMessage("§aKendi Skyblock dünyan hazır: §b{$world->getFolderName()}");
    }
}
