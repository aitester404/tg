<?php

namespace eSkyblock;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class IslandGenerator {

    public static function createIsland(Player $player): void {
        $world = $player->getWorld();
        $center = $player->getPosition()->floor()->add(0, 10, 0); // oyuncunun üstünde 10 blok yukarıda ada

        // 5x5 taş platform
        for($x = -2; $x <= 2; $x++){
            for($z = -2; $z <= 2; $z++){
                $pos = $center->add($x, 0, $z);
                $world->setBlock($pos, VanillaBlocks::STONE());
            }
        }

        // Ortadaki sandık
        $chestPos = $center->add(0, 1, 0);
        $world->setBlock($chestPos, VanillaBlocks::CHEST());

        // Sandığa başlangıç itemleri ekle
        $tile = $world->getTile($chestPos);
        if($tile instanceof \pocketmine\block\tile\Chest){
            $inv = $tile->getInventory();
            $inv->addItem(VanillaBlocks::SAPLING()->asItem()); // ağaç için fidan
            $inv->addItem(VanillaBlocks::DIRT()->asItem()->setCount(5)); // toprak
            $inv->addItem(VanillaBlocks::ICE()->asItem());
            $inv->addItem(VanillaBlocks::LAVA()->asItem());
        }

        // Ortasına bir toprak + fidan (ağaç için)
        $treeBase = $center->add(1, 1, 1);
        $world->setBlock($treeBase, VanillaBlocks::DIRT());
        $world->setBlock($treeBase->add(0, 1, 0), VanillaBlocks::OAK_SAPLING());

        // Oyuncuyu adanın üstüne ışınla
        $player->teleport($center->add(0.5, 2, 0.5));
        $player->sendMessage("§aSkyblock adan hazır!");
    }
}
