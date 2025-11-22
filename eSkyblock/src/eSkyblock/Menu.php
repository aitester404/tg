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
                        $this->plugin->getIslandManager()->teleportToIsland($player);
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
                        $this->openPartnerPermissionsMenu($player);
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
                        $player->sendMessage("§aRütbe sistemi için rank.yml dosyasını kontrol et!");
                        break;
                }
            }

            private function openPartnerPermissionsMenu(Player $player): void {
                $pdata = $this->plugin->getIslandManager()->getPlayerData($player);
                $partners = $pdata->get("island")["partners"] ?? [];

                if(empty($partners)){
                    $player->sendMessage("§cOrtak bulunmuyor!");
                    return;
                }

                $form = new class($partners, $this->plugin, $player) implements Form {
                    private array $partners;
                    private Main $plugin;
                    private Player $player;

                    public function __construct(array $partners, Main $plugin, Player $player){
                        $this->partners = $partners;
                        $this->plugin = $plugin;
                        $this->player = $player;
                    }

                    public function jsonSerialize(): array {
                        return [
                            "type" => "form",
                            "title" => "§aOrtak İzinleri",
                            "content" => "Bir ortağı seç:",
                            "buttons" => array_map(fn($p) => ["text" => $p], $this->partners)
                        ];
                    }

                    public function handleResponse(Player $player, $data): void {
                        if($data === null) return;
                        $partner = $this->partners[$data] ?? null;
                        if($partner === null) return;

                        $permManager = new PartnerPermissions($this->plugin);
                        $perms = $permManager->getPermissions($this->player, $partner);

                        $form = new class($partner, $perms, $permManager, $this->player) implements Form {
                            private string $partner;
                            private array $perms;
                            private PartnerPermissions $permManager;
                            private Player $owner;

                            public function __construct(string $partner, array $perms, PartnerPermissions $permManager, Player $owner){
                                $this->partner = $partner;
                                $this->perms = $perms;
                                $this->permManager = $permManager;
                                $this->owner = $owner;
                            }

                            public function jsonSerialize(): array {
                                return [
                                    "type" => "form",
                                    "title" => "§a" . $this->partner . " İzinleri",
                                    "content" => "İzinleri aç/kapat:",
                                    "buttons" => [
                                        ["text" => "Blok Koyma: " . ($this->perms["place"] ? "§aAçık" : "§cKapalı")],
                                        ["text" => "Blok Kırma: " . ($this->perms["break"] ? "§aAçık" : "§cKapalı")],
                                        ["text" => "Sandık Açma: " . ($this->perms["chest"] ? "§aAçık" : "§cKapalı")]
                                    ]
                                ];
                            }

                            public function handleResponse(Player $player, $data): void {
                                if($data === null) return;
                                switch($data){
                                    case 0:
                                        $this->permManager->togglePermission($this->owner, $this->partner, "place");
                                        break;
                                    case 1:
                                        $this->permManager->togglePermission($this->owner, $this->partner, "break");
                                        break;
                                    case 2:
                                        $this->permManager->togglePermission($this->owner, $this->partner, "chest");
                                        break;
                                }
                            }
                        };

                        $player->sendForm($form);
                    }
                };

                $player->sendForm($form);
            }
        };

        $player->sendForm($form);
    }
}
