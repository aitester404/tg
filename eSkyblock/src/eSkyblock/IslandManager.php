<?php

namespace eSkyblock;

use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;

class IslandManager {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function createIsland(Player $player): void {
        $world = $player->getWorld();
        $center = $player->getPosition()->floor()->add(0, 10, 0);

        // 5x5 platform: üstte çimen, altında toprak
        for($x = -2; $x <= 2; $x++){
            for($z = -2; $z <= 2; $z++){
                $grassPos = $center->add($x, 0, $z);
                $dirtPos  = $center->add($x, -1, $z);
                $world->setBlock($grassPos, VanillaBlocks::GRASS());
                $world->setBlock($dirtPos, VanillaBlocks::DIRT());
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
        }

        // Küçük ağaç için toprak + fidan
        $treeBase = $center->add(1, 1, 1);
        $world->setBlock($treeBase, VanillaBlocks::DIRT());
        $world->setBlock($treeBase->add(0, 1, 0), VanillaBlocks::OAK_SAPLING());

        // Oyuncuyu adanın üstüne ışınla
        $player->teleport($center->add(0.5, 2, 0.5));
        $player->sendMessage("§aSkyblock adan oluşturuldu!");
    }
}
