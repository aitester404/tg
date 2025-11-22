<?php

namespace eSkyblock;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
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

        // Dünya yoksa MultiWorld ile oluştur (void)
        if(!$wm->isWorldGenerated($worldName)){
            Server::getInstance()->dispatchCommand(Server::getInstance()->getConsoleSender(), "mw create $worldName void");
        }

        // Yükle (zaten yüklüyse sorun değil)
        Server::getInstance()->dispatchCommand(Server::getInstance()->getConsoleSender(), "mw load $worldName");

        // Dünya instance'ını alma (MultiWorld yüklemesi 1-2 tick sürebilir → retry)
        $this->retryGetWorld($player, $worldName, function($world) use ($player){
            // Dünya hazır → adayı yerleştir
            $baseY = 64;
            $center = new Vector3(0, $baseY, 0);

            // 5x5 platform: üstte çimen, altta toprak
            for($x = -2; $x <= 2; $x++){
                for($z = -2; $z <= 2; $z++){
                    $world->setBlock($center->add($x, 0, $z), VanillaBlocks::GRASS());
                    $world->setBlock($center->add($x, -1, $z), VanillaBlocks::DIRT());
                }
            }

            // Ortadaki sandık (üst katmanda)
            $chestPos = $center->add(0, 1, 0);
            $world->setBlock($chestPos, VanillaBlocks::CHEST());

            // Sandık tile (1 tick sonra doldur)
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($world, $chestPos){
                $tile = $world->getTile($chestPos);
                if($tile instanceof \pocketmine\block\tile\Chest){
                    $inv = $tile->getInventory();
                    $inv->addItem(VanillaBlocks::OAK_SAPLING()->asItem());
                    $inv->addItem(VanillaBlocks::DIRT()->asItem()->setCount(5));
                    $inv->addItem(VanillaBlocks::ICE()->asItem());
                    $inv->addItem(VanillaBlocks::LAVA()->asItem());
                    $inv->addItem(VanillaItems::WOODEN_PICKAXE());
                }
            }), 1);

            // Büyümüş meşe ağacı (manuel bloklarla)
            $treeBase = $center->add(1, 1, 1);
            $world->setBlock($treeBase, VanillaBlocks::DIRT());

            // gövde
            for($y = 1; $y <= 4; $y++){
                $world->setBlock($treeBase->add(0, $y, 0), VanillaBlocks::OAK_LOG());
            }

            // yaprak katmanları
            $this->placeLeavesCube($world, $treeBase->add(0, 3, 0), 2);
            $this->placeLeavesCross($world, $treeBase->add(0, 5, 0));

            // Oyuncuyu kendi dünyasına ışınla
            $loc = new Location($center->x + 0.5, $center->y + 2, $center->z + 0.5, $world, 0.0, 0.0);
            $player->teleport($loc);
            $player->sendMessage("§aKendi Skyblock dünyan hazır: §b{$world->getFolderName()}");
        });
    }

    private function retryGetWorld(Player $player, string $worldName, callable $onReady, int $tries = 10, int $delayTicks = 2): void {
        $wm = Server::getInstance()->getWorldManager();
        $try = 0;

        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use (&$try, $tries, $delayTicks, $wm, $worldName, $onReady, $player){
            $world = $wm->getWorldByName($worldName);
            if($world !== null){
                $onReady($world);
                return;
            }

            $try++;
            if($try >= $tries){
                $player->sendMessage("§cDünya yüklenemedi: $worldName. Lütfen tekrar deneyin.");
                return;
            }

            // tekrar dene
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use (&$try, $tries, $delayTicks, $wm, $worldName, $onReady, $player){
                $world = $wm->getWorldByName($worldName);
                if($world !== null){
                    $onReady($world);
                    return;
                }
                $try++;
                if($try >= $tries){
                    $player->sendMessage("§cDünya yüklenemedi: $worldName. Lütfen tekrar deneyin.");
                    return;
                }
                // zincir şeklinde tekrar denemeleri sürdürelim
                $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use (&$try, $tries, $delayTicks, $wm, $worldName, $onReady, $player){
                    $world = $wm->getWorldByName($worldName);
                    if($world !== null){
                        $onReady($world);
                        return;
                    }
                    $try++;
                    if($try >= $tries){
                        $player->sendMessage("§cDünya yüklenemedi: $worldName. Lütfen tekrar deneyin.");
                        return;
                    }
                }), $delayTicks);
            }), $delayTicks);
        }), $delayTicks);
    }

    private function placeLeavesCube($world, Vector3 $origin, int $r): void {
        for($x = -$r; $x <= $r; $x++){
            for($z = -$r; $z <= $r; $z++){
                for($y = 0; $y <= 2; $y++){
                    if(abs($x) + abs($z) <= $r * 2){
                        $world->setBlock($origin->add($x, $y, $z), VanillaBlocks::OAK_LEAVES());
                    }
                }
            }
        }
    }

    private function placeLeavesCross($world, Vector3 $center): void {
        $world->setBlock($center, VanillaBlocks::OAK_LEAVES());
        $world->setBlock($center->add(1, 0, 0), VanillaBlocks::OAK_LEAVES());
        $world->setBlock($center->add(-1, 0, 0), VanillaBlocks::OAK_LEAVES());
        $world->setBlock($center->add(0, 0, 1), VanillaBlocks::OAK_LEAVES());
        $world->setBlock($center->add(0, 0, -1), VanillaBlocks::OAK_LEAVES());
    }
}
