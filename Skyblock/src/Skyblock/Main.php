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

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("SkyblockFormIsland aktif!");
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
                case 0: // Ada Oluştur
                    $this->createIsland($player);
                    break;
                case 1: // Adana Git
                    $this->goIsland($player);
                    break;
                case 2: // Ada Sil
                    $this->deleteIsland($player);
                    break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönetmek için bir seçenek seç:");
        $form->addButton("§aAda Oluştur");
        $form->addButton("§bAdana Git");
        $form->addButton("§cAdanı Sil");

        $player->sendForm($form);
    }

    private function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        // Dünya yoksa oluştur
        if(!$wm->isWorldGenerated($worldName)){
            $console = new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage());
            Server::getInstance()->dispatchCommand($console, "mw create $worldName 0 void");
            $player->sendMessage("§aVoid dünya oluşturuldu: $worldName");
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

        // Basit ada inşa (6x6 taş platform + ortada meşale)
        $baseX = 0; $baseY = 100; $baseZ = 0;
        for($x = 0; $x < 6; $x++){
            for($z = 0; $z < 6; $z++){
                $world->setBlockAt($baseX + $x, $baseY, $baseZ + $z, \pocketmine\block\VanillaBlocks::STONE());
            }
        }
        $centerX = $baseX + 3;
        $centerZ = $baseZ + 3;
        $world->setBlockAt($centerX, $baseY + 1, $centerZ, \pocketmine\block\VanillaBlocks::TORCH());

        // Oyuncuyu ortasına ışınla
        $player->teleport(new Position($centerX + 0.5, $baseY + 1, $centerZ + 0.5, $world));
        $player->sendMessage("§aAda oluşturuldu ve ortasına ışınlandın!");
    }

    private function goIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }
        $spawnX = 3; $spawnY = 101; $spawnZ = 3;
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
            $player->sendMessage("§cAdan silindi: $worldName");
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
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
