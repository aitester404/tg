<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\console\ConsoleCommandSender;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\block\VanillaBlocks;

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("SkyblockFormIsland (ikonlu, PM5 uyumlu) aktif!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if($command->getName() === "ada"){
            if($sender instanceof Player){
                $this->openIslandMenu($sender);
            }
            return true;
        }
        return false;
    }

    public function openIslandMenu(Player $player) : void {
        $form = new SimpleForm(function(Player $player, ?int $data){
            if($data === null) return;
            switch($data){
                case 0: $this->createIsland($player); break;
                case 1: $this->goIsland($player); break;
                case 2: $this->deleteIsland($player); break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönetmek için bir seçenek seç:");

        // İkonlu butonlar (type: 0 = URL, 1 = item)
        $form->addButton("§aAda Oluştur", 1, "textures/blocks/stone");
        $form->addButton("§bAdana Git", 1, "textures/items/ender_pearl");
        $form->addButton("§cAdanı Sil", 1, "textures/items/tnt");

        $player->sendForm($form);
    }

    private function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "mw create $worldName 0 void");
            $player->sendMessage("§aVoid dünya oluşturuldu: §f$worldName");
        }

        if(!$wm->isWorldLoaded($worldName)){
            $wm->loadWorld($worldName);
        }

        $world = $wm->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cDünya yüklenemedi: §f$worldName");
            return;
        }

        // Ada koordinatları
        $baseX = 0; $baseY = 100; $baseZ = 0;
        $width = 6; $length = 6;

        // Chunk yükle
        for($x = 0; $x < $width; $x++){
            for($z = 0; $z < $length; $z++){
                $world->loadChunk(($baseX + $x) >> 4, ($baseZ + $z) >> 4);
            }
        }

        // Taş platform
        for($x = 0; $x < $width; $x++){
            for($z = 0; $z < $length; $z++){
                $world->setBlockAt($baseX + $x, $baseY, $baseZ + $z, VanillaBlocks::STONE());
            }
        }

        // Ortada meşale
        $centerX = $baseX + intdiv($width, 2);
        $centerZ = $baseZ + intdiv($length, 2);
        $world->setBlockAt($centerX, $baseY + 1, $centerZ, VanillaBlocks::TORCH());

        // Başlangıç sandığı
        $world->setBlockAt($centerX + 1, $baseY + 1, $centerZ, VanillaBlocks::CHEST());

        // Küçük ağaç
        for($y = 1; $y <= 3; $y++){
            $world->setBlockAt($centerX - 2, $baseY + $y, $centerZ - 2, VanillaBlocks::OAK_LOG());
        }
        for($x = -3; $x <= -1; $x++){
            for($z = -3; $z <= -1; $z++){
                $world->setBlockAt($centerX + $x, $baseY + 4, $centerZ + $z, VanillaBlocks::OAK_LEAVES());
            }
        }

        // Su ve lava
        $world->setBlockAt($centerX - 2, $baseY + 1, $centerZ + 2, VanillaBlocks::WATER());
        $world->setBlockAt($centerX + 2, $baseY + 1, $centerZ - 2, VanillaBlocks::LAVA());

        // Oyuncuyu ortasına ışınla
        $player->teleport(new Position($centerX + 0.5, $baseY + 2, $centerZ + 0.5, $world));
        $player->sendMessage("§aAda oluşturuldu: taş platform, sandık, ağaç, su ve lava hazır!");
    }

    private function goIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }
        $spawnX = 3; $spawnY = 102; $spawnZ = 3;
        $world->loadChunk($spawnX >> 4, $spawnZ >> 4);
        $player->teleport(new Position($spawnX + 0.5, $spawnY, $spawnZ + 0.5, $world));
        $player->sendMessage("§bAdana ışınlandın!");
    }

    private function deleteIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        if($wm->isWorldLoaded($worldName)){
            $wm->unloadWorld($wm->getWorldByName($worldName));
        }

        $path = Server::getInstance()->getDataPath() . "worlds/" . $worldName;
        if(is_dir($path)){
            $this->deleteDirectory($path);
            $player->sendMessage("§cAdan silindi: §f$worldName");
        } else {
            $player->sendMessage("§cAdan bulunamadı!");
        }
    }

    private function deleteDirectory(string $dir) : void {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach($files as $file){
            $path = "$dir/$file";
            if(is_dir($path)){
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
