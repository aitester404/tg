<?php

namespace Skyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {

    private IslandService $islands;

    public function onEnable() : void {
        @mkdir($this->getDataFolder());
        $this->islands = new IslandService($this);
        $this->getLogger()->info("SkyblockFormIsland aktif!");
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
                case 0: $this->handleCreateIsland($player); break;
                case 1: $this->handleGoIsland($player); break;
                case 2: $this->handleDeleteIsland($player); break;
            }
        });

        $form->setTitle("§aSkyblock Menü");
        $form->setContent("§eSkyblock adanı yönet:");
        $form->addButton("§aAda Oluştur", 1, "textures/blocks/grass_top");
        $form->addButton("§bAdana Git",    1, "textures/items/ender_pearl");
        $form->addButton("§cAdanı Sil",    1, "textures/items/tnt");

        $player->sendForm($form);
    }

    private function handleCreateIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        if($this->islands->createFromTemplate($worldName)){
            $spawn = $this->islands->getIslandSpawn($worldName);
            if($spawn !== null){
                $player->teleport($spawn);
                $player->sendMessage("§aAdan hazır! Template.mcworld klonlandı.");
            } else {
                $player->sendMessage("§cSpawn noktası bulunamadı.");
            }
        } else {
            $player->sendMessage("§cAda oluşturulamadı. template.mcworld bulunamadı veya açılamadı.");
        }
    }

    private function handleGoIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        $spawn = $this->islands->getIslandSpawn($worldName);
        if($spawn !== null){
            $player->teleport($spawn);
            $player->sendMessage("§bAdana ışınlandın!");
        } else {
            $player->sendMessage("§cÖnce ada oluşturmalısın!");
        }
    }

    private function handleDeleteIsland(Player $player) : void {
        $worldName = "island_" . strtolower($player->getName());
        if($this->islands->deleteIsland($worldName)){
            $player->sendMessage("§cAdan silindi: §f$worldName");
        } else {
            $player->sendMessage("§cAdan bulunamadı veya silinemedi.");
        }
    }
}
