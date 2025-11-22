<?php

namespace eSkyblock;

use pocketmine\player\Player;
use pocketmine\form\Form;

class Menu {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function openMenu(Player $player): void {
        $data = $this->plugin->getIslandManager()->getPlayerData($player);

        $hasIsland = $data->exists("island");

        $buttons = [];

        if(!$hasIsland){
            $buttons[] = "Ada Oluştur";
        } else {
            $buttons[] = "Adana Git";
            $buttons[] = "Ada Sıfırlama";
            $buttons[] = "Ada Bilgi";
            $buttons[] = "Ada Ortakları";
            $buttons[] = "Ortak İzinleri";
            $buttons[] = "Seviye Veren Bloklar";
            $buttons[] = "Rütbe Sistemi";
        }

        $form = new class($buttons, $this->plugin, $player) implements Form {
            private array $buttons;
            private Main $plugin;
            private Player $player;

            public function __construct(array $buttons, Main $plugin, Player $player){
                $this->buttons = $buttons;
                $this->plugin = $plugin;
                $this->player = $player;
            }

            public function jsonSerialize(): array {
                return [
                    "type" => "form",
                    "title" => "§aAda Menüsü",
                    "content" => "Bir seçenek seç:",
                    "buttons" => array_map(fn($b) => ["text" => $b], $this->buttons)
                ];
            }

            public function handleResponse(Player $player, $data): void {
                if($data === null) return;
                $choice = $this->buttons[$data] ?? null;
                if($choice === null) return;

                switch($choice){
                    case "Ada Oluştur":
                        $this->plugin->getIslandManager()->createIsland($player);
                        break;
                    case "Adana Git":
                        // Oyuncuyu ada spawn noktasına ışınla
                        $player->sendMessage("§aAdana ışınlandın!");
                        break;
                    case "Ada Sıfırlama":
                        $this->plugin->getIslandManager()->resetIsland($player);
                        break;
                    case "Ada Bilgi":
                        $pdata = $this->plugin->getIslandManager()->getPlayerData($player);
                        $info = $pdata->get("island", []);
                        $partners = implode(", ", $info["partners"] ?? []);
                        $player->sendMessage("§aAda Bilgisi:\n§7Seviye: " . $info["level"] . "\n§7XP: " . $info["xp"] . "\n§7Ortaklar: " . ($partners ?: "Yok"));
                        break;
                    case "Ada Ortakları":
                        $player->sendMessage("§aOrtak eklemek için: /ada ortak ekle <oyuncu>\nOrtak silmek için: /ada ortak sil <oyuncu>");
                        break;
                    case "Ortak İzinleri":
                        $player->sendMessage("§aOrtak izinleri menüsü yakında!");
                        break;
                    case "Seviye Veren Bloklar":
                        $xpBlocks = $this->plugin->getConfig()->get("xpBlocks", []);
                        $msg = "§aSeviye Veren Bloklar:\n";
                        foreach($xpBlocks as $block => $xp){
                            $msg .= "§7" . $block . " = " . $xp . "xp\n";
                        }
                        $player->sendMessage($msg);
                        break;
                    case "Rütbe Sistemi":
                        $ranks = $this->plugin->getResource("rank.yml");
                        $player->sendMessage("§aRütbe sistemi için rank.yml dosyasını kontrol et!");
                        break;
                }
            }
        };

        $player->sendForm($form);
    }
}
