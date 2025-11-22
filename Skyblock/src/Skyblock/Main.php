<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat;

final class Main extends PluginBase {

    private IslandService $islands;

    public function onEnable() : void {
        @mkdir($this->getDataFolder());
        $this->islands = new IslandService($this);
        $this->getLogger()->info("SkyblockFormIsland aktif! .mcworld klonlama hazır.");
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
                case 0:
                    $this->handleCreateIsland($player);
                    break;
                case 1:
                    $this->handleGoIsland($player);
                    break;
                case 2:
                    $this->handleDeleteIsland($player);
                    break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönet:");
        // Iconed buttons (type 1 = in-game texture)
        $form->addButton("§aAda Oluştur", 1, "textures/blocks/grass_top");
        $form->addButton("§bAdana Git",    1, "textures/items/ender_pearl");
        $form->addButton("§cAdanı Sil",    1, "textures/items/tnt");

        $player->sendForm($form);
    }

    private function handleCreateIsland(Player $player) : void {
        $owner = strtolower($player->getName());
        $worldName = "island_" . $owner;

        $ok = $this->islands->createFromTemplate($worldName);
        if(!$ok){
            $player->sendMessage("§cAda oluşturulamadı. template.mcworld bulunamadı veya zip açılamadı.");
            return;
        }

        $spawn = $this->islands->getIslandSpawn($worldName);
        if($spawn === null){
            $player->sendMessage("§cDünya yüklenemedi: $worldName");
            return;
        }

        $player->teleport($spawn);
        $player->sendMessage("§aAdan hazır! §7(Template.mcworld klonlandı)");
    }

    private function handleGoIsland(Player $player) : void {
        $owner = strtolower($player->getName());
        $worldName = "island_" . $owner;

        $spawn = $this->islands->getIslandSpawn($worldName);
        if($spawn === null){
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
            return;
        }
        $player->teleport($spawn);
        $player->sendMessage("§bAdana ışınlandın!");
    }

    private function handleDeleteIsland(Player $player) : void {
        $owner = strtolower($player->getName());
        $worldName = "island_" . $owner;

        $deleted = $this->islands->deleteIsland($worldName);
        if($deleted){
            $player->sendMessage("§cAdan silindi: §f$worldName");
        }else{
            $player->sendMessage("§cAdan bulunamadı veya silinemedi.");
        }
    }
}

