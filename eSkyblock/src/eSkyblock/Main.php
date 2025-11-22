<?php

namespace eSkyblock;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Main extends PluginBase {

    private IslandManager $islandManager;

    public function onEnable(): void {
        $this->islandManager = new IslandManager($this);
        $this->getLogger()->info("§aSkyblock plugin aktif!");
    }

    public function getIslandManager(): IslandManager {
        return $this->islandManager;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() === "ada"){
            if($sender instanceof Player){
                $menu = new Menu($this); // Menü sınıfını oluştur
                $sender->sendForm($menu); // Menü açılır
            } else {
                $sender->sendMessage("§cBu komut sadece oyunda kullanılabilir!");
            }
            return true;
        }
        return false;
    }
}
