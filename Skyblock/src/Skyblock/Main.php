<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use easyedit\EasyEdit; // EasyEdit ana sınıfı

class Main extends PluginBase {

    public function onEnable() : void {
        $this->getLogger()->info("SkyblockFormIsland (EasyEdit schematic) aktif!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
        if($command->getName() !== "ada"){
            return false;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "Bu komut sadece oyuncular içindir.");
            return true;
        }
        $this->openIslandMenu($sender);
        return true;
    }

    private function openIslandMenu(Player $player) : void {
        $form = new SimpleForm(function(Player $player, ?int $data){
            if($data === null) return;
            switch($data){
                case 0: $this->createIsland($player); break;
                case 1: $this->goIsland($player); break;
                case 2: $this->deleteIsland($player); break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönet:");
        $form->addButton("§aAda Oluştur", 1, "textures/blocks/grass_top");
        $form->addButton("§bAdana Git",    1, "textures/items/ender_pearl");
        $form->addButton("§cAdanı Sil",    1, "textures/items/tnt");

        $player->sendForm($form);
    }

    private function createIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $wm = Server::getInstance()->getWorldManager();

        // Dünya yoksa oluştur (void)
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

        // EasyEdit schematic yolu
        $schemPath = Server::getInstance()->getDataPath() . "plugin_data/EasyEdit/schematics/template.schem";
        if(!is_file($schemPath)){
            $player->sendMessage("§cSchematic bulunamadı: template.schem");
            return;
        }

        // Paste schematic
        $pastePos = new Position(0, 100, 0, $world);
        EasyEdit::getInstance()->getSchematicsManager()->pasteSchematic($schemPath, $pastePos);

        // Oyuncuyu spawn noktasına ışınla
        $player->teleport(new Position(0.5, 101, 0.5, $world));
        $player->sendMessage("§aAda oluşturuldu: schematic paste edildi!");
    }

    private function goIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if($world === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }
        $player->teleport(new Position(0.5, 101, 0.5, $world));
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

