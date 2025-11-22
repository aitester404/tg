<?php

namespace eSkyblock;

use pocketmine\form\Form;
use pocketmine\player\Player;

class Menu implements Form {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function jsonSerialize(): array {
        return [
            "type" => "form",
            "title" => "§aSkyblock Menüsü",
            "content" => "Bir seçenek seç:",
            "buttons" => [
                ["text" => "Ada Oluştur"],
                ["text" => "Ada Bilgisi"],
                ["text" => "Ada Sil"]
            ]
        ];
    }

    public function handleResponse(Player $player, $data): void {
        if($data === null) return;

        switch($data){
            case 0: // Ada Oluştur
                $this->plugin->getIslandManager()->createIsland($player);
                break;

            case 1: // Ada Bilgisi
                $player->sendMessage("§eAda bilgisi sistemi yakında.");
                break;

            case 2: // Ada Sil
                $player->sendMessage("§cAda silme özelliği yakında.");
                break;
        }
    }
}
